#!/bin/bash
set -euo pipefail

CONTAINER="ticketing_pgsql"
DB_USER="postgres"
DB_NAME="ticketing_system"
BACKUP_DIR="/home/cmlinux/backups/ticketing-postgres"
RETENTION_DAYS=14
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

docker exec "$CONTAINER" pg_dump -U "$DB_USER" "$DB_NAME" | gzip > "$OUT_FILE"

if [ ! -s "$OUT_FILE" ]; then
    echo "Backup failed or empty: $OUT_FILE" >&2
    rm -f "$OUT_FILE"
    exit 1
fi

echo "Backup written: $OUT_FILE ($(du -h "$OUT_FILE" | cut -f1))"

find "$BACKUP_DIR" -name "${DB_NAME}_*.sql.gz" -mtime +"$RETENTION_DAYS" -delete
