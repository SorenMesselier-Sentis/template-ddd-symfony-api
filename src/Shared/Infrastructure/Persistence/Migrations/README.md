# Doctrine migrations

## Granularity (avoid catch-all migrations)

Each file is **one coherent schema step** you could deploy on its own:

| Rule | Detail |
|------|--------|
| **Slice by intent** | New table (+ its indexes) = one migration. Related column changes for the same deliverable = one migration. |
| **Avoid bundling** | Do not mix unrelated changes (e.g. outbox + refresh tokens) in a single version unless you document a hard transactional reason. |
| **Order** | The `Version<YYYYMMDDHHMMSS>` prefix defines execution order; timestamps should reflect how features were introduced. |

If `doctrine:migrations:diff` outputs too much at once, split manually into several versions, or adjust mappings and regenerate in smaller steps.

## Code style

- `getDescription()`: one clear sentence describing **what** this version adds or changes.
- Multi-line SQL: heredoc `<<<'SQL' … SQL`; one `addSql()` per statement when it stays readable.
- Index names: `uniq_<table>_<columns>` for uniques, `idx_<table>_<purpose>` for non-unique.
- Do not commit Doctrine auto-generated boilerplate comments.

## Initial migrations in this template

Seven separate files, in order:

1. Users (`users`).
2. Transactional outbox (`outbox_messages`).
3. Refresh tokens (`refresh_tokens`).
4. Password reset tokens (`password_reset_tokens`).
5. Email verification tokens (`email_verification_tokens`).
6. Documents (`documents`).
7. Multipart upload sessions (`multipart_upload_sessions`).

That mirrors progressive capabilities; it is not a single “initial dump” migration.

## After rewriting migration history (local only)

Never edit a migration that has already run in a shared environment. For new work, add a **new** timestamped migration.

If you replaced the Git history of migrations and your DB is out of sync, reset:

```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```
