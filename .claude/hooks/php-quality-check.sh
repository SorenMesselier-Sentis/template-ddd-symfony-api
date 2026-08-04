#!/usr/bin/env bash

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | grep -o '"file_path"[^,}]*' | sed 's/.*: *"\(.*\)"/\1/')

if [[ "$FILE_PATH" != *.php ]]; then
  exit 0
fi

echo "→ CS-Fixer + PHPStan sur $FILE_PATH"

docker compose -f docker/compose.yaml --env-file .env.local exec -T php \
  vendor/bin/php-cs-fixer fix "$FILE_PATH" --rules=@Symfony 2>&1

docker compose -f docker/compose.yaml --env-file .env.local exec -T php \
  vendor/bin/phpstan analyse "$FILE_PATH" --level=9 2>&1

exit 0
