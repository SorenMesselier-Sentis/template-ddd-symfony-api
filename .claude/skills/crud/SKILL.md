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
6. Check these before considering the entity done (see CLAUDE.md "Architecture" for details):
   - Holds personal data about a user? → implement `PersonalDataExporterInterface` (mandatory).
   - Exposes a sensitive action (delete, role/permission change, auth event)? → implement `AuditableMessage` on that command (mandatory).
   - Relates to another entity in the *same* BC? → real Doctrine relation (`fetch="EAGER"` if the target's id is `readonly`), not a UUID field. Relates to an entity in *another* BC? → UUID field only, never a relation.
