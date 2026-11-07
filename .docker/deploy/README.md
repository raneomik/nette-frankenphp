# Deployment (VPS + caddy-docker-proxy)

Pushing a version tag builds the image (`.github/workflows/deploy.yml`), pushes it to
GHCR, then runs `deploy.sh <version>` over SSH on the VPS, which replaces the container with no
downtime using [docker rollout](https://github.com/wowu/docker-rollout).

```sh
git tag v1.3.0 && git push origin v1.3.0
```

| Git tag       | Image tags                               | Deployed |
|---------------|------------------------------------------|----------|
| `v1.3.0`      | `1.3.0`, `1.3`, `sha-8f3c2a9`, `latest` | `1.3.0`  |
| `v1.4.0-rc.1` | `1.4.0-rc.1`, `sha-8f3c2a9`              | `1.4.0-rc.1` |
| (branch, manual run) | `sha-8f3c2a9`                     | no       |

Redeploy a version: Actions tab → Build & deploy → Run workflow, picking the tag.
Quick rollback: `ssh deploy@vps /srv/rpsls/deploy.sh 1.2.0` (images are kept for 7 days).

## Setup (once)

### VPS

First of all: the subdomain points to the VPS (DNS), and the caddy-docker-proxy network
is indeed called `caddy` (`docker network ls`); otherwise, adapt `networks:` in `compose.yaml`.

```sh
# dedicated user (being in the docker group amounts to being root: dedicated SSH key)
sudo adduser --disabled-password deploy && sudo usermod -aG docker deploy

# docker rollout plugin, for the deploy user
mkdir -p ~/.docker/cli-plugins
curl -fsSL https://raw.githubusercontent.com/wowu/docker-rollout/v0.14/docker-rollout \
  -o ~/.docker/cli-plugins/docker-rollout
chmod +x ~/.docker/cli-plugins/docker-rollout

# access to the image if the GHCR package is private (classic PAT, read:packages scope)
docker login ghcr.io -u raneomik

# /srv/rpsls: compose.yaml, deploy.sh (chmod +x), litestream.yml,
# app.env (from app.env.example, chmod 600: fresh Mercure keys, TRUSTED_PROXIES)
# and .env, read by compose for ${HOST} (deploy.sh adds TAG=… to it without touching the rest):
echo 'HOST=rpsls.example.fr' > /srv/rpsls/.env

# subnet of the caddy network, for TRUSTED_PROXIES in app.env:
docker network inspect caddy -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}'
```

Also replace `rpsls.example.fr` in the workflow environment URL.

### SSH key (on your machine)

```sh
ssh-keygen -t ed25519 -f deploy_key -N "" -C "github-deploy"
# deploy_key.pub → ~deploy/.ssh/authorized_keys on the VPS
# deploy_key     → VPS_SSH_KEY secret
ssh-keyscan -t ed25519 your-vps.example   # → VPS_KNOWN_HOSTS secret (check the fingerprint)
```

### GitHub

Settings → Environments → `production`, secrets: `VPS_HOST`, `VPS_USER` (`deploy`),
`VPS_SSH_KEY`, `VPS_KNOWN_HOSTS`, and `VPS_PORT` if SSH does not listen on 22.
In that same environment, "Deployment branches and tags" → Selected → tag `v*`: only
version tags get access to the SSH secrets.

Settings → Rules → New tag ruleset, target `v*`, restrict creations/updates/deletions (with
yourself as bypass): nobody else can create a tag that deploys, or move an existing tag.

## First deployment

```sh
# 1. locally: the prod image builds (base images, assets, dependencies)
docker build --target prod -f .docker/frankenphp/Dockerfile .

# 2. a tag: the workflow builds, pushes to GHCR and runs deploy.sh
git tag v0.1.0 && git push origin v0.1.0
```

3. Check, on the VPS (`cd /srv/rpsls`) then in a browser:
   - `docker compose ps`: `app` is `healthy`;
   - `https://<domain>/health` answers `ok`;
   - a game with two browsers (one of them in private browsing);
   - `docker compose logs app`: no errors; with Litestream, `docker compose logs litestream`.

Without a correct `TRUSTED_PROXIES`, the home page answers 500 (the `__Secure-` Mercure cookie
requires Nette to see HTTPS).

## What the prod image assumes

- `SERVER_NAME=:80`: TLS terminated by caddy-docker-proxy.
- `TRUSTED_PROXIES` = subnet of the `caddy` network: otherwise Nette sees `http://`, and both the
  Mercure hub URLs and the JWT `iss` claim are wrong.
- `MERCURE_ISSUER` = `https://<public domain>`, equal to the JWT `iss`.
- `/health` answers `ok` when a PHP worker is running (Docker and Caddy healthcheck).
- The Mercure hub is built into FrankenPHP: on each deployment, SSE connections
  reconnect, and during the few seconds of switchover both instances each have
  their own hub. A separate hub (`dunglas/mercure`) removes this issue.

## SQLite database

- File `var/data/app.db`, in the `data` volume (the whole folder: the `-wal` and
  `-shm` files must stay next to it). Both instances of a rollout and Litestream share it
  safely (same host, SQLite locks).
- Each connection is configured by `App\Core\Database\SqliteConfigurator`: WAL,
  `busy_timeout`, `synchronous = NORMAL`, foreign keys, and `IMMEDIATE` transactions
  (PHP 8.5): under load, without that last setting, concurrent "read then write"
  transactions fail with `database is locked`.

### Migrations: [dbmate](https://github.com/amacneil/dbmate)

SQL files in `db/migrations`, applied when the container starts (`dbmate up`). If a
migration fails, the new container does not start and the old one stays in place.

```sh
make migration name=create_game   # creates db/migrations/<timestamp>_create_game.sql
make migrate                      # applies (dev); also updates db/schema.sql, to commit
make rollback                     # reverts the last one (dev), via its migrate:down section
```

```sql
-- migrate:up
CREATE TABLE game (id INTEGER PRIMARY KEY, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);

-- migrate:down
DROP TABLE game;
```

The old version still runs for a few seconds on the migrated database: a migration must
stay compatible with the previous code (add a column now, drop the old one
in the next version). In prod, there is no schema `rollback`: fix it with a
new migration, or restore the database (below).

### Backup: [Litestream](https://litestream.io)

Optional: without `litestream.env`, `deploy.sh` still deploys and warns that the database
is not backed up.

The `litestream` service continuously replicates the database (every 10 s) to
`LITESTREAM_REPLICA_URL`, off the VPS. No need for AWS: any S3-compatible storage, or SFTP
(`litestream.env.example`). This game's database weighs a few MB, free tiers are enough:

| Storage | Account | Free |
|---|---|---|
| Backblaze B2 | yes, application key restricted to the bucket | 10 GB, free uploads |
| Cloudflare R2 | yes, R2 API token | 10 GB, 1 M writes/month |
| SFTP (NAS, another server) | none, an SSH key | — |

Restore possible to any point in time within the last 7 days (`litestream.yml`): to within
~30 s (to the second over the last 5 minutes).

With SFTP, add the key in `litestream.yml` (under `replica:`) and mount it in the service:

```yaml
      key-path: /etc/litestream/sftp_key
      host-key: ssh-ed25519 AAAA…   # ssh-keyscan nas.example.fr, to check
```
```yaml
    volumes:
      - ./sftp_key:/etc/litestream/sftp_key:ro   # chmod 600, readable by uid 1000
```

`deploy.sh` records the time of each deployment in `deploys.log`: it is the restore
point "just before this version migrates the database".

```sh
# check that replication keeps up
docker compose logs --tail 20 litestream

# restore (e.g. after a failed migration), then redeploy the previous version:
# app AND litestream stopped while the file is replaced
docker compose stop app litestream
docker compose run --rm litestream restore -timestamp 2026-11-04T16:43:25Z -o /data/restored.db /data/app.db
docker compose run --rm --entrypoint sh litestream -c \
  'cd /data && mv restored.db app.db && rm -f app.db-wal app.db-shm'
./deploy.sh 1.2.0   # restarts litestream (resumes from the restored state) then the app
```

Without `-timestamp`, the latest replicated state is restored (VPS lost: new server, same
`litestream.env`, restore before the first deployment).
