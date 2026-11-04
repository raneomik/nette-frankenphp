#!/bin/sh
# /srv/rpsls/deploy.sh — sur le VPS
# Usage : ./deploy.sh 1.3.0        (aussi pour un rollback manuel : ./deploy.sh 1.2.0)
#         ./deploy.sh sha-8f3c2a9  (n'importe quelle image construite)
set -eu

TAG="${1:?usage: deploy.sh <version>}"
cd "$(dirname "$0")"

# n'accepte qu'un tag d'image attendu, en entier : l'argument vient d'une connexion SSH
# (1.3.0, 1.4.0-rc.1, sha-8f3c2a9 ou latest ; un "v" initial est retiré)
TAG="${TAG#v}"
if ! printf '%s' "$TAG" | grep -Eqx '[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.]+)?|sha-[0-9a-f]{7,40}|latest'; then
  echo "invalid tag: $TAG" >&2
  exit 1
fi

docker rollout --help >/dev/null 2>&1 || { echo "docker rollout plugin is missing" >&2; exit 1; }
grep -q '^HOST=.' .env 2>/dev/null || { echo "HOST=<domaine> is missing from .env" >&2; exit 1; }

previous=$(sed -n 's/^TAG=//p' .env 2>/dev/null || true)
echo "→ deploying $TAG (currently: ${previous:-none})"

# une variable du shell l'emporte sur le .env lu par compose
export TAG
docker compose pull app

# réplication de la base, si configurée (sans effet s'il tourne déjà avec la même config)
if [ -f litestream.env ]; then
  docker compose --profile backup up -d litestream
else
  echo "⚠ no litestream.env: the database is not backed up" >&2
fi

# point de restauration : l'état de la base juste avant que cette version ne la migre
# (litestream restore -timestamp …, cf. README)
restore_point=$(date -u +%Y-%m-%dT%H:%M:%SZ)

if [ -z "$(docker compose ps -q app)" ]; then
  # premier déploiement : rien à remplacer
  docker compose up -d --wait app
else
  # démarre le nouveau conteneur à côté de l'ancien, attend son healthcheck,
  # puis retire l'ancien ; en cas d'échec, l'ancien reste en place
  docker rollout -t 120 app
fi

# on n'enregistre le tag qu'une fois le déploiement réussi ; les autres variables
# du .env (HOST...) sont conservées
{ grep -v '^TAG=' .env; printf 'TAG=%s\n' "$TAG"; } > .env.tmp && mv .env.tmp .env
printf '%s %s (previously %s)\n' "$restore_point" "$TAG" "${previous:-none}" >> deploys.log
echo "✓ $TAG is live (database restore point before it: $restore_point)"

# images inutilisées de plus de 7 jours (les plus récentes restent dispo pour un rollback)
docker image prune -af --filter "until=168h" >/dev/null
