# Backup and Restore

## What's actually critical

**PostgreSQL is the only store that must be backed up.** It's the source of truth for everything: all
entity tables, `audit_log`, `feature_flags`, refresh/reset tokens, and — critically — `outbox_messages`,
the transactional outbox that guarantees domain events survive a crash between being persisted and being
relayed to RabbitMQ (see `CLAUDE.md`, "Three Messenger buses"). Losing this database loses real data.

Everything else in the stack is either ephemeral by design or reconstructible without data loss:

| Service | Why it doesn't need backing up |
|---|---|
| **Redis** | Cache pool, rate-limiter counters, scheduler "last processed tick" state — all disposable. Losing it costs a brief performance dip and lets the scheduler re-run its missed-run catch-up logic; it never loses domain data. |
| **RabbitMQ** | Transient queue only. The outbox pattern exists specifically so the queue is never the durable copy of an event — if RabbitMQ's data is lost, unpublished events are safely re-relayed from `outbox_messages` on the next scheduler tick (`make outbox-relay` / the `scheduler` worker). |
| **Garage (dev/CI)** | Local, ephemeral object storage for development and tests. Never holds anything worth keeping. |
| **Cloudflare R2 (staging/prod)** | Cloudflare already replicates object storage at the infrastructure layer. If a deployment needs protection against *application-level* mistakes (accidental delete, a bad migration script) rather than hardware failure, that's R2 bucket versioning / cross-region replication — an infra decision that varies per fork, same as the deployment target in "Building & publishing the production image" (README). Out of scope here for the same reason. |
| **JWT signing keys** (`config/jwt/*.pem`) | Gitignored, generated per environment, never committed or baked into the image (see `.dockerignore`). Losing them isn't data loss — it invalidates every issued access/refresh token, forcing all users to re-authenticate. Store them somewhere durable across redeploys (a mounted secret / secrets manager), not "back them up" in the database sense. |

## Local backup/restore tooling

```bash
make db-backup                          # dumps to var/backups/<timestamp>.dump (pg_dump, custom format)
make db-restore file=var/backups/<name>.dump   # restores — DESTRUCTIVE, overwrites the current database
```

`db-backup` uses `pg_dump -Fc` (PostgreSQL's compressed, custom archive format — smaller than plain SQL
and restorable with `pg_restore`, including selectively). `db-restore` runs `pg_restore --clean
--if-exists`, which drops and recreates each object before restoring it, so the target database ends up
byte-for-byte matching the dump — not merged with whatever was there before.

This is a **local, manual tool** — for disaster-recovery drills, or grabbing a quick snapshot before a
risky manual change. It is not a production backup strategy by itself.

## Production strategy

Use your database host's native backup feature as the primary strategy — managed Postgres (RDS, Cloud
SQL, Neon, Supabase, etc.) offers continuous WAL-based point-in-time recovery, which recovers to *any*
timestamp, not just the moment of your last periodic dump. A periodic `pg_dump` snapshot is a reasonable
fallback if self-hosting Postgres without a managed backup feature, but it only ever recovers to the
moment the dump was taken.

Whichever strategy is used: **an untested backup is not a backup.** Periodically restore a real backup
into a scratch database (`make db-restore` works for exactly this, pointed at a non-production
`DATABASE_URL`) and confirm the app actually boots and serves against it (e.g. `GET /health` returns
`200`) — this validates the backup is actually restorable, not just that the backup command exited `0`.
(`make db-validate` is not useful for this check on this template: it compares the schema against
Doctrine's ORM entity mappings, and `audit_log`/`feature_flags`/`outbox_messages` are intentionally raw
DBAL tables with no ORM mapping — see `CLAUDE.md` — so it reports drift on a perfectly healthy, freshly
migrated database too.)
