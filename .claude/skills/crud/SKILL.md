---
name: crud
description: Scaffold CRUD (Domain+Application+Infra+tests) for an entity in an existing bounded context
argument-hint: <Context> <Entity>
---

Parse "$ARGUMENTS" as two space-separated values: context name and entity name.

Steps:
1. Run `make crud context=<Context> entity=<Entity>`.
2. Run `make db-diff` and show the generated migration diff.
3. Ask for explicit confirmation before running `make db-migrate` — never apply a migration silently.
4. After migration, run `make deptrac` to confirm no boundary violations were introduced by the generated code (delegate to the deptrac-guardian subagent if violations are found).
5. Remind the user: add business fields, write fixtures under `<BC>/Infrastructure/Fixture/` using `FixtureData` UUID constants for any cross-BC references.
