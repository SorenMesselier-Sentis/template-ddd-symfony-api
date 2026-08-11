# Migrating object storage: RustFS → Garage (local/CI) + Cloudflare R2 (staging/prod)

RustFS is pre-1.0 (`1.0.0-beta.x`); this repo replaces it with two S3-compatible
backends behind the same `S3ClientFactory` / domain ports, split by environment:

- **[Garage](https://garagehq.deuxfleurs.fr/)** — self-hosted, runs in Docker for
  local development, CI, and integration/HTTP tests. Replaces the `rustfs` compose
  service.
- **[Cloudflare R2](https://developers.cloudflare.com/r2/)** — managed, used in
  staging/prod via `S3_ENDPOINT`/`S3_ACCESS_KEY`/`S3_SECRET_KEY` pointed at a real
  R2 bucket. No container involved.

Both are plain S3-compatible endpoints, so the Document BC's domain ports
(`DocumentStorageInterface`, `BucketManagerInterface`, etc. — see
`src/Document/Domain/Storage/`) need **no changes**. Only the adapter's
hardcoded region/addressing-style assumptions and the environment wiring change.

This doc is the executable checklist for the migration. Steps are grouped by
file/concern; check them off in order. Facts below marked **(verified)** were
tested against a real `dxflrs/garage:v2.3.0` container in this session, not
assumed from docs.

## Why this is more than an env var swap

RustFS auto-provisions everything from env vars on boot (`RUSTFS_ACCESS_KEY`,
`RUSTFS_SECRET_KEY`, ...). Garage does not: a fresh node has **no cluster layout**
and **no access keys** until you bootstrap it once via CLI. **(verified)**:
`key import`/`bucket create` fail with `Layout not ready` until a layout is
assigned and applied. This is a one-time, idempotent step per environment
(handled by a new `make garage-bootstrap` target — see step 5).

R2, by contrast, needs no bootstrap step here: buckets/keys are provisioned
through the Cloudflare dashboard or Terraform, not by this template's tooling.

## Key facts locked down by hands-on testing

- Garage CLI commands (`layout`, `key`, `bucket`, ...) must run **inside the
  running server container** (`docker compose exec garage /garage ...`) — a
  separate container can't reach the node's RPC/metadata without extra
  network-namespace and volume sharing. **(verified)**
- Bootstrap order matters: `layout assign` → `layout apply` **before** any
  `key import` / `bucket create` — both fail with `Layout not ready`
  otherwise. **(verified)**
- Idempotency per command **(verified)**:
  - `layout assign` — safe to re-run (re-stages the same change); only run
    assign+apply at all if `garage status` still shows `NO ROLE ASSIGNED`.
  - `key import <id> <secret> --yes` — **fails** (`KeyAlreadyExists`, 409) on
    a second run; guard with `garage key info <id>` (exit `0`/`1`) first.
  - `bucket create <name>` — **fails** (`BucketAlreadyExists`, 409) on a
    second run; guard with `garage bucket info <name>` first.
  - `bucket allow ... --key <id>` — safe to re-run unconditionally.
- `key import` accepts arbitrary non-`GK`-prefixed access key IDs (e.g. the
  existing dev credential style), not just Garage-generated `GK...` keys.
- **Imported keys cannot call S3 `CreateBucket`/`DeleteBucket` by default**
  (`Can create buckets: false`) — the S3 API rejects it with `AccessDenied:
  ... is not allowed to create buckets`. This breaks
  `S3BucketManager::create()`/`delete()` (used by
  `DocumentObjectStorageFixtureSeeder` and any future dynamic bucket
  provisioning) unless the key is granted `garage key allow --create-bucket
  <key>` during bootstrap. Non-obvious and easy to miss — RustFS never had
  this restriction.
- S3 API works end-to-end with `region = "garage"` + path-style addressing,
  confirmed via `aws s3 ls`/`cp` against the container.
- R2 supports `CreateBucket`/`DeleteBucket`/`ListBuckets`/`HeadBucket` through
  its S3 API (with region aliased to `auto`), so `BucketManagerInterface`
  works unchanged against R2 too — no special-casing needed in the adapter.
  Multipart upload is supported with one caveat: re-uploading the same part
  number discards the previous part if the retry fails (irrelevant to this
  codebase's usage, which uploads each part once).
- Current stable image tag: `dxflrs/garage:v2.3.0`.

## Step-by-step checklist

### 1. `docker/garage/garage.toml` (new file)

Single-node config: `replication_factor = 1`, `db_engine = "sqlite"`,
`s3_region = "garage"`, RPC secret from `GARAGE_RPC_SECRET` env (interpolated
at container start isn't supported by Garage's TOML loader directly — bake a
fixed dev secret into the tracked file, same trust model as the other
committed dev defaults in `.env`/`.env.test`, e.g. `POSTGRES_PASSWORD=change_me`).

### 2. `docker/compose.yaml`

- Replace the `rustfs` service block with a `garage` service:
  - `image: dxflrs/garage:v2.3.0`
  - mount `./garage/garage.toml:/etc/garage.toml:ro`
  - named volume `garage_data:/data` (holds both meta + data per config)
  - publish only `${S3_API_PORT}:3900` (S3 API) — do **not** publish
    3901 (RPC)/3903 (admin) to the host; nothing outside the compose network
    needs them.
  - healthcheck: `["CMD", "/garage", "status"]` — works even pre-layout, no
    `curl`/`start_period` juggling like RustFS needed.
- `php` service: swap `depends_on.rustfs` → `depends_on.garage` (same
  `condition: service_healthy` pattern).
- Rename volume `rustfs_data` → `garage_data` in the top-level `volumes:`
  block.
- Drop the RustFS console port mapping entirely — Garage has no built-in web
  console. (A community `garage-webui` project exists if a UI is wanted
  later; out of scope here.)

### 3. Env files (`.env`, `.env.local`, `.env.test`)

Keep the app-facing `S3_*` names (they're already backend-agnostic) and add
two new ones the adapter needs to stop hardcoding:

```bash
# S3-compatible object storage — Garage in Docker (dev/CI), Cloudflare R2 in staging/prod
S3_ACCESS_KEY=garageadmin
S3_SECRET_KEY=change_me
S3_ENDPOINT=http://garage:3900
S3_REGION=garage             # NEW — "garage" locally, "auto" for Cloudflare R2
S3_FORCE_PATH_STYLE=true     # NEW — true for both Garage and R2
S3_USE_SSL=false
S3_API_PORT=3900
S3_PRESIGNED_URL_TTL=3600
```

No `GARAGE_*` env var is needed: Garage's TOML loader doesn't interpolate
environment variables, so `docker/garage/garage.toml`'s RPC secret is a
baked-in dev-only value (same trust model as other committed dev defaults
like `POSTGRES_PASSWORD=change_me`), not env-driven.

Drop `S3_CONSOLE_PORT` (no console). `.env.test` keeps the same shape minus
the host-only `S3_API_PORT` (tests run against the already-running
dev-compose Garage, same as the RustFS-era setup).

For **staging/prod**, `S3_ENDPOINT`/`S3_ACCESS_KEY`/`S3_SECRET_KEY` point at a
real R2 bucket instead (`https://<account_id>.r2.cloudflarestorage.com`,
credentials from an R2 API token), `S3_REGION=auto`, `S3_USE_SSL=true`. No
`GARAGE_*` vars apply outside Docker.

### 4. `src/Document/Infrastructure/Storage/S3ClientFactory.php`

Remove the hardcoded `'region' => 'us-east-1'` and
`'use_path_style_endpoint' => true`. Add `string $region` and
`bool $forcePathStyle` constructor parameters, threaded through from the two
new env vars.

Update every call site to match the new signature:

- `config/services.yaml` — `S3DocumentStorageAdapter`, `S3BucketExistenceChecker`,
  `S3BucketManager` argument blocks: add
  `$region: '%env(S3_REGION)%'` / `$forcePathStyle: '%env(bool:S3_FORCE_PATH_STYLE)%'`.
- `src/Document/Infrastructure/Health/ObjectStorageHealthCheck.php` — its
  injected `$clientFactory` callable type
  (`callable(string, string, string, bool, ?float): S3Client`) gains two more
  positional params; update the default closure and the `#[Autowire]`
  constructor args (`S3_REGION`, `bool:S3_FORCE_PATH_STYLE`).
- `tests/Unit/Document/Infrastructure/Health/ObjectStorageHealthCheckTest.php`
  — update the inline closures' signatures to match.

### 5. `Makefile`

Add a `garage-bootstrap` target implementing the verified idempotent sequence
(layout assign+apply gated on `NO ROLE ASSIGNED`; key import gated on
`garage key info`; bucket create gated on `garage bucket info`; bucket allow
always re-run) for each bucket in `document_storage.buckets`
(`config/packages/document_storage.yaml` — currently `documents`, `invoices`).
Read `S3_ACCESS_KEY`/`S3_SECRET_KEY` from `.env.local` inside the recipe.

Wire it into `init: build up install db-fresh garage-bootstrap` (order
matters — `garage` must be up+healthy first, which `up` already waits for via
the compose healthcheck).

### 6. `tests/Support/ObjectStorageTestTrait.php`

Update `createObjectStorageS3Client()` to stop hardcoding `region`/
`use_path_style_endpoint` — read from `$_ENV['S3_REGION'] ?? 'garage'` and
`$_ENV['S3_FORCE_PATH_STYLE'] ?? true`, matching the adapter. Update the
endpoint/access-key fallback defaults (`http://garage:3900`, `garageadmin`).

### 7. Test skip messages (cosmetic but real)

Rename `'RustFS is not available.'` → `'Object storage is not available.'` in:
- `tests/Integration/Document/S3DocumentStorageAdapterTest.php`
- `tests/Http/Document/UploadDocumentControllerTest.php` (×3)
- `tests/Http/Document/DocumentSecurityHttpTest.php`

### 8. CI (`.github/workflows/ci.yml`)

- `up -d --wait postgres rabbitmq rustfs redis php` → replace `rustfs` with
  `garage`.
- Add a step right after that (`make garage-bootstrap` or the equivalent
  `docker compose exec` calls) **before** `composer install`/fixtures run,
  since fixture loading (`DocumentObjectStorageFixtureSeeder`) needs buckets
  to exist (it already no-ops gracefully if storage is unreachable, but
  bootstrapping first means it actually seeds instead of skipping).

### 9. Docs prose (`README.md`, `docs/ddd-conventions.md`, `.claude/CLAUDE.md`)

- README tech-stack table row, beta-status callout → replace with a two-row
  Garage/R2 entry; drop the beta disclaimer (Garage is production-grade;
  note R2 is Cloudflare's managed offering).
  - Note: the R2 S3 API compatibility facts about `CreateBucket`, region
    `auto` handling, and multipart caveats above already reflect
    Cloudflare's own current docs — check
    <https://developers.cloudflare.com/r2/api/s3/api/> again if this doc goes
    stale, rather than trusting old prose blindly.
- Architecture tree comment: `Document/ # Object storage (RustFS) ...` →
  `Document/ # Object storage (Garage/R2, S3-compatible) ...`.
- Env var reference table and inline `.env.local` block: replace
  `RustFS`/`RUSTFS_*` rows with `S3_REGION`, `S3_FORCE_PATH_STYLE`,
  `GARAGE_RPC_SECRET`; update the `S3_ENDPOINT`/`S3_API_PORT` descriptions.
- Replace the **"Migrating from MinIO"** section with a **"Migrating from
  RustFS"** section: same shape (config mapping table, object-data sync
  commands using `aws s3 sync`/`rclone` against the new Garage/R2 endpoint,
  DB-metadata note that no schema change is needed), swapping RustFS↔MinIO's
  former roles.
- RustFS Console access row → remove (no console); optionally add a "Garage
  CLI" row pointing at `make garage-bootstrap` / `docker compose exec garage
  /garage bucket list`.
- Test-pyramid / local-setup prose: "RustFS" → "Garage" (dev/CI), one-line
  mention that prod uses R2.
- `docs/ddd-conventions.md` lines 141, 217 and `.claude/CLAUDE.md` line 105:
  "RustFS" → "Garage" (or generic "S3-compatible storage" where the sentence
  doesn't need to name the backend).

### 10. Verify

Run `make down -v && make init && make ci` (or incrementally: `make up`,
`make garage-bootstrap`, `make db-fresh`, `make ci`) and confirm the
Document BC integration/HTTP tests pass against Garage instead of skipping.
R2 itself isn't exercised by this repo's tooling — validate it manually
against a real Cloudflare account before relying on it in staging/prod.

**This checklist was executed end-to-end in this repo** (Docker stack up,
`make garage-bootstrap`, full `make ci`) — final result: cs-check/phpstan/
deptrac clean, Unit 324 passed, Integration 34/34 passed (0 skipped — all
previously ran against a live Garage, not a skip), Http 45/45 passed. Two
real bugs only surfaced by actually running it, not by reading the code:

1. **Garage rejects secret keys under 16 characters** (`ImportKey` /
   `InvalidRequest 400: Secret keys should be at least 16 characters long`).
   The old RustFS-era dev default `S3_SECRET_KEY=change_me` (9 chars) fails
   `garage-bootstrap`. Fixed to `change_me_16_char_minimum` in `.env` /
   `.env.local` — if you're hand-migrating a fork, check your dev secret's
   length before running bootstrap.
2. **`tests/Integration/Document/S3DocumentStorageAdapterTest.php`
   constructs `S3DocumentStorageAdapter`/`S3BucketManager`/
   `S3BucketExistenceChecker` directly** (bypassing DI), so the step-4
   constructor-signature change doesn't reach it through
   `config/services.yaml` — grep for `new S3DocumentStorageAdapter(`,
   `new S3BucketManager(`, `new S3BucketExistenceChecker(` across `tests/`
   in addition to updating the DI config, or this fails with
   `ArgumentCountError` instead of a clean type error.

Also note: after changing `S3_SECRET_KEY` in `.env.local`, the running `php`
container still has the **old** value baked into its process environment
until recreated — `docker compose up -d --force-recreate php` (or
`make restart`) is required, editing the file alone isn't enough.

## Non-goals

- No code path branches on "which backend am I talking to" — Garage and R2
  are both just S3-compatible endpoints configured via env vars. If that
  invariant breaks (e.g. a future R2 quirk needs special-casing), don't
  patch around it in `S3ClientFactory` without updating this doc.
- No automated RustFS→Garage/R2 *data* migration tooling is added, consistent
  with the prior "automated Docker volume migration is out of scope"
  stance for the MinIO→RustFS move. The README's "Migrating from RustFS"
  section documents the manual `aws s3 sync`/`rclone` path.
