---
name: bc
description: Scaffold a new bounded context via the DDD maker command
argument-hint: <ContextName>
---

Scaffold a new bounded context named "$ARGUMENTS".

Steps:
1. Run `make bc name=$ARGUMENTS` — never hand-create the BC structure.
2. List the files that were generated (Domain/Application/Infrastructure).
3. Remind the user of the next manual steps: add business fields to the entity, write the Doctrine XML mapping, add an exception mapper if the BC needs non-default error statuses, then `make db-diff` → `make db-migrate`.
4. Do not run `make db-migrate` automatically — that requires explicit confirmation.
