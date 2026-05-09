# Project Context: YAMS to Simple Docker Compose Migration

## Overview
Migrate the existing "Yet Another Media Server" (YAMS) setup to a clean, consolidated Docker Compose configuration. The goal is to remove bloat, simplify service management, and optimize the networking setup (Tailscale).

## Vision
A maintainable, lightweight media server stack that uses standard Docker Compose commands and a single configuration file, with centralized reverse proxying and networking.

## Core Goals
1. **Migration**: Move away from the `yams` wrapper script and split compose files.
2. **Simplification**: Eliminate unused services (Portainer, Gluetun).
3. **Consolidation**: Single `docker-compose.yml` and `.env` for all services.
4. **Networking Strategy**: Implement a "Hybrid Tailscale Architecture":
    - Sidecars for services requiring distinct hostnames (e.g., Jellyfin, Audiobookshelf, Mealie).
    - A consolidated Tailscale + Caddy hub for services that work well in subdirectories (Arr stack, Homepage).
5. **Clean Infrastructure**: Remove legacy YAMS files once the migration is successful.

## Tech Stack
- **Orchestration**: Docker Compose
- **Networking**: Tailscale (Hybrid: Hub + Sidecars)
- **Reverse Proxy**: Caddy (Consolidated for the Hub)
- **Services**: Jellyfin, qBittorrent, Sonarr, Radarr, Bazarr, Prowlarr, Audiobookshelf, Mealie, Homepage, Watchtower.

## Constraints
- Must preserve all existing data and configurations in `config/` and `metadata/`.
- Must maintain Tailscale connectivity (MagicDNS).
- Must be easy to manage via standard `docker compose` commands.
