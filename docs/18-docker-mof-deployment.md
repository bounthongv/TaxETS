# Tax-ETS at MOF — Deployment Playbook

> **Scenario**: You're at the customer's site (Ministry of Finance,
> Vientiane). Their network is behind a Fortigate VPN, the server
> has a local IP only (no public domain), and outbound internet
> from the server is blocked.
>
> **Goal**: Get Tax-ETS running on that server, pointed at their
> existing MySQL, with zero internet access.
>
> **Time on site**: 30-60 minutes if everything is prepped.
>
> **Before you go**: read
> [`16-docker-getting-started.md`](16-docker-getting-started.md)
> and
> [`17-docker-tax-ets-implementation.md`](17-docker-tax-ets-implementation.md)
> for background.

---

## 1. The plan in one sentence

Build a Docker image on your dev machine, save it to a tarball,
copy the tarball to a USB, run an installer script on the MOF
server, answer four questions, done.

---

## 2. Before you go (prep checklist)

On your dev machine, in the Tax-ETS project folder:

- [ ] **Code is committed and clean.** `git status` shows no
      uncommitted changes in app code.
- [ ] **No secrets in the working tree.** `git status` should NOT
      show changes to `config.sys`, `config.php` (with real
      password), or `.env`.
- [ ] **Database is reachable from your dev machine.** You can
      `mysql -h ... -u ... -p` from terminal.
- [ ] **Docker Desktop is running** (`docker info` succeeds).
- [ ] **Build works locally** — run once to verify:
      ```bash
      docker compose build
      docker compose up -d
      # open http://localhost:8080
      ```

- [ ] **Generate the bundle**:
      ```bash
      bash docker/save-images.sh
      # → creates dist/INSTALL-BUNDLE.tar.gz
      ```

- [ ] **Copy the bundle to a USB stick**:
      ```bash
      cp dist/INSTALL-BUNDLE.tar.gz /Volumes/MY-USB/
      ```

- [ ] **Pack a paper printout** of this doc (you may not have
      internet at the site to read it).

---

## 3. At the MOF site — the install

### 3.1 Verify the server has Docker

```bash
docker --version
docker compose version
```

If either command fails, the server is missing Docker. Two paths:

**A. Server has internet (less common, but if so):**
```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# log out and back in for the group change to take effect
```

**B. Server has NO internet (more common):**
You need a "Docker offline install" USB. There are several ways
to do this; ask the MOF IT team — they likely have a standard
procedure for offline Docker deployment on Ubuntu.

### 3.2 Copy the bundle from USB to the server

```bash
# Plug in USB
ls /media/  # find the USB mount point
mkdir -p ~/tax-ets-install
cd ~/tax-ets-install
tar -xzf /media/USB-NAME/INSTALL-BUNDLE.tar.gz
cd bundle-contents
ls   # should show: tax-ets-image.tar  install.sh  docker-compose.yml  .env.example  README-MOF-INSTALL.md
```

### 3.3 Get the database details from MOF IT

You need to know:
- DB host (IP or hostname of the MySQL server — likely another
  machine on their LAN)
- DB port (almost always 3306)
- DB name (likely `tax_ets`)
- DB user
- DB password

Ask for a temporary account with rights to:
- `CREATE DATABASE` (first-time install) OR
- `SELECT, INSERT, UPDATE, DELETE` on existing `tax_ets` database

If the database doesn't exist yet, also load the schema:

```bash
mysql -h <host> -u <user> -p < db/schema.sql
mysql -h <host> -u <user> -p tax_ets < db/users_schema.sql
mysql -h <host> -u <user> -p tax_ets < db/server_auth_schema.sql
# ... and the other schema files in db/
```

(Or, if MOF IT prefers, hand them the `.sql` files and let them
load them — it's their server, their call.)

### 3.4 Run the installer

```bash
sudo bash install.sh
```

The script will:
1. ✓ Verify Docker is installed
2. ✓ Load the image from the tarball
3. ❓ Ask for DB credentials (have them ready)
4. ❓ Ask for BASE_URL (leave empty if served at web root)
5. ✓ Write a `.env` file (mode 600)
6. ✓ Start the container
7. ✓ Print "Installation complete!"

### 3.5 Verify

```bash
# Is the container running?
docker ps

# Are the logs clean?
docker compose logs --tail=50 web
# Look for "Apache/2 (Debian) ... configured -- resuming normal operations"
# NOT "AH00558: apache2: Could not reliably determine..."

# Can Apache serve the login page?
curl -sI http://localhost/login.php
# Expect: HTTP/1.1 200 OK
```

Open a browser on a different machine on the same LAN and visit:

```
http://<this-server-local-ip>/login.php
```

Default credentials:
- **Email:** `admin@example.com`
- **Password:** `admin123`

⚠️ **Warn the customer to change the default password immediately.**

---

## 4. Hand-off to the customer

Before you leave, walk through these with the MOF IT contact:

1. **Where the data lives**:
   - App code: inside the container (don't edit directly)
   - Import logs: `~/tax-ets-install/bundle-contents/data/logs/`
   - `.env` file: `~/tax-ets-install/bundle-contents/.env`
     (contains the DB password — keep it safe)

2. **How to manage the app** (give them this cheat sheet):

   ```bash
   cd ~/tax-ets-install/bundle-contents

   docker compose ps            # status
   docker compose logs -f web   # live logs
   docker compose restart web   # restart the app
   docker compose down          # stop the app
   docker compose up -d web     # start the app
   ```

3. **How to update the app** when you ship a new version:

   - You (the developer) run `bash docker/save-images.sh` again.
   - The new `dist/tax-ets-image.tar` overwrites the old one on
     the USB.
   - On the MOF server:

     ```bash
     cd ~/tax-ets-install/bundle-contents
     cp /media/USB/tax-ets-image.tar .
     docker load -i tax-ets-image.tar
     docker compose up -d web
     ```

   The container picks up the new image, restarts, done.

4. **Backups**:
   - The app's MySQL data is on their MySQL server — that's
     their backup policy.
   - Import logs in `data/logs/` are not backed up by default.
     Tell them to add this to their backup script if they care.

---

## 5. Troubleshooting cheat sheet

| Symptom | First check | Fix |
| --- | --- | --- |
| `docker: command not found` | Docker is not installed | Install Docker offline (ask MOF IT) |
| `tax-ets-image.tar not found` | Wrong directory | `cd bundle-contents` and try again |
| `Cannot connect to MySQL` | Network/firewall | `mysql -h <host> -u <user> -p` from the server's shell; if that fails, it's a network problem |
| `Permission denied writing .env` | Running as non-root | Use `sudo bash install.sh` |
| `Login page 500` | DB tables missing | Load the SQL files (see step 3.3) |
| `Everything 404 except /login.php` | BASE_URL wrong | Edit `.env`, set `BASE_URL=` (empty), `docker compose restart web` |
| `ImagePullBackOff` on k8s | (only relevant for k8s) | Image wasn't loaded — `docker load -i` |
| Container keeps restarting | Check `docker logs` | Usually a PHP error in `index.php` — read the trace |
| Forgot DB password | Read `.env` | `sudo cat .env` (or reset on the MySQL server) |

---

## 6. What to do if the MOF wants HTTPS

The customer is on a private LAN. HTTPS is not strictly required,
but if they want it, the cleanest way is to add a **reverse proxy
container** (Caddy or Traefik) in front of the `web` service. We
can do this in a follow-up — the existing structure supports it
without changes to the app.

For now, document the IP + port and the customer IT team can put
their existing reverse proxy in front of it.

---

## 7. When you get back to your dev machine

1. **Sanity check the customer's data**. If they gave you sample
   data, run a few reports locally to make sure the app behaves
   the same as it does at MOF.
2. **Update the `.env.example`** if you used a non-default
   configuration (different port, timezone, etc.) — that way
   the next install is one click.
3. **Tag a release** in git so the version that went to MOF is
   traceable:
   ```bash
   git tag -a v1.0-mof-2026-06-15 -m "MOF production release"
   git push --tags
   ```
4. **Save the tarball to your archive** so you can rebuild the
   MOF environment exactly, 6 months from now, if needed.
5. **Note any customer-specific changes** in a CHANGELOG entry.

---

## 8. Appendix: file layout on the MOF server

After install, the server looks like this:

```
~/tax-ets-install/
└── bundle-contents/
    ├── .env                      ← DB credentials (mode 600)
    ├── docker-compose.yml        ← service definitions
    ├── .env.example              ← template (no real values)
    ├── tax-ets-image.tar         ← the Docker image (kept for re-loads)
    ├── install.sh                ← installer (already ran)
    ├── README-MOF-INSTALL.md     ← quick reference for the customer
    └── data/
        └── logs/                 ← import logs (grows over time)
```

Docker itself stores the running container and any data the
container writes outside the volume mounts — but we don't write
anywhere else, so the only persistent state is in `~/tax-ets-install/`
and the customer's MySQL server.
