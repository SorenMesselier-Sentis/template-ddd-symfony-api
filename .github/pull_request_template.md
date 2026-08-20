## Summary

<!-- What does this change do, and why? Link the issue/ticket if there is one. -->

## Bounded context(s) touched or created

<!-- e.g. User, Document, Project, Shared, or a new context -->

## Type of change

- [ ] feat: new functionality
- [ ] fix: bug fix
- [ ] refactor: no behavior change
- [ ] chore / docs / ci
- [ ] breaking change (new `/api/v2` prefix required, not a mutation of `/v1`)

## DDD / architecture checklist

- [ ] `make deptrac` passes, no new cross-BC imports, `Shared/*` still imports no bounded context
- [ ] Cross-BC references use stable UUIDs only, **no Doctrine relations across contexts**
- [ ] New same-BC relations map `fetch="EAGER"` if the related entity's id is `readonly`
- [ ] New/changed entity: personal data -> implements `PersonalDataExporterInterface`
- [ ] New/changed command: sensitive action (delete, role/permission change, auth event) -> implements `AuditableMessage`
- [ ] New/changed command or query: `AuthorizedMessage` role requirement set correctly
- [ ] Migration added via `make db-diff` and lives in `src/Shared/Infrastructure/Persistence/Migrations/` (never per-BC)

## Testing

- [ ] `make ci` passes locally (cs-check, phpstan, deptrac, unit, integration, http)
- [ ] Added/updated tests in `tests/Unit`, `tests/Integration`, and/or `tests/Http` as appropriate
- [ ] Fixtures updated if new reference data is needed (`FixtureData` / `FixtureReference`)

## API/docs

- [ ] Response envelope + HATEOAS `links` followed for any new/changed endpoint
- [ ] `docs/er-diagram.md` regenerated if the schema changed (`make er-diagram`, or let CI do it on `main`)
- [ ] `README.md` / `CLAUDE.md` updated if this introduces a new convention

## Screenshots / API examples

<!-- curl/HTTPie examples or screenshots for reviewers, if useful -->
