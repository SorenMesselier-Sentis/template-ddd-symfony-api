---
name: ci
description: Run the full quality gate (cs-check, phpstan, deptrac, unit, integration, http) and summarize results
---

Run `make ci` and report results as a compact per-stage summary (cs-check / phpstan / deptrac / unit / integration / http — pass or fail each).

If `cs-check` fails only on style drift (no logic issues), you may run `make cs-fix` and re-run `make cs-check` to confirm, without asking — this is a safe, mechanical fix.

For any other failure (phpstan, deptrac, test failures), stop and report the exact error with file/line. Do not attempt automatic fixes without discussing the approach first — these usually require understanding intent, not just applying a patch. Do not add the failures to a ignore pattern error schema.
