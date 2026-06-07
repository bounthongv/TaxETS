# Docker for PHP Developers — A Beginner's Guide

> **Who this is for**: PHP developers who have used XAMPP / WAMP and
> want to understand what Docker is, why people use it, and how to
> work with it day-to-day.
>
> **Time to read**: ~20 minutes.
>
> **What you will get out of it**: a working mental model of Docker
> and enough vocabulary to read any Docker tutorial without getting
> lost.

## See also

The Tax-ETS Docker setup is spread across several docs. Read this
one first, then jump to the others as you need them:

| Doc | When to read it |
|---|---|
| [`17-docker-tax-ets-implementation.md`](17-docker-tax-ets-implementation.md) | After this one — explains *why* each line of our `Dockerfile` and `docker-compose.yml` looks the way it does |
| [`18-docker-mof-deployment.md`](18-docker-mof-deployment.md) | When you're about to install Tax-ETS on the MOF Laos server (USB workflow, no internet) |
| [`20-multi-app-routing.md`](20-multi-app-routing.md) | When you add a 2nd Docker app (MK-Dashboard, Lao Knowledge Hub) and want clean URLs instead of port numbers |

---

## 1. The problem Docker solves

You have probably lived this story:

> "It works on my machine."

You write PHP code on your Windows laptop with XAMPP. You push it
to a Linux server. Suddenly something is broken. The PHP version is
different. The MySQL version is different. An extension is missing.
The file paths are case-sensitive and yours weren't. After two days
of debugging, you discover the production server is missing the
`gd` PHP extension and `mod_rewrite` is not enabled.

**Docker's promise:** "If it works in the container, it works
everywhere the same container runs." You build the container on
your laptop, ship the container to the server, and the server runs
it. No surprises.

---

## 2. Core concepts (the only 6 you need)

Think of Docker as a standardized way to package and ship software.
There are six words you must understand:

### 2.1 Image

A **Docker image** is a saved snapshot of a complete filesystem +
the command to run it. It is what you build.

- An image is **immutable** — once built, it never changes.
- An image is **layered** — each instruction in the `Dockerfile`
  creates a new layer. Docker caches layers, so re-building after a
  small code change is fast.
- An image is **portable** — you can copy it to a USB, upload it,
  email it. The format is just a `.tar` file.

**Analogy:** an image is like an `.iso` file for a virtual machine,
except much smaller and faster to start.

### 2.2 Container

A **Docker container** is a *running instance* of an image.

- You can start many containers from the same image.
- Each container has its own CPU/RAM slice, network interface, and
  filesystem changes (that don't go back to the image).
- When the container stops, all changes inside it are **lost** —
  *unless* you attach a volume (see below).

**Analogy:** an image is a class; a container is an instance of
that class.

### 2.3 Dockerfile

A **`Dockerfile`** is a text file with the recipe for building an
image. It is essentially a list of shell commands, but with caching.

Our `Dockerfile` for Tax-ETS is 80 lines. The first line is:

```dockerfile
FROM php:8.2-apache
```

That means "start with the official PHP 8.2 + Apache image, then add
my stuff on top." Everything else (installing extensions, copying
code, setting permissions) is layered on top of that base.

### 2.4 Volume

A **volume** is a directory that exists *outside* a container so
its data survives container restarts and `docker rm`.

For Tax-ETS, we mount `./data/logs` from the host into the
container at `/var/www/html/data/logs`. That way, when an admin
imports a spreadsheet and the app writes a log file, the log
survives a `docker compose down && docker compose up`.

### 2.5 Network

A **Docker network** is a virtual LAN. Containers on the same
network can talk to each other by service name. In our compose
file, the PHP container reaches the MySQL container using the
hostname `db` (not `localhost`, not an IP).

### 2.6 Registry / Hub

A **registry** is a place to store and share images.
**Docker Hub** is the public one (`hub.docker.com`). When you write
`FROM mysql:8.0`, Docker downloads that image from Docker Hub on
build.

For the MOF customer (no internet), we **save** the image to a
`.tar` file, copy it via USB, and **load** it on the target
machine. The image never leaves the USB.

---

## 3. The 8 commands you will use 90% of the time

| Command | What it does |
| --- | --- |
| `docker build -t myname:tag .` | Build an image from `./Dockerfile`, tag it `myname:tag` |
| `docker images` | List images on this machine |
| `docker run -d -p 8080:80 myname` | Run a container in the background, host port 8080 → container port 80 |
| `docker ps` | List running containers (`-a` includes stopped) |
| `docker logs -f <container>` | Tail the logs (Ctrl-C to exit) |
| `docker exec -it <container> bash` | Open a shell inside the running container |
| `docker stop <container>` | Stop a container gracefully |
| `docker rm <container>` | Delete a stopped container |

For multi-container setups, you use `docker compose` (a separate
tool that reads `docker-compose.yml`):

| Command | What it does |
| --- | --- |
| `docker compose up -d` | Build (if needed) and start all services in the background |
| `docker compose down` | Stop and remove all services |
| `docker compose logs -f web` | Tail logs of the `web` service |
| `docker compose ps` | List containers in this stack |
| `docker compose exec web bash` | Shell into the `web` service |
| `docker compose --profile with-db up -d` | Start, *including* the optional `db` service |

---

## 4. How this applies to Tax-ETS

Our `docker-compose.yml` has two services:

- **`web`** — the PHP/Apache app. Always starts.
- **`db`** — MySQL 8, marked `profiles: ["with-db"]`, so it only
  starts when you pass `--profile with-db`.

### Day-to-day workflows

**Scenario A: I have my own MySQL (XAMPP, Ubuntu server, etc.)**

```bash
# Tell the app where MySQL is
echo "DB_HOST=host.docker.internal" > .env   # macOS / Windows
echo "DB_HOST=192.168.1.50"       > .env     # Linux box on the LAN

# Start the app only
docker compose up -d
```

The PHP container reaches your XAMPP MySQL by IP. No MySQL in
Docker, nothing to manage.

**Scenario B: I want a self-contained stack (DB + app in Docker)**

```bash
# Start MySQL container too, with a one-time schema import
docker compose --profile with-db up -d
```

A fresh MySQL container starts, loads everything in `./db/*.sql`
on first boot, and the app connects to it via the `db` hostname.

**Scenario C: Ship to MOF (no internet)**

```bash
# On the developer's machine
bash docker/save-images.sh
# → creates dist/INSTALL-BUNDLE.tar.gz

# On the MOF server
sudo bash install.sh
```

The image is moved via USB and loaded with `docker load`.

---

## 5. Common beginner confusions, answered

### "What's the difference between `docker run` and `docker compose up`?"

`docker run` starts ONE container. `docker compose up` reads a
YAML file describing MANY services and starts them all with shared
networks, volumes, and start order. For anything beyond a toy
container, use Compose.

### "Why is `db` a hostname and not `localhost`?"

Inside a container, `localhost` means *that container's own
loopback*. The MySQL container is a different machine, even though
they share a Docker network. Docker's internal DNS resolves `db`
to the MySQL container's IP automatically. We use `db` as the
hostname in `.env` when DB runs in Docker, and the host's IP when
it doesn't.

### "Where is my data? I stopped the container and now my files are gone?"

Containers are *disposable*. Files written *inside* a container are
lost when it's removed. That's why we mount `./data/logs` from the
host as a volume — the host's directory persists.

For databases, we use a **named volume** (`tax-ets-mysql-data`),
which Docker stores in its own area on the host. It survives
`docker compose down` and can be inspected with `docker volume
inspect tax-ets-mysql-data`.

### "What if port 8080 is already taken?"

Edit `docker-compose.yml` and change the left side:

```yaml
ports:
  - "9000:80"   # host 9000 → container 80
```

### "Can I edit files inside the container while it's running?"

Yes — for debugging. Use:

```bash
docker compose exec web bash
# now you're inside the container's shell
nano /var/www/html/index.php
```

But those edits are lost on container restart. Edit files on the
host, then `docker compose restart web` (or rebuild if you changed
the Dockerfile or dependencies).

---

## 6. Mental model: the "shipping container" metaphor

The guy who invented Docker borrowed this from the real shipping
industry:

- Before standardized containers, loading a ship was a custom job
  per cargo type.
- After standardized containers, *any* container fits *any* ship,
  *any* truck, *any* train. The contents don't matter.
- Docker does the same for software: package your app + its
  dependencies into a standard "container" and it runs the same
  everywhere.

That's it. Everything else is implementation detail.

---

## 7. Quick reference card (print this)

```
BUILD
  docker build -t tax-ets:latest .              # build the image
  docker images                                  # list images
  docker rmi tax-ets:latest                      # delete an image

RUN (one container)
  docker run -d -p 8080:80 --name tax-ets-web tax-ets:latest
  docker ps                                      # is it running?
  docker logs -f tax-ets-web                    # what is it saying?
  docker exec -it tax-ets-web bash              # shell inside
  docker stop tax-ets-web
  docker rm tax-ets-web

COMPOSE (multi-container, the normal way)
  docker compose up -d                           # start everything
  docker compose --profile with-db up -d         # + MySQL container
  docker compose down                            # stop everything
  docker compose logs -f web                     # app logs
  docker compose restart web                     # restart just web
  docker compose exec web bash                   # shell into web

TRANSFER (offline)
  docker save -o image.tar tax-ets:latest        # export
  docker load -i image.tar                       # import

CLEANUP
  docker system df                               # disk usage
  docker system prune -a                         # delete everything unused
  docker volume prune                            # delete unused volumes
  docker container prune                         # delete stopped containers
```

---

## 8. Where to learn more

- **Official "Get Started" tutorial** (1 hour, hands-on):
  https://docs.docker.com/get-started/
- **Play with Docker** (free browser-based Docker host):
  https://labs.play-with-docker.com/
- **Docker Compose specification** (reference):
  https://docs.docker.com/compose/compose-file/
- **PHP + Docker cookbook** (community): search
  "docker-php-apache best practices"

When you read those, ignore the swarm / kubernetes / production
orchestration stuff for now. The 6 concepts in section 2 and the
commands in section 3 cover 95% of what you need for a PHP project.
