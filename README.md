# Media Server (Simple Docker Compose)

A clean, consolidated media server stack based on Docker Compose, featuring Tailscale networking and Caddy reverse proxying.

## Features
- **Consolidated Management**: Single `docker-compose.yml` for the entire stack.
- **Hybrid Networking**: Tailscale Hub for subdirectory routing + dedicated Sidecars for apps requiring distinct hostnames.
- **Privacy-First**: Sensitive information (IPs, domains, keys) is abstracted into `.env`.
- **Automated Updates**: Integrated Watchtower for keeping containers up to date.

## Prerequisites
- Docker and Docker Compose installed.
- A Tailscale account and Auth Key.

## Directory Structure
This project is designed to live in `/opt/media-server` with media in `/srv/media`.

```bash
# Create project home
sudo mkdir -p /opt/media-server
sudo chown -R $USER:$USER /opt/media-server

# Create media home
sudo mkdir -p /srv/media/{movies,tv,downloads,audiobooks,podcasts}
sudo chown -R $USER:$USER /srv/media
```

## Setup Instructions

1. **Environment Configuration**:
   - Copy `.env.example` to `.env`.
   - Update `TS_DOMAIN` with your Tailscale domain (e.g., `your-name.ts.net`).
   - Update `LOCAL_IP` with your server's local IP.
   - Add your `TAILSCALE_AUTH_KEY`.

2. **Manual Configuration (Post-Init)**:
   - **Tailscale Sidecars**: Environment variables in `config/tailscale/sidecars/*.json` are placeholders. You **must** manually replace `${TS_DOMAIN}` with your actual tailnet domain in these files.
   - **Service Base URLs**: For services behind the hub (Sonarr, Radarr, etc.), ensure the "URL Base" is set in the service's internal settings (e.g., `/sonarr`) to match the Caddy routes.
   - **Permissions**: If Caddy fails to fetch SSL certificates, ensure the Tailscale socket at `config/tailscale/hub/tailscaled.sock` is readable by the Caddy container (default socket permissions are often restrictive).

3. **Initialize Stack**:
   ```bash
   docker compose up -d
   ```

3. **Service Accessibility**:
   - **Local Dashboard**: `http://localhost/` or `http://<LOCAL_IP>/`
   - **Remote Access**: `https://media.<your-domain>.ts.net`

## Maintenance
- **Restart All**: `docker compose restart`
- **Check Logs**: `docker compose logs -f`
- **Git Updates**: This repo is safe to push to Git as long as `.env` is ignored.
