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

# /srv/rpsls : compose.yaml, deploy.sh (chmod +x) et app.env (depuis app.env.example, chmod 600)
```

Remplace `rpsls.example.fr` dans `compose.yaml` (labels `caddy` et `MERCURE_ISSUER`) et dans
l'URL de l'environnement du workflow.

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

## Ce que l'image prod suppose

- `SERVER_NAME=:80` : TLS terminé par caddy-docker-proxy.
- `TRUSTED_PROXIES` = sous-réseau du réseau `caddy` : sinon Nette voit `http://`, et les URLs
  du hub Mercure comme le claim `iss` des JWT sont faux.
- `MERCURE_ISSUER` = `https://<domaine public>`, égal au `iss` des JWT.
- `/health` répond `ok` quand un worker PHP tourne (healthcheck Docker et Caddy).
- Le hub Mercure est intégré à FrankenPHP : à chaque déploiement, les connexions SSE se
  reconnectent, et pendant les quelques secondes de bascule les deux instances ont chacune
  leur hub. Un hub séparé (`dunglas/mercure`) supprime ce point.
