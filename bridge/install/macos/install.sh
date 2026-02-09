#!/bin/sh
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BASE_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
PLIST_PATH="$HOME/Library/LaunchAgents/com.customrt.bridge.plist"
LOG_DIR="$BASE_DIR/bridge/storage"

mkdir -p "$LOG_DIR"

cat > "$PLIST_PATH" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>com.customrt.bridge</string>
  <key>ProgramArguments</key>
  <array>
    <string>/usr/bin/env</string>
    <string>node</string>
    <string>$BASE_DIR/bridge/src/server.js</string>
  </array>
  <key>EnvironmentVariables</key>
  <dict>
    <key>BRIDGE_CONFIG</key>
    <string>$BASE_DIR/bridge/config.json</string>
  </dict>
  <key>WorkingDirectory</key>
  <string>$BASE_DIR/bridge</string>
  <key>StandardOutPath</key>
  <string>$LOG_DIR/bridge.out.log</string>
  <key>StandardErrorPath</key>
  <string>$LOG_DIR/bridge.err.log</string>
  <key>RunAtLoad</key>
  <true/>
  <key>KeepAlive</key>
  <true/>
</dict>
</plist>
EOF

launchctl unload "$PLIST_PATH" >/dev/null 2>&1 || true
launchctl load "$PLIST_PATH"

echo "Bridge installato. Log in $LOG_DIR"
