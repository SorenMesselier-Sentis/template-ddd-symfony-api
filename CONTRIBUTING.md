# Contributing

This is a template repository: what you're contributing to might be the template itself (`main` here) or a
fork that scaffolds new bounded contexts (BCs) from it. Either way, the same workflow applies.

## Setup

Everything runs in Docker, there is no local PHP toolchain assumption.

```bash
cp .env .env.local     # if not already present
make init               # build + up + composer install + db reset + fixtures
```

See the [README](README.md) for the full command list and [`.claude/CLAUDE.md`](.claude/CLAUDE.md) /
[`docs/ddd-conventions.md`](docs/ddd-conventions.md) for architecture rules.

## Before opening a pull request

```bash
make ci    # cs-check, phpstan, deptrac, unit, integration, http — same gate CI runs
```

Also run `make deptrac` specifically after any structural change (new BC, new use case, moved files,
new cross-context import), it's the cheapest way to catch an architecture-boundary violation before
review does.

If your change touches the database schema:

```bash
make db-diff      # generate the migration
make db-migrate
make er-diagram   # regenerate docs/er-diagram.md (CI also does this on main, but do it locally too)
```

## Commit messages

Commits must follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`,
`refactor:`, `chore:`, ...) — enforced by `conventional-pre-commit` if you've installed the hooks
(`pre-commit install`), and by the PR-title check in CI either way, since GitHub's default squash-merge
uses the PR title as the final commit message.

## Scaffolding, not hand-writing

**Never** hand-create a bounded context or CRUD entity, the maker commands also patch config (routes,
Doctrine mapping, repository aliases, RabbitMQ bindings):

```bash
make bc name=Product
make crud context=Product entity=Product
```

Follow the checklist in `docs/ddd-conventions.md` after generating: business fields, exception mapper,
`PersonalDataExporterInterface` if the entity holds personal data, `AuditableMessage` on any sensitive
command, `make db-diff` -> `make db-migrate` -> `make ci`.

## CHANGELOG

Add an entry under `Unreleased` in [`CHANGELOG.md`](CHANGELOG.md) in the same PR as the change (skip for
pure chore/ci/test-only work). This is a template that gets forked repeatedly — the changelog is how a
fork tells what upstream fixes or features are worth pulling in.

## Pull requests

Use the PR template checklist (`.github/pull_request_template.md`) — it mirrors the checks above so
reviewers don't have to ask for them. Keep PRs scoped to one BC or one cross-cutting change where
possible; `Shared/*` changes touch every fork's forward-merge path, so call those out explicitly in the
PR description.
