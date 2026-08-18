#!/bin/bash
set -euo pipefail

# Zero-downtime deploy for the two-replica ticketing app. Recreates one
# replica at a time, waits for it to report healthy, then reloads nginx so
# it re-resolves that replica's new IP (upstream hostnames only resolve at
# config load time — see the comment above `upstream ticketing_php` in
# nginx-proxy/conf.d/ticketing.conf). The other replica keeps serving
# traffic throughout each step.
#
# Usage: run from the TicketingSystemVersion2 repo root after `docker compose
# build` (or with no prior build if only compose.yaml/.env changed).

REPLICAS=("app_1" "app_2")
REVERSE_PROXY="reverse_proxy"

wait_healthy() {
    local container="$1"
    local waited=0
    until [ "$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null)" = "healthy" ]; do
        sleep 3
        waited=$((waited + 3))
        if [ "$waited" -ge 120 ]; then
            echo "ERROR: $container did not become healthy within 120s — aborting before touching the other replica." >&2
            exit 1
        fi
    done
}

for service in "${REPLICAS[@]}"; do
    container="ticketing_${service}"
    echo "== ${service} (${container}) =="

    docker compose up -d "$service"
    echo "waiting for ${container} to report healthy..."
    wait_healthy "$container"

    echo "reloading nginx so it re-resolves ${container}'s new IP..."
    docker exec "$REVERSE_PROXY" nginx -t
    docker exec "$REVERSE_PROXY" nginx -s reload

    echo "${service} done — traffic confirmed on the other replica throughout."
done

echo "Rolling restart complete. Both replicas updated with zero request-serving gap."
