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

1. **Directory Preparation**:
   This project is designed to live in `/opt/media-server` with media in `/srv/media`. Create them and set permissions:

   ```bash
   # Create project home
   sudo mkdir -p /opt/media-server
   sudo chown -R $USER:$USER /opt/media-server

   # Create media home
   sudo mkdir -p /srv/media/{movies,tv,downloads,audiobooks,podcasts}
   sudo chown -R $USER:$USER /srv/media
   ```

2. **Clone the Repository**:
   Clone directly into the project directory:

   ```bash
   git clone https://github.com/andreich1980/media-server /opt/media-server
   cd /opt/media-server
   ```

3. **Environment Configuration**:
   - Copy `.env.example` to `.env`.
   - Update `TS_DOMAIN` with your Tailscale domain (e.g., `your-name.ts.net`).
   - Update `LOCAL_IP` with your server's local IP.
   - Add your `TAILSCALE_AUTH_KEY`.
   - Update `PUID` and `PGID` to match your user (run `id` to check).

4. **Manual Service Tweaks**:
   - **Configuration Templates**: Generate config files from templates (`Caddyfile`, `services.yaml`, Tailscale sidecars) and replace the domain.

     **Bash/Zsh:**

     ```bash
     export TS_DOMAIN=$(grep TS_DOMAIN .env | cut -d '=' -f2)
     # Process all .template files
     find config -name "*.template" | while read f; do
       sed "s/\${TS_DOMAIN}/$TS_DOMAIN/g" "$f" > "${f%.template}"
     done
     ```

     **Fish:**

     ```fish
     set TS_DOMAIN (grep TS_DOMAIN .env | cut -d '=' -f2)
     # Process all .template files
     for f in (find config -name "*.template")
       sed "s/\${TS_DOMAIN}/$TS_DOMAIN/g" "$f" > (string replace ".template" "" $f)
     end
     ```

   - **Base URLs**: Set "URL Base" in Sonarr/Radarr/etc. settings to match Caddy (e.g., `/sonarr`).
   - **Socket Permissions**: If SSL fails, ensure `config/tailscale/hub/tailscaled.sock` is readable by Caddy.

5. **Initialize Stack**:
   ```bash
   docker compose up -d
   ```

## Migrating Existing Data

If you are migrating from an existing server (e.g., YAMS), follow these steps to ensure your data and permissions stay intact.

1. **Stop Services**:
   - **On Remote**: Shut down your existing media server to prevent file locks.
   - **On Local**: If you already ran `docker compose up`, stop it now: `docker compose stop`.

2. **Transfer Data (Safe Approach)**:
   To avoid SSH/Sudo permission issues, sync the data to a temporary folder as your normal user first:

   ```bash
   # From your local project root:
   mkdir -p config_tmp metadata_tmp

   # Sync application data (skip Tailscale/Caddy state which are root-owned)
   rsync -qav --exclude 'tailscale' --exclude 'caddy/data' media:/opt/yams/config/ ./config_tmp/
   rsync -qav media:/opt/yams/metadata/ ./metadata_tmp/

   # Sync media (use -H to preserve hardlinks if you are seeding torrents)
   rsync -qavH media:/srv/media/ /srv/media/
   ```

3. **Merge and Fix Permissions**:
   Move the temporary data into your project and restore ownership so Docker can use it:

   ```bash
   # Move contents to real folders
   sudo cp -rv ./config_tmp/* ./config/
   sudo cp -rv ./metadata_tmp/* ./metadata/

   # Set ownership to your local user
   sudo chown -R $USER:$USER ./config ./metadata /srv/media

   # Cleanup
   rm -rf ./config_tmp ./metadata_tmp
   ```

4. **Service Tweaks**:
   For services behind the hub (Sonarr, Radarr, etc.), ensure the "URL Base" is set in the service's internal `config.xml` (e.g., `<UrlBase>/sonarr</UrlBase>`) before starting.

## Service Accessibility

- **Local Dashboard**: `http://localhost/` or `http://<LOCAL_IP>/`
- **Remote Access**: `https://media.<your-domain>.ts.net`

## Maintenance

- **Restart All**: `docker compose restart`
- **Check Logs**: `docker compose logs -f`
- **Git Updates**: This repo is safe to push to Git as long as `.env` is ignored.
