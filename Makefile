.DEFAULT_GOAL := help

# Colors
GREEN  := \033[32m
YELLOW := \033[33m
RESET  := \033[0m

.PHONY: help
help:
	@printf "Usage: make $(YELLOW)[command]$(RESET)\n\n"
	@printf "Commands:\n"
	@printf "  $(YELLOW)start$(RESET)       - Start all services in the background (docker compose up -d)\n"
	@printf "  $(YELLOW)stop$(RESET)        - Stop and remove all services (docker compose down)\n"
	@printf "  $(YELLOW)restart$(RESET)     - Recreate and restart all services\n"
	@printf "  $(YELLOW)vpn-update$(RESET)  - Force the VPN config builder to fetch a new node and restart glueless\n"
	@printf "  $(YELLOW)help$(RESET)        - Show this help message\n"

.PHONY: start
start:
	@printf "$(GREEN)Starting all services...$(RESET)\n"
	docker compose up -d

.PHONY: stop
stop:
	@printf "$(GREEN)Stopping all services...$(RESET)\n"
	docker compose down

.PHONY: restart
restart:
	@printf "$(GREEN)Recreating and restarting all services...$(RESET)\n"
	docker compose down
	docker compose up -d

.PHONY: vpn-update
vpn-update:
	@printf "$(GREEN)Forcing VPN config update...$(RESET)\n"
	docker exec glueless-config-builder php make-config.php
