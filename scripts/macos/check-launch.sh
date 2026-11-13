#!/usr/bin/env bash
#
# Starts a copy of a signed Sudo Play.app once and checks that the launch left
# its signature intact. Run by release.sh after notarization. See docs/RELEASE.md.
#
# NativePHP runs `artisan optimize` on every launch. app/Native/BootstrapCache.php
# sends the caches it writes to the user data folder. If they land inside the
# bundle again, macOS says "a sealed resource is missing or invalid" from the
# first launch on, and this check fails.
#
# The copy uses the real user data folder, the same as installing the build
# would. A running Sudo Play holds the single instance lock and the copy would
# quit at once, so quit it first.
#
# Usage: scripts/macos/check-launch.sh "<path>/Sudo Play.app"
#   SUDO_PLAY_USER_DATA  user data folder, ~/Library/Application Support/sudo-play by default
#   SUDO_PLAY_LAUNCH_WAIT  seconds to wait for the config cache, 120 by default

set -euo pipefail

app="${1:?Usage: scripts/macos/check-launch.sh \"<path>/Sudo Play.app\"}"
user_data="${SUDO_PLAY_USER_DATA:-$HOME/Library/Application Support/sudo-play}"
wait="${SUDO_PLAY_LAUNCH_WAIT:-120}"
config="$user_data/bootstrap/cache/config.php"

[ -d "$app" ] || { echo "No app at $app." >&2; exit 1; }

if pgrep -xq "Sudo Play"; then
    echo "Sudo Play is running. Quit it, then run the check again." >&2
    exit 1
fi

stage="$(mktemp -d)"
copy="$stage/Sudo Play.app"
pid=""
cleanup() {
    [ -n "$pid" ] && kill "$pid" 2>/dev/null || true
    pkill -f "$copy/Contents/" 2>/dev/null || true
    rm -rf "$stage"
}
trap cleanup EXIT

ditto "$app" "$copy"
touch "$stage/started"

executable="$(plutil -extract CFBundleExecutable raw "$copy/Contents/Info.plist")"
"$copy/Contents/MacOS/$executable" > "$stage/launch.log" 2>&1 &
pid=$!

waited=0
until [ -f "$config" ] && [ "$config" -nt "$stage/started" ]; do
    if [ "$waited" -ge "$wait" ]; then
        echo "No new $config after ${wait}s. The app's output:" >&2
        cat "$stage/launch.log" >&2
        exit 1
    fi
    sleep 2
    waited=$((waited + 2))
done
echo "The launch wrote its config cache to $user_data after ${waited}s."

kill "$pid" 2>/dev/null || true
pkill -f "$copy/Contents/" 2>/dev/null || true
pid=""
sleep 2

found=0
for file in config.php routes-v7.php events.php; do
    if [ -e "$copy/Contents/Resources/build/app/bootstrap/cache/$file" ]; then
        echo "The launch wrote bootstrap/cache/$file inside the bundle." >&2
        found=1
    fi
done
[ "$found" -eq 0 ] || exit 1

codesign --verify --deep --strict --verbose=2 "$copy"
echo "The signature is intact after a launch."
