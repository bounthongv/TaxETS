# How We Dockerized Tax-ETS — A Walkthrough

> **Who this is for**: you, in three months, wondering "why did I
> write it this way?"
>
> **Companion doc**: read
> [`16-docker-getting-started.md`](16-docker-getting-started.md)
> first if you are new to Docker.
>
> **Time to read**: ~15 minutes.

This document explains the design decisions behind the Tax-ETS
Docker setup: what each file does, why we wrote it that way, and
what trade-offs we made. It is paired with the file itself — open
both side by side.

---

## 1. The big picture

```
┌─────────────────────────────────────────────────────────────┐
│  HOST (your machine or the MOF server)                      │
│                                                             │
│  ┌────────────────────────────────┐                         │
│  │  Container: tax-ets-web        │                         │
│  │                                │                         │
│  │  - PHP 8.2 + Apache            │                         │
│  │  - /var/www/html  ← app code   │                         │
│  │  - /var/www/html/data/logs ←───┼──┐                      │
│  │                                │  │  (volume mount)       │
│  └────────────────────────────────┘  │                      │
│                                      │                      │
│  ./data/logs/  ◄─────────────────────┘                      │
│                                                             │
│  (optionally)                                               │
│  ┌────────────────────────────────┐                         │
│  │  Container: tax-ets-db         │                         │
│  │  - MySQL 8.0                   │                         │
│  │  - /var/lib/mysql  ◄── volume  │                         │
│  │  - /docker-entrypoint-initdb.d ◄── ./db/*.sql            │
│  └────────────────────────────────┘                         │
└─────────────────────────────────────────────────────────────┘
```

Two services, opt-in MySQL, persistent logs, no `docker rm` data
loss.

---

## 2. Files we added (and what each one is for)

| File | Purpose |
| --- | --- |
| `Dockerfile` | Recipe to build the `tax-ets:latest` image |
| `docker-compose.yml` | Recipe to run the image (and optional MySQL) as a service |
| `.dockerignore` | Tells Docker what NOT to copy into the build (secrets, vendor, etc.) |
| `.env.example` | Template for the env-var file (you copy it to `.env`) |
| `config.php` | Now reads env vars; falls back to old `config.sys` for XAMPP users |
| `docker/install.sh` | One-shot installer for the MOF server (no internet) |
| `docker/save-images.sh` | Packages the image for USB transfer (run on the dev's machine) |
| `docker/README.md` | Quick reference for the MOF deployment |
| `docs/16-…md`, `17-…md`, `18-…md` | You are reading them |

---

## 3. `Dockerfile` — line by line

### 3.1 The base image

```dockerfile
FROM php:8.2-apache
```

This is the official PHP image maintained by the Docker team. It
comes with:

- PHP 8.2 (the version we use locally)
- Apache 2.4
- Debian Bookworm (Linux)
- `docker-php-ext-install` (a helper script we'll use in a moment)

Why not `php:8.2` (without Apache)? We'd then have to install
Apache ourselves. The `-apache` variant is the standard for
"PHP web app I want to deploy" use cases.

Why not a custom base image (e.g. `php:8.2-fpm-alpine`)? Alpine
is smaller but uses musl instead of glibc, and some PHP extensions
take hours to make work on Alpine. The slim Debian-based image is
the well-trodden path for PHP web apps.

### 3.2 System dependencies

```dockerfile
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        unzip git default-mysql-client nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd zip intl opcache
```

Each package has a reason:

| Package | Why |
| --- | --- |
| `libicu-dev` | Build dep for the `intl` PHP extension (currency / number formatting) |
| `libzip-dev` | Build dep for the `zip` extension (PhpSpreadsheet reads .xlsx, which is a zip) |
| `libpng-dev libjpeg-dev libfreetype6-dev` | Build deps for `gd` (image manipulation in PhpSpreadsheet) |
| `unzip` | Composer extracts archives, and we sometimes need to inspect .xlsx by hand |
| `git` | Composer uses git for some packages; also handy for diagnostics |
| `default-mysql-client` | Gives us the `mysql` CLI inside the container for debugging |
| `nano` | Friendlier than `vi` for editing config files in a pinch |

The `docker-php-ext-install` line compiles and enables PHP
extensions. `-j"$(nproc)"` parallelizes the build (uses all CPU
cores).

We then `apt-get clean && rm -rf /var/lib/apt/lists/*` to keep the
image small.

### 3.3 Composer

```dockerfile
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
```

This is a **multi-stage copy**: we pull the Composer binary from
the official `composer:2` image, but we don't keep any of its
filesystem. This gives us a current Composer without a 200 MB
download.

### 3.4 Apache config

```dockerfile
RUN a2enmod rewrite headers
```

- `rewrite` — for clean URLs (we don't use them now, but might)
- `headers` — for security headers (X-Frame-Options etc.)

### 3.5 PHP config

```dockerfile
RUN { \
        echo 'upload_max_filesize = 50M'; \
        echo 'post_max_size = 50M'; \
        ...
    } > /usr/local/etc/php/conf.d/tax-ets.ini
```

We write a single `.ini` file that PHP auto-loads on startup. This
is the standard way to override `php.ini` defaults inside a Docker
container. The values we set:

- `upload_max_filesize = 50M` — allow big Excel files (default 2M)
- `post_max_size = 50M` — must be ≥ upload_max_filesize
- `memory_limit = 256M` — PhpSpreadsheet is memory-hungry on big sheets
- `max_execution_time = 300` — 5 min for big import jobs
- `date.timezone = Asia/Vientiane` — because we live here
- `opcache.*` — keep the compiled PHP bytecode in memory between requests

### 3.6 Application code

```dockerfile
WORKDIR /var/www/html
COPY composer.json ./
COPY composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
```

**The order matters.** Docker caches each step. By copying
`composer.json` first and running `composer install` *before*
copying the rest of the code, we get:

- First build: long (downloads + compiles all packages)
- Subsequent builds where only PHP code changed: **fast** — Composer
  step is cached, only the final `COPY . .` runs.

`--no-dev` skips dev-only packages (phpunit, etc.). For Tax-ETS we
don't have any, but it's good hygiene.

`composer.lock*` is the wildcard copy — `composer.lock` may not
exist if you've never run `composer install` locally. If it
exists, we use it (pinning exact versions); if not, the fallback
`composer install` on the next line generates a fresh one.

### 3.7 Writable directories

```dockerfile
RUN mkdir -p data/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 data/logs
```

- `data/logs/` — the only place the app writes files (import logs)
- `chown www-data` — Apache runs as `www-data` inside the container
- `chmod 775` on `data/logs` — the Apache user needs to write here

### 3.8 Healthcheck

```dockerfile
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/login.php > /dev/null || exit 1
```

Every 30 seconds, Docker curls `/login.php`. If it returns 200
OK, the container is `healthy`. If it fails 3 times in a row, it's
`unhealthy`. This makes `docker ps` show the health status and
lets `docker compose` know when to start dependent services.

### 3.9 `EXPOSE` and `CMD`

```dockerfile
EXPOSE 80
CMD ["apache2-foreground"]
```

`EXPOSE 80` is documentation — it doesn't actually publish the
port. Port publishing happens in `docker-compose.yml` with the
`ports:` block.

`CMD ["apache2-foreground"]` runs Apache in the foreground (so the
container stays alive). The default CMD of the `php:8.2-apache`
image is the same, so technically we could omit it; we leave it
explicit for clarity.

---

## 4. `docker-compose.yml` — the runbook

### 4.1 The web service

```yaml
web:
  build:
    context: .
    dockerfile: Dockerfile
  image: tax-ets:latest
  container_name: tax-ets-web
  restart: unless-stopped
  ports:
    - "8080:80"
  environment:
    DB_HOST: ${DB_HOST:-db}
    ...
  volumes:
    - ./data/logs:/var/www/html/data/logs
```

- `build:` — Compose will build the image from `./Dockerfile` if
  it doesn't exist locally.
- `image: tax-ets:latest` — names the built image so we can refer
  to it later (`docker save tax-ets:latest`).
- `restart: unless-stopped` — if the server reboots, the container
  starts back up.
- `ports: "8080:80"` — host port 8080 maps to container port 80.
  Visit `http://localhost:8080` in your browser.
- `environment:` — read from `.env` with defaults (the `:-default`
  syntax). For example `${DB_HOST:-db}` says "use DB_HOST from .env
  if set, else use 'db' (which works when MySQL is also in Docker)."
- `volumes:` — `./data/logs` on the host is mounted into the
  container. Survives `docker compose down`.

### 4.2 The optional MySQL service (the magic of `profiles`)

```yaml
db:
  image: mysql:8.0
  profiles: ["with-db"]
  ...
```

The `profiles: ["with-db"]` line is the clever part. **By default,
this service does not start.** It only starts when the user passes
the flag:

```bash
docker compose --profile with-db up -d
```

This lets one Compose file support both scenarios:

- "I have my own MySQL — just run the app" → `docker compose up -d`
- "I want a self-contained stack" → `docker compose --profile with-db up -d`

We also bind MySQL's host port to `3307` (not 3306) so it doesn't
clash with XAMPP's MySQL if you're running both.

### 4.3 Volumes and networks

```yaml
volumes:
  mysql_data:
    name: tax-ets-mysql-data
networks:
  tax-ets-net:
    name: tax-ets-net
    driver: bridge
```

- `mysql_data` is a **named volume** — Docker stores it somewhere
  on the host (typically `/var/lib/docker/volumes/`) and our
  MySQL container's data lives there. Survives `docker compose
  down`.
- `tax-ets-net` is a user-defined bridge network. Both containers
  attach to it. The PHP container can reach MySQL by hostname
  `db` because Docker's internal DNS resolves service names on
  the same network.

---

## 5. `config.php` — env-aware, backwards-compatible

```php
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
```

The pattern `defined(X) || define(X, $val)` means:
- If something earlier already defined `X` (e.g. `config.sys` for
  the XAMPP user), keep it.
- Otherwise define `X` from the environment variable, falling
  back to `'localhost'`.

This gives us three tiers of configuration:

1. **Real env vars** (Docker) — highest priority
2. **`config.sys`** (XAMPP / bare metal) — legacy, still works
3. **Hard-coded defaults** — last-resort fallback for notebook
   testing

The old behavior is 100% preserved. The new behavior is additive.

---

## 6. The MOF install bundle

The `docker/install.sh` script on the MOF server:

1. Checks Docker is installed.
2. Loads `tax-ets-image.tar` (if not already loaded).
3. Asks for DB credentials.
4. Writes a `.env` file (mode `600` — only root can read).
5. Runs `docker compose up -d web`.

The `docker/save-images.sh` script on the developer's machine:

1. Builds the image.
2. Saves it to `dist/tax-ets-image.tar`.
3. Bundles the image + install script + compose + env template
   into `dist/INSTALL-BUNDLE.tar.gz`.
4. Prints "copy this to a USB stick."

Why a script and not a "just follow these steps" doc? Because
when you're on the MOF site with limited time, network down,
people watching — you want zero decisions. The script asks the
necessary questions, validates the answers, and gives a clear
"✓ done" or "✗ failed: reason" output.

---

## 7. Trade-offs we accepted

| Decision | Trade-off |
| --- | --- |
| We didn't rewrite the app to use a framework | Existing 162 pages stay as-is; no big refactor needed |
| We didn't containerize the schema bootstrap into a custom image | The `db/*.sql` files are mounted into MySQL's official entrypoint — works fine for first boot |
| We kept the legacy `config.sys` path | Zero changes needed for XAMPP users; env vars only matter in Docker |
| We used `with-db` profile instead of a second compose file | One file, two scenarios — easier to maintain |
| We didn't add HTTPS termination | The customer (MOF) is on a private LAN behind VPN; HTTPS is the customer's reverse proxy's job |
| We didn't add CI/CD yet | That's a separate doc (Phase 2). For now, "developer builds, ops ships the tarball" is fine |
| The data/logs mount is host-relative, not a named volume | Simpler for the dev to find logs at `./data/logs`; survives `down`; on MOF, no MySQL volume is needed |

---

## 8. What we deliberately did NOT do

- **No docker swarm, no kubernetes.** This is one app on one
  server. Overkill.
- **No multi-stage build for the app.** Our image is small
  enough (~300 MB) that the complexity isn't worth it.
- **No custom health-check page.** `/login.php` exists in every
  deployment and is fast to respond, so we use it.
- **No init system.** `apache2-foreground` is the only process;
  if it dies, the container dies; Docker restarts it.
- **No production hardening.** Things like dropping root in the
  container, read-only root filesystem, AppArmor profiles — all
  valuable, but out of scope for a first cut. The customer is on
  a private network, and the security boundary is the VPN.

---

## 9. How to extend this later

- **Add HTTPS** — put a Traefik or Caddy container in front.
- **Add CI/CD** — GitHub Actions builds the image, pushes it to
  a registry, the MOF server pulls on a schedule.
- **Multi-tenant** — parameterize the image with env vars for
  branding.
- **Logs to a central place** — mount `/var/log/apache2` to a
  volume and ship to ELK / Loki.

For each of these, the existing structure should accommodate
without rewrite.
