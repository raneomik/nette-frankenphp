# Déploiement (VPS + caddy-docker-proxy)

Pousser un tag de version construit l'image (`.github/workflows/deploy.yml`), la pousse sur
GHCR, puis lance `deploy.sh <version>` en SSH sur le VPS, qui remplace le conteneur sans
coupure avec [docker rollout](https://github.com/wowu/docker-rollout).

```sh
git tag v1.3.0 && git push origin v1.3.0
```

| Tag git       | Tags de l'image                          | Déployé |
|---------------|------------------------------------------|---------|
| `v1.3.0`      | `1.3.0`, `1.3`, `sha-8f3c2a9`, `latest` | `1.3.0` |
| `v1.4.0-rc.1` | `1.4.0-rc.1`, `sha-8f3c2a9`              | `1.4.0-rc.1` |
| (branche, lancement manuel) | `sha-8f3c2a9`              | non     |

Redéployer une version : onglet Actions → Build & deploy → Run workflow, en choisissant le tag.
Rollback rapide : `ssh deploy@vps /srv/rpsls/deploy.sh 1.2.0` (images gardées 7 jours).

## Mise en place (une fois)

### VPS

Avant tout : le sous-domaine pointe vers le VPS (DNS), et le réseau de caddy-docker-proxy
s'appelle bien `caddy` (`docker network ls`) ; sinon, adapte `networks:` dans `compose.yaml`.

```sh
# utilisateur dédié (être dans le groupe docker revient à être root : clé SSH dédiée)
sudo adduser --disabled-password deploy && sudo usermod -aG docker deploy

# plugin docker rollout, pour l'utilisateur deploy
mkdir -p ~/.docker/cli-plugins
curl -fsSL https://raw.githubusercontent.com/wowu/docker-rollout/v0.14/docker-rollout \
  -o ~/.docker/cli-plugins/docker-rollout
chmod +x ~/.docker/cli-plugins/docker-rollout

# accès à l'image si le package GHCR est privé (PAT classique, scope read:packages)
docker login ghcr.io -u raneomik

# /srv/rpsls : compose.yaml, deploy.sh (chmod +x), litestream.yml,
# app.env (depuis app.env.example, chmod 600 : clés Mercure neuves, TRUSTED_PROXIES)
# et .env, lu par compose pour ${HOST} (deploy.sh y ajoute TAG=… sans toucher au reste) :
echo 'HOST=rpsls.example.fr' > /srv/rpsls/.env

# sous-réseau du réseau caddy, pour TRUSTED_PROXIES dans app.env :
docker network inspect caddy -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}'
```

Remplace aussi `rpsls.example.fr` dans l'URL de l'environnement du workflow.

### Clé SSH (sur ta machine)

```sh
ssh-keygen -t ed25519 -f deploy_key -N "" -C "github-deploy"
# deploy_key.pub → ~deploy/.ssh/authorized_keys sur le VPS
# deploy_key     → secret VPS_SSH_KEY
ssh-keyscan -t ed25519 ton-vps.fr   # → secret VPS_KNOWN_HOSTS (vérifie l'empreinte)
```

### GitHub

Settings → Environments → `production`, secrets : `VPS_HOST`, `VPS_USER` (`deploy`),
`VPS_SSH_KEY`, `VPS_KNOWN_HOSTS`, et `VPS_PORT` si SSH n'écoute pas sur 22.
Dans ce même environnement, « Deployment branches and tags » → Selected → tag `v*` : seuls
les tags de version accèdent aux secrets SSH.

Settings → Rules → New tag ruleset, cible `v*`, restrict creations/updates/deletions (en te
mettant en bypass) : personne d'autre ne peut créer un tag qui déploie, ni déplacer un tag existant.

## Premier déploiement

```sh
# 1. en local : l'image prod se construit (images de base, assets, dépendances)
docker build --target prod -f .docker/frankenphp/Dockerfile .

# 2. un tag : le workflow construit, pousse sur GHCR et lance deploy.sh
git tag v0.1.0 && git push origin v0.1.0
```

3. Vérifier, sur le VPS (`cd /srv/rpsls`) puis dans un navigateur :
   - `docker compose ps` : `app` est `healthy` ;
   - `https://<domaine>/health` répond `ok` ;
   - une partie avec deux navigateurs (dont un en navigation privée) ;
   - `docker compose logs app` : pas d'erreur ; avec Litestream, `docker compose logs litestream`.

Sans `TRUSTED_PROXIES` correct, la page d'accueil répond 500 (le cookie Mercure `__Secure-`
exige que Nette voie le HTTPS).

## Ce que l'image prod suppose

- `SERVER_NAME=:80` : TLS terminé par caddy-docker-proxy.
- `TRUSTED_PROXIES` = sous-réseau du réseau `caddy` : sinon Nette voit `http://`, et les URLs
  du hub Mercure comme le claim `iss` des JWT sont faux.
- `MERCURE_ISSUER` = `https://<domaine public>`, égal au `iss` des JWT.
- `/health` répond `ok` quand un worker PHP tourne (healthcheck Docker et Caddy).
- Le hub Mercure est intégré à FrankenPHP : à chaque déploiement, les connexions SSE se
  reconnectent, et pendant les quelques secondes de bascule les deux instances ont chacune
  leur hub. Un hub séparé (`dunglas/mercure`) supprime ce point.

## Base SQLite

- Fichier `var/data/app.db`, dans le volume `data` (le dossier entier : les fichiers `-wal` et
  `-shm` doivent rester à côté). Les deux instances d'un rollout et Litestream le partagent sans
  risque (même hôte, verrous SQLite).
- Chaque connexion est configurée par `App\Core\Database\SqliteConfigurator` : WAL,
  `busy_timeout`, `synchronous = NORMAL`, clés étrangères, et transactions `IMMEDIATE`
  (PHP 8.5) : sous charge, sans ce dernier réglage, les transactions « lire puis écrire »
  concurrentes échouent en `database is locked`.

### Migrations : [dbmate](https://github.com/amacneil/dbmate)

Fichiers SQL dans `db/migrations`, appliqués au démarrage du conteneur (`dbmate up`). Si une
migration échoue, le nouveau conteneur ne démarre pas et l'ancien reste en place.

```sh
make migration name=create_game   # crée db/migrations/<horodatage>_create_game.sql
make migrate                      # applique (dev) ; met aussi à jour db/schema.sql, à commiter
make rollback                     # annule la dernière (dev), via sa section migrate:down
```

```sql
-- migrate:up
CREATE TABLE game (id INTEGER PRIMARY KEY, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);

-- migrate:down
DROP TABLE game;
```

L'ancienne version tourne encore quelques secondes sur la base migrée : une migration doit
rester compatible avec le code précédent (ajouter une colonne maintenant, supprimer l'ancienne
à la version suivante). En prod, on ne fait pas de `rollback` de schéma : on corrige par une
nouvelle migration, ou on restaure la base (ci-dessous).

### Sauvegarde : [Litestream](https://litestream.io)

Facultatif : sans `litestream.env`, `deploy.sh` déploie quand même et prévient que la base
n'est pas sauvegardée.

Le service `litestream` réplique la base en continu (toutes les 10 s) vers
`LITESTREAM_REPLICA_URL`, hors du VPS. Pas besoin d'AWS : tout stockage compatible S3, ou SFTP
(`litestream.env.example`). Une base de ce jeu pèse quelques Mo, les offres gratuites suffisent :

| Stockage | Compte | Gratuit |
|---|---|---|
| Backblaze B2 | oui, clé d'application limitée au bucket | 10 Go, envois gratuits |
| Cloudflare R2 | oui, jeton API R2 | 10 Go, 1 M d'écritures/mois |
| SFTP (NAS, autre serveur) | aucun, une clé SSH | — |

Restauration possible à n'importe quel instant des 7 derniers jours (`litestream.yml`) : à
~30 s près (à la seconde sur les 5 dernières minutes).

En SFTP, ajoute la clé dans `litestream.yml` (sous `replica:`) et monte-la dans le service :

```yaml
      key-path: /etc/litestream/sftp_key
      host-key: ssh-ed25519 AAAA…   # ssh-keyscan nas.example.fr, à vérifier
```
```yaml
    volumes:
      - ./sftp_key:/etc/litestream/sftp_key:ro   # chmod 600, lisible par l'uid 1000
```

`deploy.sh` note dans `deploys.log` l'heure de chaque déploiement : c'est le point de
restauration « juste avant que cette version migre la base ».

```sh
# vérifier que la réplication suit
docker compose logs --tail 20 litestream

# restaurer (ex. après une migration ratée), puis redéployer la version précédente :
# app ET litestream arrêtés pendant qu'on remplace le fichier
docker compose stop app litestream
docker compose run --rm litestream restore -timestamp 2026-11-04T16:43:25Z -o /data/restored.db /data/app.db
docker compose run --rm --entrypoint sh litestream -c \
  'cd /data && mv restored.db app.db && rm -f app.db-wal app.db-shm'
./deploy.sh 1.2.0   # relance litestream (reprend depuis l'état restauré) puis l'app
```

Sans `-timestamp`, on restaure le dernier état répliqué (VPS perdu : nouveau serveur, même
`litestream.env`, restaurer avant le premier déploiement).
