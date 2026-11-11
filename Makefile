# Facade of bin/dev. Vars: PORT=<port> PROD=1 V=-v (e.g. make up PORT=9000 PROD=1)
.DEFAULT_GOAL := help
.PHONY: help up start down restart build vendors php php-stop clear-logs clear-cache migrate migration rollback certs pwa-icon monitor monitor-down

help: ## List available targets
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  \033[32m%-12s\033[0m %s\n", $$1, $$2}'

up: ## FrankenPHP in background
	bin/dev -f -d $(V)
start: ## FrankenPHP in foreground
	bin/dev -f $(V)
down: ## Stop FrankenPHP
	bin/dev -f stop $(V)
restart: ## Restart FrankenPHP
	bin/dev -f restart $(V)
build: ## Rebuild docker img & go background
	bin/dev -f -b -d $(V)
clean-dangling: ## Cleanup docker dangling elements
	bin/dev --clean-docker
vendors: ## Force dependencies update & go background
	bin/dev -f -u -d $(V)

certs: ## Install docker ssl certificates
	bin/certs install --win

php: ## Launch in php integrated server
	bin/dev --php $(V)
php-stop: ## Stop php server
	bin/dev --php stop $(V)

clear-logs: ## clear logs
	bin/dev --clear-logs
clear-cache: ## clear cache
	bin/dev --clear-cache

migrate: ## Apply pending migrations (db/migrations)
	docker compose exec app dbmate up
migration: ## New migration file: make migration name=create_game
	docker compose exec app dbmate new $(name)
rollback: ## Roll back the last migration (dev)
	docker compose exec app dbmate rollback

# opaque background (iOS paints transparency black) + logo at 64% (maskable safe zone);
# linked by the manifest as img:pwa-icon (config/common.neon), versioned by Nette Assets
pwa-icon: ## Regenerate assets/img/pwa-icon.svg (PWA manifest) from assets/img/app.svg
	@inner=$$(sed -e 's/^<svg[^>]*>//' -e 's#</svg>$$##' assets/img/app.svg | tr -d '\n'); \
	printf '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="512" height="512" viewBox="0 0 300 300"><rect width="300" height="300" fill="#11161c"/><g transform="translate(150 150) scale(0.64) translate(-884 -270.86458587646484)">%s</g></svg>' "$$inner" > assets/img/pwa-icon.svg

monitor: ## Start Prometheus, Grafana (:3000, admin/admin) & Dozzle (:8888)
	docker compose --profile monitor up -d prometheus grafana dozzle
monitor-down: ## Stop monitor services
	docker compose --profile monitor stop prometheus grafana dozzle

ecs: ## Run easy coding standards
	@vendor/bin/ecs --fix
rector: ## Run rector
	@vendor/bin/rector
stan: ## Run phpstan
	@vendor/bin/phpstan
ci: ecs rector stan

#Deploy
tag:
	git tag $(tag) && git push origin $(tag)
