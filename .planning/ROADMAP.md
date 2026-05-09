# Roadmap: YAMS to Simple Docker Compose

## Phase 1: Preparation & Base Configuration (Current)
- [ ] Create `.env` from current environment variables.
- [ ] Draft the new consolidated `docker-compose.yml`.
- [ ] Draft the new `Caddyfile` for consolidated routing.

## Phase 2: Core Migration
- [ ] Stop all YAMS services (`yams stop`).
- [ ] Backup current configuration files.
- [ ] Bring up the new `docker-compose.yml` stack.
- [ ] Verify internal service connectivity.

## Phase 3: Tailscale & Networking Integration
- [ ] Implement the consolidated Tailscale Hub + Caddy.
- [ ] Port the sidecar Tailscale configurations for Jellyfin, Audiobookshelf, and Mealie.
- [ ] Verify HTTPS certificates for all hostnames (Hub and Sidecars).
- [ ] Transition Homepage to the new hybrid routing scheme.

## Phase 4: Final Cleanup
- [ ] Verify all services (Jellyfin, Books, Food) are fully functional.
- [ ] Delete `yams` script.
- [ ] Delete `docker-compose.yaml` and `docker-compose.custom.yaml`.
- [ ] Remove legacy `.yams` hidden files/folders if any.

## Phase 5: Optimization
- [ ] Fine-tune Homepage widgets.
- [ ] Ensure Watchtower is correctly monitoring the new stack.
