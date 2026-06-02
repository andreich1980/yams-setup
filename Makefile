.DEFAULT_GOAL := help

.PHONY: help
help:
	@echo "Usage: make [command]"
	@echo ""
	@echo "Commands:"
	@echo "  start       - Start all services in the background (docker compose up -d)"
	@echo "  stop        - Stop and remove all services (docker compose down)"
	@echo "  vpn-update  - Force the VPN config builder to fetch a new node and restart glueless"
	@echo "  help        - Show this help message"

.PHONY: start
start:
	docker compose up -d

.PHONY: stop
stop:
	docker compose down

.PHONY: vpn-update
vpn-update:
	docker exec glueless-config-builder php make-config.php
