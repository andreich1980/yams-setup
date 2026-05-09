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

## Setup Instructions

1. **Clone the Repository**:

   ```bash
   git clone https://github.com/your-username/yams-setup.git
   cd yams-setup
   ```

2. **Directory Preparation**:
   This project is designed to live in `/opt/media-server` with media in `/srv/media`. Create them:

   ```bash
   # Create project home
   sudo mkdir -p /opt/media-server
   sudo chown -R $USER:$USER /opt/media-server

   # Create media home
   sudo mkdir -p /srv/media/{movies,tv,downloads,audiobooks,podcasts}
   sudo chown -R $USER:$USER /srv/media
   ```

3. **Project Placement**:
   Move the contents of the cloned repository to `/opt/media-server`:

   ```bash
   cp -r . /opt/media-server/
   cd /opt/media-server
   ```

4. **Environment Configuration**:
   - Copy `.env.example` to `.env`.
   - Update `TS_DOMAIN` with your Tailscale domain (e.g., `your-name.ts.net`).
   - Update `LOCAL_IP` with your server's local IP.
   - Add your `TAILSCALE_AUTH_KEY`.
   - Update `PUID` and `PGID` to match your user (run `id` to check).

5. **Manual Service Tweaks**:
   - **Tailscale Sidecars**: Generate config files from templates and replace the domain.

     **Bash/Zsh:**

     ```bash
     export TS_DOMAIN=$(grep TS_DOMAIN .env | cut -d '=' -f2)
     for f in config/tailscale/sidecars/*.json.template; do
       sed "s/\${TS_DOMAIN}/$TS_DOMAIN/g" "$f" > "${f%.template}"
     done
     ```

     **Fish:**

     ```fish
     set TS_DOMAIN (grep TS_DOMAIN .env | cut -d '=' -f2)
     for f in config/tailscale/sidecars/*.json.template
       sed "s/\${TS_DOMAIN}/$TS_DOMAIN/g" "$f" > (string replace ".template" "" $f)
     end
     ```

   - **Base URLs**: Set "URL Base" in Sonarr/Radarr/etc. settings to match Caddy (e.g., `/sonarr`).
   - **Socket Permissions**: If SSL fails, ensure `config/tailscale/hub/tailscaled.sock` is readable by Caddy.

6. **Initialize Stack**:

   ```bash
   docker compose up -d
   ```

7. **Service Accessibility**:
   - **Local Dashboard**: `http://localhost/` or `http://<LOCAL_IP>/`
   - **Remote Access**: `https://media.<your-domain>.ts.net`

## Maintenance

- **Restart All**: `docker compose restart`
- **Check Logs**: `docker compose logs -f`
- **Git Updates**: This repo is safe to push to Git as long as `.env` is ignored.
