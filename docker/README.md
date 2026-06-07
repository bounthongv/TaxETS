# Tax-ETS — MOF Customer Deployment Bundle

This folder contains everything you need to install Tax-ETS on the
MOF Laos server (behind Fortigate VPN, no internet access).

## What's in this folder

| File | Purpose |
| --- | --- |
| `install.sh` | The one-shot installer. Run this on the MOF server. |
| `save-images.sh` | Build the Docker image and package it for USB. Run this on the developer machine. |
| `README.md` | This file. |

## Step 1 — Build the bundle on the developer's machine

On your Windows / Mac / Linux machine (with internet):

```bash
cd /path/to/tax-ets
bash docker/save-images.sh
```

This creates:
- `dist/tax-ets-image.tar` — the Docker image
- `dist/INSTALL-BUNDLE.tar.gz` — full bundle: image + installer

## Step 2 — Transfer to the MOF server

1. Copy `dist/INSTALL-BUNDLE.tar.gz` to a USB stick.
2. Bring the USB to the MOF server room.
3. Plug in the USB, open a terminal.

## Step 3 — Install on the MOF server

Prerequisites (one-time setup):
- Linux (Ubuntu 22.04+ recommended)
- Docker Engine installed (`docker --version` should print something)
- Docker Compose plugin installed (`docker compose version`)

Then:

```bash
# 1. Copy the bundle from USB to the server
mkdir -p ~/tax-ets-install
cd ~/tax-ets-install
tar -xzf /media/usb/INSTALL-BUNDLE.tar.gz   # adjust USB path
cd bundle-contents

# 2. Run the installer
sudo bash install.sh
```

The installer will ask you for:
- DB host (IP or hostname of the MOF's MySQL server)
- DB port (usually 3306)
- DB name, user, password
- Public base URL (leave empty if served at web root)

## Step 4 — Verify

After install:

```bash
# Check the container is up
docker ps

# See the logs
docker compose logs -f web

# Open the UI in a browser
# (use the server's local IP, e.g. http://192.168.x.x/login.php)
```

## Common operations

| Action | Command |
| --- | --- |
| View logs | `docker compose logs -f web` |
| Restart app | `docker compose restart web` |
| Stop app | `docker compose down` |
| Start app | `docker compose up -d web` |
| Update app | `docker load -i tax-ets-image.tar.new && docker compose up -d web` |

## Where data lives

- **Application code**: inside the Docker container (`/var/www/html`)
- **Import logs**: on the host, at `./data/logs/` (mounted into the container)
- **MySQL data**: stays on the customer's MySQL server (we don't run MySQL in Docker for MOF)

## Troubleshooting

**"Cannot connect to MySQL"**
- The MOF's MySQL server must be reachable from this server on port 3306.
- Test: `mysql -h <db_host> -u <db_user> -p`
- The container's `web` reaches MySQL via the host's network namespace, so use
  the same hostname/IP the OS uses.

**"Page shows 500 Internal Server Error"**
- Check logs: `docker compose logs web | tail -50`
- Most common cause: missing database tables. Run the SQL in `db/` on the MOF MySQL.

**"Login page works but everything else 404s"**
- BASE_URL is wrong. Edit `.env`, set `BASE_URL=` (empty) and restart: `docker compose restart web`.

**"Image tax-ets:latest not found"**
- The image wasn't loaded. Re-run: `docker load -i tax-ets-image.tar`
