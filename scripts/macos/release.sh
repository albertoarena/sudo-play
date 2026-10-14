#!/usr/bin/env bash
#
# Signs, packages, notarizes and staples a Sudo Play build. See docs/RELEASE.md.
#
# Run it from a clean clone, after `php artisan native:build mac arm64` with
# CSC_IDENTITY_AUTO_DISCOVERY=false, so electron-builder leaves signing to us.
#
# Usage: scripts/macos/release.sh <version> [arch]
#   SUDO_PLAY_SIGN_IDENTITY  certificate name, "Developer ID Application" by default
#   SUDO_PLAY_NOTARY_PROFILE notarytool keychain profile, "sudo-play-notary" by default

set -euo pipefail

version="${1:?Usage: scripts/macos/release.sh <version> [arch]}"
arch="${2:-arm64}"
identity="${SUDO_PLAY_SIGN_IDENTITY:-Developer ID Application}"
profile="${SUDO_PLAY_NOTARY_PROFILE:-sudo-play-notary}"

root="$(cd "$(dirname "$0")/../.." && pwd)"
app="$root/dist/mac-$arch/Sudo Play.app"
out="$root/dist/release"
dmg="$out/Sudo-Play-$version-$arch.dmg"

[ -d "$app" ] || { echo "No app at $app. Build it first." >&2; exit 1; }

built="$(plutil -extract CFBundleShortVersionString raw "$app/Contents/Info.plist")"
[ "$built" = "$version" ] || { echo "The app is version $built, not $version." >&2; exit 1; }

echo "1. Sign the app"
node "$root/scripts/macos/sign.mjs" "$app"
codesign --verify --deep --strict --verbose=2 "$app"
codesign -d --entitlements - --xml "$app" 2>/dev/null | grep -q 'com.apple.security.automation.apple-events' \
    || { echo "The app is missing the Apple events entitlement." >&2; exit 1; }

echo "2. Make the DMG"
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
ditto "$app" "$stage/Sudo Play.app"
ln -s /Applications "$stage/Applications"
mkdir -p "$out"
rm -f "$dmg"
hdiutil create -quiet -volname "Sudo Play" -srcfolder "$stage" -format UDZO "$dmg"
codesign --sign "$identity" --timestamp "$dmg"

echo "3. Notarize (usually a few minutes)"
xcrun notarytool submit "$dmg" --keychain-profile "$profile" --wait --output-format json | tee "$out/notary.json"
grep -q '"status":"Accepted"' "$out/notary.json" \
    || { echo "Notarization was not accepted. Read the log with: xcrun notarytool log <id> --keychain-profile $profile" >&2; exit 1; }

echo "4. Staple and check"
xcrun stapler staple "$dmg"
xcrun stapler staple "$app"
spctl --assess --type open --context context:primary-signature --verbose=2 "$dmg"
spctl --assess --type execute --verbose=2 "$app"

echo
echo "Done: $dmg"
shasum -a 256 "$dmg"
