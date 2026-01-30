#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
LOG_DIR="$ROOT/storage/logs"
LOG_FILE="$LOG_DIR/energy_offers_import.log"

mkdir -p "$LOG_DIR"

{
  echo "--- $(date '+%Y-%m-%d %H:%M:%S') Import offerte energia ---"
  php "$ROOT/scripts/import_energy_offers.php"
  echo ""
} >> "$LOG_FILE" 2>&1
