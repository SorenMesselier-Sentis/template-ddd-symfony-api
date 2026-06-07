#!/bin/sh
set -e

if command -v mc >/dev/null 2>&1; then
    mc alias set local "http://127.0.0.1:9000" "${MINIO_ROOT_USER}" "${MINIO_ROOT_PASSWORD}" >/dev/null
fi

exec /usr/bin/minio "$@"
