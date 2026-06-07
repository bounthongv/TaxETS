# Running Multiple Docker Apps on One Machine — Routing Options

> **Who this is for**: you, when you add a 2nd Docker app to your
> dev machine (or your Ubuntu server) and want to stop memorizing
> port numbers.
>
> **Read this**: BEFORE adding the 2nd app, not after.
>
> **Time to read**: ~15 minutes.

This doc explains the three ways to expose multiple Docker apps
on the same machine, when to use each, and gives copy-paste
configurations.

---

## 1. The problem

Right now (with one app), you access Tax-ETS at
`http://localhost:8080`. When you add a second app, the choices
are:

```
A) Use port 8081 for the second app →  localhost:8080, localhost:8081, localhost:8082...
B) Use a reverse proxy on port 80     →  tax-ets.local, knowledge.local, mk.local...
C) Use path-based routing            →  localhost/tax-ets, localhost/knowledge...
```

This document covers all three.

---

## 2. The 3 options compared

| | **A. Different ports** | **B. Subdomain reverse proxy** | **C. Path-based reverse proxy** |
|---|---|---|---|
| Setup time | 0 min | 30 min (once) | 30 min (once) |
| URL looks like | `localhost:8080` | `tax-ets.local` | `localhost/tax-ets` |
| Looks "professional" | ❌ | ✅ | ⚠️ (works but odd) |
| Port conflicts possible | ✅ yes | ❌ no | ❌ no |
| HTTPS support | manual per app | automatic (Let's Encrypt) | automatic |
| Scales to N apps | hard past 5 | easy to 50+ | fragile past 5 |
| Apps need to know their URL? | no | no | **yes** (BASE_URL) |
| Tax-ETS already supports this? | n/a | n/a | **yes** (BASE_URL works) |
| Recommend when | 1-2 apps, prototyping, dev | **2+ apps, anywhere** | special cases only |

**My recommendation: option B (Traefik with subdomains).** It's
the industry standard for Docker-based setups, scales forever, and
gives you free HTTPS when you deploy for real.

---

## 3. Option A — Different ports (what we have now)

```yaml
# docker-compose.yml for Tax-ETS (current)
services:
  web:
    ports:
      - "8080:80"    # Tax-ETS

# docker-compose.yml for the second app
services:
  web:
    ports:
      - "8081:80"    # second app
```

**Use this when:**
- You have only 1-2 apps on the machine
- You're prototyping / testing
- You don't care about URLs looking nice

**Don't use this when:**
- You have 3+ apps and keep forgetting the ports
- You want to share screenshots / URLs with non-technical people
- You want HTTPS

---

## 4. Option B — Traefik reverse proxy with subdomains ⭐

This is what I'd build for you. It's a one-time setup, then adding
a new app is just 3 lines in its `docker-compose.yml`.

### 4.1 How it works

```
                         ┌──────────────────┐
   Browser request:      │                  │
   tax-ets.local:80 ────►│  Traefik proxy   │  listens on :80
   knowledge.local:80 ──►│  (one container) │  reads docker labels
                         │                  │
                         └────────┬─────────┘
                                  │ routes by hostname
              ┌───────────────────┼────────────────────┐
              ▼                   ▼                    ▼
        ┌──────────┐        ┌──────────┐         ┌──────────┐
        │ tax-ets- │        │ lkh-     │         │  mk-     │
        │ web      │        │ web      │         │  web     │
        │ (no port)│        │ (no port)│         │ (no port)│
        └──────────┘        └──────────┘         └──────────┘
```

Each app's container is reachable only through Traefik. It has no
public port — only Traefik can talk to it. This is more secure
too (apps are not directly exposed).

### 4.2 The 30-minute setup

#### Step 1: Create a folder for the proxy

On your Windows machine:

```bash
mkdir -p ~/docker-proxy
cd ~/docker-proxy
```

#### Step 2: Create the Traefik compose file

Save this as `~/docker-proxy/docker-compose.yml`:

```yaml
name: proxy

services:
  traefik:
    image: traefik:v3
    container_name: traefik
    restart: unless-stopped
    command:
      # Docker provider: watch for new containers with traefik labels
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      # Dashboard (optional, for debugging)
      - "--entrypoints.dashboard.address=:8081"
      - "--api.dashboard=true"
      # HTTP entrypoint on port 80
      - "--entrypoints.web.address=:80"
    ports:
      - "80:80"          # public HTTP
      - "8081:8081"      # Traefik dashboard (optional)
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./traefik-data:/data   # dashboard password storage (optional)
    networks:
      - proxy-net

networks:
  proxy-net:
    name: proxy-net
    external: true   # other apps will join this same network
```

#### Step 3: Create the external network (so other apps can join)

```bash
docker network create proxy-net
```

#### Step 4: Start Traefik

```bash
cd ~/docker-proxy
docker compose up -d
```

You should see Traefik start. Check the dashboard at
`http://localhost:8081` (it'll be empty for now — no apps are
routed yet).

#### Step 5: Tell Windows about the subdomains

Edit `C:\Windows\System32\drivers\etc\hosts` as Administrator
(right-click Notepad → Run as Administrator → File → Open →
browse to the path):

```
# Add these lines at the bottom:
127.0.0.1   tax-ets.local
127.0.0.1   knowledge.local
127.0.0.1   mk.local
```

(`*.local` is a special TLD that always resolves to 127.0.0.1 on
most systems, so you can skip this on macOS/Linux. On Windows you
have to add it manually.)

#### Step 6: Convert Tax-ETS to use Traefik

Update `D:\tax-ets\docker-compose.yml`:

```yaml
name: tax-ets

services:
  web:
    build: .
    image: tax-ets:latest
    container_name: tax-ets-web
    restart: unless-stopped
    # NO ports: section anymore — Traefik handles it
    environment:
      DB_HOST: ${DB_HOST:-db}
      DB_PORT: ${DB_PORT:-3306}
      DB_NAME: ${DB_NAME:-tax_ets}
      DB_USER: ${DB_USER:-tax_ets_user}
      DB_PASS: ${DB_PASS:-change_this_password}
      BASE_URL: ${BASE_URL:-}
      TZ: ${TZ:-Asia/Vientiane}
    volumes:
      - ./data/logs:/var/www/html/data/logs
    # --- Traefik labels (the magic 3 lines) ---
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.tax-ets.rule=Host(`tax-ets.local`)"
      - "traefik.http.routers.tax-ets.entrypoints=web"
    # --- Join the shared network ---
    networks:
      - proxy-net
      - tax-ets-net

  db:
    image: mysql:8.0
    profiles: ["with-db"]
    # ... (unchanged) ...

networks:
  tax-ets-net:
    driver: bridge
  proxy-net:
    external: true   # points to the network we created in step 3
```

Then restart:

```bash
cd /d/tax-ets
docker compose up -d
```

Now open `http://tax-ets.local` (no port, no `/tax-ets/`). It
should just work. ✨

#### Step 7: Adding a 2nd app (e.g., Lao Knowledge Hub)

In the new app's `docker-compose.yml`, just add:

```yaml
services:
  web:
    # ... (other config) ...
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.lkh.rule=Host(`knowledge.local`)"
      - "traefik.http.routers.lkh.entrypoints=web"
    networks:
      - proxy-net
      # ... other networks ...

networks:
  proxy-net:
    external: true
```

Add a new line to your hosts file:
```
127.0.0.1   knowledge.local
```

That's it. No restart of Traefik. It picks up the new container
automatically.

### 4.3 Traefik dashboard

Visit `http://localhost:8081` to see:
- All registered routes
- Active connections
- Service health

Great for debugging "why isn't my app reachable?"

### 4.4 Adding HTTPS later (when you deploy to a real server)

Once you have a real domain (e.g., `tax-ets.apis.com.la` pointing
at your Ubuntu server), Traefik can auto-provision Let's Encrypt
certificates. Add these flags to Traefik's `command:`:

```yaml
- "--entrypoints.websecure.address=:443"
- "--certificatesresolvers.letsencrypt.acme.tlschallenge=true"
- "--certificatesresolvers.letsencrypt.acme.email=you@yourdomain.com"
- "--certificatesresolvers.letsencrypt.acme.storage=/data/acme.json"
```

And change the labels in your app:

```yaml
labels:
  - "traefik.http.routers.tax-ets.rule=Host(`tax-ets.apis.com.la`)"
  - "traefik.http.routers.tax-ets.entrypoints=websecure"
  - "traefik.http.routers.tax-ets.tls=true"
  - "traefik.http.routers.tax-ets.tls.certresolver=letsencrypt"
  # Auto-redirect HTTP → HTTPS:
  - "traefik.http.middlewares.redirect-https.redirectscheme.scheme=https"
  - "traefik.http.routers.tax-ets.middlewares=redirect-https"
```

Now `http://tax-ets.apis.com.la` auto-redirects to
`https://tax-ets.apis.com.la` with a valid cert. Free.

---

## 5. Option C — Path-based reverse proxy (Caddy)

For completeness, in case you want it.

```bash
# Install Caddy (one-time)
# Windows: download from https://caddyserver.com/
# Linux: sudo apt install caddy

# /etc/caddy/Caddyfile  (or C:\caddy\Caddyfile on Windows)
tax-ets.local {
    reverse_proxy localhost:8080
}
knowledge.local {
    reverse_proxy localhost:8081
}
```

Apps still expose their own ports, Caddy routes by hostname.
Caddy auto-issues HTTPS in production. Simpler than Traefik for
small setups, less powerful for complex routing.

For your use case, **Traefik vs Caddy is mostly a matter of
preference**. Traefik's killer feature is reading labels from
docker-compose.yml — no separate config file to maintain. Caddy
is easier to read for beginners.

---

## 6. Decision matrix — what to do right now

| Scenario | Use |
|---|---|
| 1 Docker app on the dev machine (now) | **Option A** — port 8080 |
| Adding a 2nd Docker app on dev | **Option B (Traefik)** — set it up once |
| 1-2 Docker apps on Ubuntu server (tax-ets.apis.com.la) | **Option A** — different ports, or Option B if you want subdomains |
| Many Docker apps on Ubuntu server (future) | **Option B** — Traefik |
| MOF Laos server | **Not applicable** — only 1 app, port 80 directly |
| Production with real domain | **Option B with HTTPS** — Traefik + Let's Encrypt |

---

## 7. The actual implementation plan (concrete)

**Right now** (1 app, Tax-ETS only):
- Use Option A. Port 8080. Don't change anything.

**When you add the 2nd app** (e.g., Lao Knowledge Hub or
MK-Dashboard):
- I'll spend ~30 min setting up Traefik on your dev machine
- Convert Tax-ETS to Traefik labels (3 lines added)
- The new app also uses Traefik labels
- You'll never type a port number again on your dev machine

**When you deploy to a real server with a domain**:
- Same Traefik setup, with the Let's Encrypt add-on
- Free HTTPS

**MOF server** (always):
- Just port 80 directly. One app, no reverse proxy needed.

---

## 8. Reference: full Traefik cheat sheet

| Task | Command |
|---|---|
| Start the proxy | `cd ~/docker-proxy && docker compose up -d` |
| Stop the proxy | `cd ~/docker-proxy && docker compose down` |
| View proxy logs | `cd ~/docker-proxy && docker compose logs -f traefik` |
| List all routes | `curl -s http://localhost:8081/api/http/routers \| jq` |
| Force re-read of all apps | restart Traefik (it auto-reloads, but sometimes doesn't) |
| See why an app isn't routed | check the app's `labels:` and `traefik.enable=true` |
| Add HTTPS for a new domain | add the 4 lines from section 4.4 to the app's labels |
| Traefik dashboard | http://localhost:8081 (the "api.dashboard" entrypoint) |

---

## 9. FAQ

**Q: Do I need Traefik if I only have one app?**
A: No. Just expose port 80 directly. Traefik only pays off with 2+.

**Q: Can I use Traefik AND a port (e.g., for debugging)?**
A: Yes, add `ports: ["8080:80"]` to the service alongside the
Traefik labels. You get both routes. Useful for development.

**Q: Does Traefik work on the Ubuntu server?**
A: Yes, same setup. The compose file is identical. You'd add
real domain names instead of `.local` ones.

**Q: What about the MOF server behind the VPN?**
A: They only have one app. Traefik adds no value. Just run the
container with port 80 directly (or any port + their existing
reverse proxy, if they have one).

**Q: Does Traefik replace the need for a web server (Apache)?**
A: No. Traefik is a *reverse proxy* — it forwards requests. Your
PHP/Apache container still runs Apache. Traefik sits in front.

**Q: What if I have 10 apps? Do I need 10 subdomains?**
A: Yes — but you only need to do the `docker-compose.yml` change
once per app (3 lines). Traefik auto-discovers.

**Q: Is Traefik hard to learn?**
A: It has a learning curve, but for the simple "route by
hostname" case (which is what we'd use), it's about 10 minutes.
The dashboard helps a lot.
