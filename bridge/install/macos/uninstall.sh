#!/bin/sh
set -e

PLIST_PATH="$HOME/Library/LaunchAgents/com.customrt.bridge.plist"

launchctl unload "$PLIST_PATH" >/dev/null 2>&1 || true
rm -f "$PLIST_PATH"

echo "Bridge rimosso."
