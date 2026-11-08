#!/bin/sh
# deploy.sh — on the VPS, in the project folder (VPS_PROJECT_DIR); works from any folder
# Usage: ./deploy.sh 1.3.0        (also for a manual rollback: ./deploy.sh 1.2.0)
#        ./deploy.sh sha-8f3c2a9  (any built image)
#        ./deploy.sh 1.3.0@sha256:… (what the workflow sends: that exact image, even if the
#                                    tag is pushed again later)
set -eu

# argument, or through the forced command of the GitHub key (authorized_keys command="…"):
# what the client asked for is in SSH_ORIGINAL_COMMAND ("<dir>/deploy.sh 1.3.0" or "1.3.0"),
# its last word is the version
SSH_ORIGINAL_COMMAND="${SSH_ORIGINAL_COMMAND:-}" # unset outside SSH, and set -u
TAG="${1:-${SSH_ORIGINAL_COMMAND##* }}"
TAG="${TAG:?usage: deploy.sh <version>}"
cd "$(dirname "$0")"

# only accepts an expected image tag, in full: the argument comes from an SSH connection
# (1.3.0, 1.4.0-rc.1, sha-8f3c2a9 or latest, optionally @sha256:<digest>; a leading "v" is stripped)
TAG="${TAG#v}"
if ! printf '%s' "$TAG" | grep -Eqx '([0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.]+)?|sha-[0-9a-f]{7,40}|latest)(@sha256:[0-9a-f]{64})?'; then
  echo "invalid tag: $TAG" >&2
  exit 1
fi

docker rollout --help >/dev/null 2>&1 || { echo "docker rollout plugin is missing" >&2; exit 1; }
grep -q '^HOST=.' .env 2>/dev/null || { echo "HOST=<domain> is missing from .env" >&2; exit 1; }

previous=$(sed -n 's/^TAG=//p' .env 2>/dev/null || true)
echo "→ deploying $TAG (currently: ${previous:-none})"

# a shell variable takes precedence over the .env read by compose
export TAG
docker compose pull app

# Deploy files of the deployed version (compose.yaml, this script, litestream.yml, *.example):
# shipped in the image under /deploy and installed here, so that the server always matches what
# runs, rollbacks included, with no other SSH access (the GitHub key can only run this script).
# Secrets (.env, app.env, litestream.env) are never touched.
if [ -z "${DEPLOY_FILES_SYNCED:-}" ]; then
  image=$(docker compose config --images app | head -n 1)
  files=$(mktemp -d)
  container=$(docker create "$image" true)
  if docker cp "$container:/deploy/." "$files" 2>/dev/null; then
    updated=""
    for file in compose.yaml litestream.yml app.env.example litestream.env.example deploy.sh; do
      [ -f "$files/$file" ] || continue
      cmp -s "$files/$file" "$file" 2>/dev/null && continue
      cp "$files/$file" "$file.new" && mv "$file.new" "$file" # atomic: this script may be the one replaced
      updated="$updated $file"
    done
    chmod +x deploy.sh
    echo "  deploy files from $image: ${updated:+updated$updated}${updated:-unchanged}"
  else
    echo "⚠ $image ships no deploy files: the current ones are kept" >&2
    updated=""
  fi
  docker rm "$container" >/dev/null
  rm -rf "$files"

  # a new version of this script: run it instead, for this same deployment
  case "$updated" in *deploy.sh*) DEPLOY_FILES_SYNCED=1 exec ./deploy.sh "$TAG" ;; esac
fi

# database replication, if configured (no effect if it already runs with the same config)
if [ -f litestream.env ]; then
  # litestream reads litestream.yml at start only: recreate it when that file changed
  config_sum=$(cksum < litestream.yml)
  if [ "$config_sum" = "$(cat .litestream.sum 2>/dev/null)" ]; then
    docker compose --profile backup up -d litestream
  else
    docker compose --profile backup up -d --force-recreate litestream
  fi
  printf '%s\n' "$config_sum" > .litestream.sum
else
  echo "⚠ no litestream.env: the database is not backed up" >&2
fi

# restore point: the database state just before this version migrates it
# (litestream restore -timestamp …, see README)
restore_point=$(date -u +%Y-%m-%dT%H:%M:%SZ)

if [ -z "$(docker compose ps -q app)" ]; then
  # first deployment: nothing to replace
  docker compose up -d --wait app
else
  # starts the new container next to the old one, waits for its healthcheck,
  # then removes the old one; on failure, the old one stays in place
  docker rollout -t 120 app
fi

# the tag is only recorded once the deployment succeeded; the other variables
# of the .env (HOST...) are kept
{ grep -v '^TAG=' .env; printf 'TAG=%s\n' "$TAG"; } > .env.tmp && mv .env.tmp .env
printf '%s %s (previously %s)\n' "$restore_point" "$TAG" "${previous:-none}" >> deploys.log
echo "✓ $TAG is live (database restore point before it: $restore_point)"

# unused images older than 7 days (the more recent ones stay available for a rollback)
docker image prune -af --filter "until=168h" >/dev/null
