# Deployment (single VM, Docker Compose)

One ready-made deployment path — a single host running `docker/compose.prod.yaml` — for the reasons
explained in the README ("Building & publishing the production image"): a direct extension of the
Docker-first approach used everywhere else in this repo, not an attempt to cover every target.
Kubernetes/ECS/Terraform/etc. are all valid choices for a real production system but vary too much per
fork for a template to guess right — this doc only covers the one path this repo actually ships.

**Not covered, and not planned:** high availability / multi-node, autoscaling, zero-downtime rolling
deploys (a deploy briefly restarts the `php` service — see "Updating" below), a managed secrets store.
If you need any of those, this compose file is a reasonable starting point to adapt, not the end state.

## Prerequisites

- A host with Docker Engine + the Compose plugin installed, reachable on ports **80** and **443** from
  the internet (Caddy's automatic HTTPS needs the ACME HTTP-01 challenge to reach the box on 80).
- A DNS `A`/`AAAA` record pointing at the host's IP — this becomes `SERVER_NAME` below.
- Cloudflare R2 (or any S3-compatible bucket) for object storage — Garage is dev/CI-only, see
  `docker/compose.yaml`'s comment on the `garage` service.
- A real SMTP provider for `MAILER_DSN` — Mailpit is dev-only.
- The repo cloned on the host (`git clone` — only `docker/compose.prod.yaml`, `docker/postgres/`,
  `docker/rabbitmq/`, and the generated `config/jwt`/`config/oauth` directories are actually read at
  runtime, but cloning the whole repo is the simplest way to get all of them in the right relative
  layout, and you'll want it for `git pull` on every update anyway).

## First-time setup

**1. Configure secrets.** Copy `.env` to `.env.local` on the host and fill in real values — same
mechanism as local dev (`.env.local` is gitignored, never committed), just with production values this
time. At minimum:

```bash
cp .env .env.local
```

Then edit `.env.local`:

| Variable | What to set it to |
|---|---|
| `DOCKER_IMAGE` | `ghcr.io/<owner>/<repo>` (lowercase — matches what `publish-image` pushes) |
| `IMAGE_TAG` | `latest`, a git SHA, or a `vX.Y.Z` tag |
| `SERVER_NAME` | Your real domain, no scheme (e.g. `api.example.com`) |
| `APP_SECRET`, `POSTGRES_PASSWORD`, `RABBITMQ_PASSWORD`, `REDIS_PASSWORD`, `MERCURE_JWT_SECRET`, `JWT_PASSPHRASE` | Real random secrets — never the `change_me*` placeholders from `.env` |
| `DATABASE_URL`, `RABBITMQ_DSN` | Leave as-is if self-hosting via this same compose file (they already reference the `postgres`/`rabbitmq` service names); point elsewhere for a managed alternative (see below) |
| `S3_*` | Your R2 (or other S3-compatible) bucket credentials |
| `MAILER_DSN` | A real SMTP DSN |
| `SENTRY_DSN` | Optional — leave empty to disable, see README "Error tracking (Sentry)" |
| `DEFAULT_URI`, `APP_FRONTEND_URL`, `CORS_ALLOWED_ORIGINS` | Your real public URL / frontend origin — **don't leave these blank**: an empty value is not the same as unset (see the comment on `APP_FRONTEND_URL` in `.env`) and silently produces broken links/CORS instead of falling back sensibly |
| `OAUTH2_ENCRYPTION_KEY` | Generate with the command in `docs/api-clients.md`, not the placeholder |

**2. Generate the JWT and OAuth2 keypairs**, directly on the host (`openssl` is a standard package, no
container needed yet):

```bash
mkdir -p config/jwt config/oauth

# Replace change_me with the real JWT_PASSPHRASE you set in .env.local above.
openssl genrsa -aes256 -passout pass:change_me -out config/jwt/private.pem 4096
openssl rsa -pubout -passin pass:change_me -in config/jwt/private.pem -out config/jwt/public.pem

openssl genrsa -out config/oauth/private.key 2048
openssl rsa -in config/oauth/private.key -pubout -out config/oauth/public.key
chmod 600 config/jwt/private.pem config/jwt/public.pem config/oauth/private.key config/oauth/public.key
```

Set `JWT_SECRET_KEY`/`JWT_PUBLIC_KEY` in `.env.local` to `%kernel.project_dir%/config/jwt/private.pem` /
`.../public.pem` (the default in `.env` already points there — leave as-is unless you moved the files).
Back these files up somewhere durable outside the VM (a secrets manager, encrypted object storage) —
losing them isn't data loss, but it invalidates every issued token and forces every user to
re-authenticate.

**3. Deploy:**

```bash
make prod-deploy   # = prod-pull + prod-migrate + prod-up
```

`make prod-up` waits for every service's healthcheck before returning. First boot: Caddy requests the
Let's Encrypt certificate on the first HTTPS request it receives — allow it a few seconds after startup
before the first real request.

## Updating to a new version

```bash
# set IMAGE_TAG in .env.local to the new tag, or leave it on "latest"
make prod-deploy
```

This briefly recreates the `php`/`scheduler`/`consumer` containers (a few seconds of downtime — see
"Not covered" above if you need to avoid that). Migrations run *before* the new containers start, so a
migration that's incompatible with the *old* code running for those last few seconds needs the usual
expand/contract discipline (add nullable columns first, backfill, only remove the old column in a later
deploy) — this template's migration tooling doesn't do anything special about that for you.

## Rollback

Set `IMAGE_TAG` back to the previous known-good tag (the short git SHA tags from `publish-image` are
useful here) and run `make prod-deploy` again. **This does not roll back the database schema** — a
migration is a forward-only operation in this template (see `make db-diff`/`make db-migrate`); if the
new version's migration needs to be undone, write and run the down-migration by hand
(`doctrine:migrations:execute --down`) before rolling the image back, or restore a backup taken before
the deploy.

## Backups

Self-hosting Postgres via this compose file: automate `make prod-db-backup` (see
[docs/backup-and-restore.md](backup-and-restore.md) for what `db-backup`/`db-restore` actually do) with
cron:

```cron
0 3 * * * cd /path/to/app && make prod-db-backup >> /var/log/app-backup.log 2>&1
```

Prefer a managed Postgres instead (see below) if you want continuous point-in-time recovery rather than
periodic snapshots — `docs/backup-and-restore.md` "Production strategy" covers the trade-off.

## Using a managed Postgres / Redis instead of self-hosting

`docker/compose.prod.yaml` self-hosts Postgres/RabbitMQ/Redis because that's what "single VM" implies,
but nothing about the app requires it. To use a managed Postgres (RDS, Cloud SQL, Neon, Supabase, …):
point `DATABASE_URL` in `.env.local` at it and delete the `postgres` service (and its `depends_on`
entries on `php`/`scheduler`/`consumer`) from `docker/compose.prod.yaml`. Same idea for a managed Redis
(ElastiCache, Redis Cloud, …) — update `REDIS_*`/`METRICS_REDIS_*` and drop the `redis` service.
RabbitMQ is less commonly available as a managed product outside CloudAMQP-style add-ons; self-hosting
it is the more typical choice even alongside a managed database.

## Monitoring (optional)

Prometheus/Grafana aren't included in `docker/compose.prod.yaml` by default. If you want them, copy the
`prometheus`/`grafana`/`postgres_exporter` service definitions and the `profiles: ["monitoring"]`
pattern from `docker/compose.yaml` — same config files (`docker/prometheus/`, `docker/grafana/`) work
unchanged, they're not dev-specific. See README "Monitoring stack (optional)".

## Known limitations

- **Runs as root in the container**, same as the `dev` image — see README "Building & publishing the
  production image" for why (FrankenPHP binding port 80) and what hardening it needs.
- **No horizontal scaling** — one `php` container serves all traffic. If you outgrow a single VM, that's
  the point where Kubernetes/ECS/a load balancer in front of multiple hosts starts being worth the extra
  complexity this doc deliberately doesn't take on.
