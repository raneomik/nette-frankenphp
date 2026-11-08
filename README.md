# Nette × FrankenPHP × Mercure

A [Nette](https://nette.org) application running in [FrankenPHP](https://frankenphp.dev) **worker mode**,
with real-time updates pushed through the [Mercure](https://mercure.rocks) hub built into FrankenPHP
(via [raneomik/nette-mercure](https://github.com/raneomik/nette-mercure)).

The demo app is a multiplayer **Rock · Paper · Scissors · Lizard · Spock**: a live lobby of waiting rooms
and two-player duels, updated as [Turbo Streams](https://turbo.hotwired.dev/handbook/streams) rendered from Latte templates.

## What's inside

- **One entry point, two runners**: FrankenPHP worker mode, or the plain Nette request cycle
  on the PHP built-in server (`APP_RUNNER`).
- **Mercure**: broadcasting from presenters, per-topic subscriber JWTs, a TypeScript Turbo Streams client built with Vite.
- **SQLite** shared by all worker threads (a file in WAL mode: `:memory:` would give each thread its own empty database),
  migrations with [dbmate](https://github.com/amacneil/dbmate).
- **Dev**: hot reload, Xdebug.
- **Monitoring** (optional): Prometheus, Grafana, Dozzle.
- **Deployment**: a git tag builds the prod image, pushes it to GHCR and deploys it on a VPS
  behind caddy-docker-proxy, with Litestream backups. See [.docker/deploy/README.md](.docker/deploy/README.md).

## Getting started

Requires Docker with Compose (PHP 8.4+, Composer and Node only for the built-in server mode).

```bash
git clone https://github.com/raneomik/nette-frankenphp
cd nette-frankenphp
make certs   # once, from WSL: trust the local Caddy CA in Windows (bin/certs -h for other targets)
make up      # FrankenPHP in the background (dev: hot reload, Xdebug, debug mode)
```

Then open <https://localhost:448> (ports from `.env`). The game is at `/rock-paper-scissors`:
open it in two browsers to play against yourself.

| Target | |
|---|---|
| `make up` / `start` / `down` / `restart` | FrankenPHP in the background / foreground / stop / restart |
| `make build` | Rebuild the image |
| `make php` | Same app on the PHP built-in server (`http://localhost:8000`) |
| `make migrate` / `migration name=…` / `rollback` | dbmate migrations|
| `make certs` | Move FrankenPHP tls certificates & load them in navigator (wsl) |
| `make monitor` | Grafana on `:3000` (admin/admin), Dozzle on `:8888` |

`make` lists every target; append `PROD=1` to disable debug mode, `V=-v` to print the executed commands.

## License

MIT, see [LICENSE](LICENSE).
