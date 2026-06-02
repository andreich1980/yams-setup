# Requirements: YAMS Migration

## Functional Requirements
1. **Single Entry Point**: All services must be defined in a single `docker-compose.yml`.
2. **Environment Management**: Use a `.env` file for all host-specific variables (UID, GID, paths, keys).
3. **Service Parity**:
    - Jellyfin (Media)
    - Audiobookshelf (Books)
    - Mealie (Food)
    - qBittorrent (Downloads)
    - Sonarr, Radarr, Bazarr, Prowlarr (Arr Stack)
    - Homepage (Dashboard)
    - Watchtower (Updates)
4. **Tailscale Architecture**:
    - **Hub Model**: A single Tailscale instance + Caddy for services compatible with subdirectory routing (Sonarr, Radarr, etc.).
    - **Sidecar Model**: Maintain distinct Tailscale sidecars for services requiring unique hostnames (Jellyfin, Audiobookshelf, Mealie) to ensure app compatibility and clean URLs.
    - Support for both local IP access and Tailscale MagicDNS.
5. **Clean Removal**:
    - Remove Portainer.
    - Remove Gluetun (VPN to be handled differently or dropped if not needed, as per user's "get rid of" instruction). *Note: User mentioned Portainer and Gluetun specifically as unused.*

## Non-Functional Requirements
1. **Zero Data Loss**: Volume mappings must be preserved exactly.
2. **Maintainability**: Remove the complex `yams` bash script.
3. **Simplicity**: Use standard `docker compose` networking where possible.

## Technical Details
- **Base Network**: A single bridge network for internal communication.
- **Tailscale Hostname**: `media.${TS_DOMAIN}` (or similar).
- **Caddy Config**: Consolidated Caddyfile with subdomain/path routing.
