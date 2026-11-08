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
Quick rollback: `ssh <you>@your-vps.example <dir>/deploy.sh 1.2.0` (images are kept for 7 days).

## Setup (once)

`<you>` below is your usual user on the VPS: it deploys too, no dedicated user. GitHub gets its
own key, which can only run `deploy.sh` (see SSH key).

`<dir>` is the project folder on the VPS: any folder `<you>` can write to, as an absolute path
(e.g. `/home/<you>/frankenette`). The same value goes in the `VPS_PROJECT_DIR` secret (GitHub
below). Nothing in the repository assumes a particular path.

### VPS

First of all: the subdomain points to the VPS (DNS), the caddy-docker-proxy network
is indeed called `caddy` (`docker network ls`; otherwise, adapt `networks:` in `compose.yaml`),
and `<you>` runs docker without sudo (`groups` lists `docker`).

```sh
# docker rollout plugin, for <you>
mkdir -p ~/.docker/cli-plugins
curl -fsSL https://raw.githubusercontent.com/wowu/docker-rollout/v0.14/docker-rollout \
  -o ~/.docker/cli-plugins/docker-rollout
chmod +x ~/.docker/cli-plugins/docker-rollout

# access to the image if the GHCR package is private (stored in ~/.docker/config.json of <you>).
# The password is a classic PAT with the read:packages scope only (fine-grained tokens do not
# support GHCR, the GitHub password is refused); renew it before it expires, or pulls fail
read -rs CR_PAT && echo "$CR_PAT" | docker login ghcr.io -u raneomik --password-stdin; unset CR_PAT

# the project folder (Server files below); outside your home, create it with sudo then
# give it to <you>: sudo mkdir -p <dir> && sudo chown "$USER:" <dir>
mkdir -p <dir>
```

### SSH key (on your machine)

```sh
ssh-keygen -t ed25519 -f deploy_key -N "" -C "github-deploy"
# deploy_key     → VPS_SSH_KEY secret
ssh-keyscan -t ed25519 your-vps.example   # → VPS_KNOWN_HOSTS secret (check the fingerprint)
```

`deploy_key.pub` goes in `~/.ssh/authorized_keys` of `<you>` on the VPS, restricted to the
deployment: `command=` runs `deploy.sh` whatever the client asks (it reads the version from
`$SSH_ORIGINAL_COMMAND`), `restrict` disables port forwarding, agent and terminal. A leaked
key can only redeploy an existing image; to revoke it, delete the line.

```
command="<dir>/deploy.sh",restrict ssh-ed25519 AAAA… github-deploy
```

This key therefore gives no shell and no `scp`: for everything else, keep your own access.
Test from your machine (`-i`: not a default key name):

```sh
ssh -i deploy_key <you>@your-vps.example latest   # runs deploy.sh latest, once an image is pushed
```

Asked for a password: sshd silently ignores an `authorized_keys` owned by root or writable by
others (`chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys && chmod go-w ~`);
`sudo journalctl -u ssh -f` on the VPS while connecting gives the reason.

### Server files

The project itself is not copied to the VPS: the code ships in the image pulled from GHCR.
`<dir>` only holds the files of this folder. Copy them once, from the project root on your
machine; afterwards `deploy.sh` keeps them in sync by itself (see Deploy files below):

```sh
scp .docker/deploy/{compose.yaml,deploy.sh,litestream.yml,app.env.example,litestream.env.example} \
  <you>@your-vps.example:<dir>/
```

Then on the VPS:

```sh
cd <dir>
chmod +x deploy.sh

# app secrets, never committed: a fresh Mercure secret, and TRUSTED_PROXIES = subnet
# of the caddy network:
#   docker network inspect caddy -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}'
cp app.env.example app.env && chmod 600 app.env
sed -i "s#^MERCURE_JWT_SECRET=.*#MERCURE_JWT_SECRET=$(openssl rand -base64 48)#" app.env
nano app.env   # TRUSTED_PROXIES

# public domain, read by compose for ${HOST} (deploy.sh adds TAG=… to it without touching the rest)
echo 'HOST=rpsls.example.fr' > .env

# optional backup (see Litestream below): without this file, deploy.sh deploys and warns
cp litestream.env.example litestream.env && chmod 600 litestream.env && nano litestream.env
```

Changes to `compose.yaml`, `deploy.sh`, `litestream.yml` or the `*.example` files then reach the
server with the next deployment, without copying anything (Deploy files below).

### GitHub

Settings → Environments → `production`:
- secrets: `VPS_HOST`, `VPS_USER` (`<you>`), `VPS_PROJECT_DIR` (the project folder on the VPS,
  absolute, e.g. `/home/<you>/frankenette`), `VPS_SSH_KEY`, `VPS_KNOWN_HOSTS`, and `VPS_PORT` if
  SSH does not listen on 22;
- variable: `APP_URL` (`https://rpsls.example.fr`), the environment link and the smoke test
  target (`$APP_URL/health` after each deployment, from outside: DNS, TLS, proxy);
- "Deployment branches and tags" → Selected → tag `v*`: only version tags get access to the
  SSH secrets.

Settings → Rules → New tag ruleset, target `v*`, restrict creations/updates/deletions (with
yourself as bypass): nobody else can create a tag that deploys, or move an existing tag.

What the workflow does, and why:
- it runs `$VPS_PROJECT_DIR/deploy.sh 1.3.0@sha256:…`: the digest pins the exact image just
  built (a tag can be pushed again, a digest cannot change); `deploy.sh 1.2.0` by hand still works;
- the token has no right by default (`permissions: {}`), the build job only `packages: write`;
- Dependabot (`.github/dependabot.yml`) proposes the new major versions of the actions;
- jobs have a timeout, and the deployment ends with the smoke test.

## Deploy files

The prod image carries the server-side files of its version under `/deploy` (`compose.yaml`,
`deploy.sh`, `litestream.yml`, `app.env.example`, `litestream.env.example`). On each deployment,
`deploy.sh` extracts them from the image it deploys and installs the ones that changed next to
itself, before starting anything:

- the server always matches what runs, a manual rollback (`deploy.sh 1.2.0`) included, and the
  GitHub key keeps its single right: running `deploy.sh`;
- a new `deploy.sh` takes over for that same deployment;
- `litestream` is recreated when `litestream.yml` changed (it reads that file at start only);
- secrets are never touched: `.env`, `app.env`, `litestream.env` are not in the image. A new
  variable in `app.env.example` is to be copied into `app.env` by hand.

An image built before this mechanism ships no `/deploy`: `deploy.sh` warns and keeps the current
files. Once, to switch a server set up before it, copy the new `deploy.sh` by hand (the old one
does not sync):

```sh
scp .docker/deploy/deploy.sh <you>@your-vps.example:<dir>/
```

## First deployment

```sh
# 1. locally: the prod image builds (base images, assets, dependencies)
docker build --target prod -f .docker/frankenphp/Dockerfile .

# 2. a tag: the workflow builds, pushes to GHCR and runs deploy.sh
git tag v0.1.0 && git push origin v0.1.0
```

3. Check, on the VPS (`cd <dir>`) then in a browser:
   - `docker compose ps`: `app` is `healthy`;
   - `https://<domain>/health` answers `ok`;
   - a game with two browsers (one of them in private browsing);
   - `docker compose logs app`: no errors; with Litestream, `docker compose logs litestream`.

Without a correct `TRUSTED_PROXIES`, the home page answers 500 (the `__Secure-` Mercure cookie
requires Nette to see HTTPS).

`MERCURE_JWT_SECRET` lives in `app.env` (not `.env`, which only feeds compose). Without it the
container stops at start (`MERCURE_JWT_SECRET is not set`) and `docker rollout` keeps the
previous version. After editing `app.env`, recreate the container: a `restart` does not
reread it.

```sh
./deploy.sh "$(sed -n 's/^TAG=//p' .env)"
```

Hub log `Authorization error … token signature is invalid`, a few lines right after a secret
change: open tabs retry with their old token until they are reloaded.

### Upgrading from two Mercure keys

Before deploying an image that reads `MERCURE_JWT_SECRET`, replace the two lines
`MERCURE_PUBLISHER_JWT_KEY` / `MERCURE_SUBSCRIBER_JWT_KEY` of `app.env` with one:

```sh
sed -i '/^MERCURE_SUBSCRIBER_JWT_KEY=/d; s/^MERCURE_PUBLISHER_JWT_KEY=/MERCURE_JWT_SECRET=/' app.env
```

Forgotten, it is harmless: the new container does not start and the previous one keeps running.

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
