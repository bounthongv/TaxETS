# Docker + GitHub + CI/CD Workflow

## 1. Architecture Overview

```
                    ┌──────────────────────────────────────────────────┐
                    │                 GitHub                           │
                    │   https://github.com/bounthongv/TaxETS          │
                    │         (source of truth)                        │
                    └──────────┬──────────────┬───────────────────────┘
                               │              │
                    git push   │    git push  │
                               │              │
               ┌───────────────▼──┐     ┌─────▼───────────────────┐
               │  LOCAL           │     │  SERVER (Ubuntu)        │
               │  Docker Desktop  │     │  apis.com.la            │
               │  Windows         │     │                          │
               │                  │     │  ┌──────────────────┐   │
               │  tax-ets-web     │     │  │ Apache (port 443) │   │
               │  + tax-ets-db    │     │  │ proxy -> Docker   │   │
               │  (Docker MySQL)  │     │  └────────┬─────────┘   │
               │                  │     │           │              │
               │  URL:            │     │  ┌────────▼─────────┐   │
               │  tax-ets.local   │     │  │ tax-ets-web      │   │
               │                  │     │  │ (port 127.0.0.1  │   │
               │                  │     │  │      :5000)      │   │
               └──────────────────┘     │  └──────────────────┘   │
                                        │                          │
                                        │  MySQL: system MySQL     │
                                        │  phpMyAdmin: works       │
                                        │                          │
                                        │  URL:                    │
                                        │  tax-ets.apis.com.la    │
                                        └──────────────────────────┘
```

## 2. Docker Files (in GitHub repo)

| File | Purpose |
|------|---------|
| **`Dockerfile`** | Defines the PHP 8.2 + Apache container. Installs extensions (pdo_mysql, gd, zip, intl, opcache), Composer dependencies, and configures Apache. |
| **`docker-compose.yml`** | Orchestrates services. `web` = PHP app. `db` = MySQL 8.0 (only with `--profile with-db`). Networks, volumes, and health checks. |
| **`.dockerignore`** | Excludes node_modules, .git, .env from the Docker build context. |
| **`.env.docker`** | Environment config for **Docker MySQL** (`DB_HOST=db`). Use this when running with `--profile with-db`. |
| **`.env.xampp`** | Environment config for **XAMPP / external MySQL** (`DB_HOST=host.docker.internal`). Use when connecting to a MySQL running outside Docker. |
| **`docker-init/01-dump.sql`** | MySQL schema + data dump. Seeded into the Docker MySQL container on first start. |
| **`docker/php-overrides.ini`** | PHP config overrides (upload size, timeouts, etc.) applied inside the container. |
| **`docker/vhost.conf`** | Apache virtual host config for the Docker container. |

### Key: `.env` file switching

The project has **two environment variants** for easy switching:

```bash
# Docker MySQL (bundled database)
cp .env.docker .env
docker compose --profile with-db up -d

# External MySQL (XAMPP or server MySQL)
cp .env.xampp .env
docker compose up -d
```

---

## 3. How Code Flows (CI/CD)

```
YOU EDIT CODE
      │
      ▼
  LOCAL TEST
  https://tax-ets.local/
  (Docker on Windows)
      │
      ▼
  git add + git commit + git push
      │
      ▼
  GITHUB
  https://github.com/bounthongv/TaxETS
  (source of truth)
      │
      ▼
  SERVER UPDATE
  ssh apis.com.la
  cd /opt/tax-ets-docker
  git pull
  docker compose down && docker compose up -d
  docker exec tax-ets-web composer install --no-dev
```

---

## 4. How to Deploy a Change

### Step-by-step:

```bash
# 1. Edit code on Windows (local)
#    Files at: D:\Tax-ETS\
#    Changes appear instantly at https://tax-ets.local/

# 2. Test locally

# 3. Commit and push to GitHub
cd D:\Tax-ETS
git add .
git commit -m "Description of what you changed"
git push

# 4. SSH into server
ssh apis.com.la

# 5. Pull and restart
cd /opt/tax-ets-docker
git pull
docker compose down && docker compose up -d
docker exec tax-ets-web composer install --no-dev

# 6. Verify at https://tax-ets.apis.com.la/
```

---

## 5. Database: Where Is My Data?

### Option A: Server MySQL (current production)

The database stays in the **server's MySQL** — same as before Docker. Docker only runs the PHP code.

- **phpMyAdmin**: works as before
- **Backup**: `mysqldump -u admin -p tax_ets > backup.sql`
- **Restore**: `mysql -u admin -p tax_ets < backup.sql`

### Option B: Docker MySQL (local development)

When you use `--profile with-db` locally:

- **Data location**: Docker volume `tax-ets-mysql-data`
- **phpMyAdmin**: `docker run -d --name pma --network tax-ets-net -e PMA_HOST=tax-ets-db -p 127.0.0.1:8081:80 phpmyadmin` then visit `http://localhost:8081`
- **Backup**: `docker exec tax-ets-db mysqldump -u root -p tax_ets > backup.sql`
- **Stop + keep data**: `docker compose down` (volume stays)
- **Wipe + fresh start**: `docker compose down -v` (deletes volume too)

---

## 6. Key Files on Each Machine

| Machine | Code Location | Docker Config |
|---------|--------------|---------------|
| **Local (Windows)** | `D:\Tax-ETS\` | `docker-compose.yml` in project root. Volume mount: `./` → `/var/www/html` |
| **Server (Ubuntu)** | `/opt/tax-ets-docker/` | Same `docker-compose.yml`. Volume mount: `./` → `/var/www/html` |

Both use the same repo from GitHub — code stays in sync via `git pull`.

---

## 7. Quick Verification

Check if Docker is serving:

```bash
# Look at the Server header
curl -sI https://tax-ets.apis.com.la/login.php | grep -i server
# If it shows: Apache/2.4.67 (Debian) → Docker
# If it shows: Apache/2.4.58 (Ubuntu) → old Apache

# Check container is running
docker ps --filter name=tax-ets
```

---

## 8. File Permission Rules

The Docker container runs as `www-data` (UID 33). Any directory that PHP needs to **write to** must be writable by `www-data`:

```bash
# On the server (run once after git pull if needed)
cd /opt/tax-ets-docker
chgrp -R www-data uploads/ backups/ data/
chmod -R g+w uploads/ backups/ data/
```

Directories that need write access:
- `uploads/users/` — user profile photos
- `backups/` — system backups
- `data/logs/` — import logs
