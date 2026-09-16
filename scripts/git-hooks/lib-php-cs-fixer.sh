#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if [[ $# -eq 0 ]]; then
    exit 0
fi

PHP_CS_FIXER_ARGS=(
    fix
    --config=.php-cs-fixer.dist.php
    --dry-run
    --diff
)

run_fixer() {
    "$1" "${PHP_CS_FIXER_ARGS[@]}" -- "${@:2}"
}

if [[ -f .env.local ]] && command -v docker >/dev/null 2>&1; then
    if docker compose -f docker/compose.yaml --env-file .env.local ps --status running --services php 2>/dev/null | grep -qx php; then
        docker compose -f docker/compose.yaml --env-file .env.local exec -T php \
            vendor/bin/php-cs-fixer "${PHP_CS_FIXER_ARGS[@]}" -- "$@"
        exit $?
    fi
fi

if [[ -x vendor/bin/php-cs-fixer ]]; then
    run_fixer vendor/bin/php-cs-fixer "$@"
    exit $?
fi

echo "PHP CS Fixer not found. Run 'composer install', 'make install', or use vendor/bin/php-cs-fixer locally." >&2
exit 1
