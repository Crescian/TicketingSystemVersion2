#!/bin/bash
set -uo pipefail

CONTAINERS=("ticketing_app_1" "ticketing_app_2" "ticketing_queue_worker")
LOG_DIR="/home/cmlinux/backups/ticketing-watchdog"
LOG_FILE="${LOG_DIR}/watchdog.log"

mkdir -p "$LOG_DIR"

for CONTAINER in "${CONTAINERS[@]}"; do
    STATUS="$(docker inspect --format='{{.State.Health.Status}}' "$CONTAINER" 2>/dev/null)"

    if [ "$STATUS" = "unhealthy" ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') UNHEALTHY — restarting ${CONTAINER}" >> "$LOG_FILE"
        docker restart "$CONTAINER" >> "$LOG_FILE" 2>&1
    fi
done
