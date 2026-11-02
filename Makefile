# Façade of bin/dev. Vars: PORT=<port> PROD=1 V=-v (e.g. make up PORT=9000 PROD=1)
.DEFAULT_GOAL := help
.PHONY: help up start down restart build vendors php php-stop clear-logs clear-cache certs

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
