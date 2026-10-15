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
#   SUDO_PLAY_NOTARY_KEY     path to an App Store Connect .p8 key, used instead of the
#                            profile when set, with SUDO_PLAY_NOTARY_KEY_ID and
#                            SUDO_PLAY_NOTARY_ISSUER. release.yml uses this

set -euo pipefail

version="${1:?Usage: scripts/macos/release.sh <version> [arch]}"
arch="${2:-arm64}"
identity="${SUDO_PLAY_SIGN_IDENTITY:-Developer ID Application}"
profile="${SUDO_PLAY_NOTARY_PROFILE:-sudo-play-notary}"

if [ -n "${SUDO_PLAY_NOTARY_KEY:-}" ]; then
    notary_auth=(--key "$SUDO_PLAY_NOTARY_KEY" --key-id "${SUDO_PLAY_NOTARY_KEY_ID:?Set SUDO_PLAY_NOTARY_KEY_ID}" --issuer "${SUDO_PLAY_NOTARY_ISSUER:?Set SUDO_PLAY_NOTARY_ISSUER}")
else
    notary_auth=(--keychain-profile "$profile")
fi

root="$(cd "$(dirname "$0")/../.." && pwd)"
source "$root/scripts/macos/retry.sh"
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

# Submits a file to Apple and waits. Prints Apple's log when it is refused.
notarize() {
    local file="$1" result="$out/notary-$(basename "$1").json"
    xcrun notarytool submit "$file" "${notary_auth[@]}" --wait --output-format json | tee "$result"
    if ! grep -q '"status":"Accepted"' "$result"; then
        echo "Notarization of $(basename "$file") was not accepted. Apple's log:" >&2
        local id
        id="$(sed -n 's/.*"id":"\([^"]*\)".*/\1/p' "$result")"
        [ -n "$id" ] && xcrun notarytool log "$id" "${notary_auth[@]}" >&2
        exit 1
    fi
}

mkdir -p "$out"
stage="$(mktemp -d)"
trap 'hdiutil detach -quiet "$stage/mnt" 2>/dev/null || true; rm -rf "$stage"' EXIT

# The app is notarized and stapled on its own first, so the copy inside the
# DMG carries its ticket. Without it, a first launch with no network can be
# refused once the app is out of the DMG.
echo "2. Notarize and staple the app (usually a few minutes)"
ditto -c -k --keepParent "$app" "$stage/app.zip"
notarize "$stage/app.zip"
retry xcrun stapler staple "$app"

echo "3. Make the DMG"
mkdir "$stage/content"
ditto "$app" "$stage/content/Sudo Play.app"
ln -s /Applications "$stage/content/Applications"
rm -f "$dmg"
hdiutil create -quiet -volname "Sudo Play" -srcfolder "$stage/content" -fs APFS -format UDRW "$stage/rw.dmg"
mkdir "$stage/mnt"
hdiutil attach -quiet -nobrowse -mountpoint "$stage/mnt" "$stage/rw.dmg"
# The volume shows the app icon in Finder: .VolumeIcon.icns plus the custom icon flag
cp "$app/Contents/Resources/icon.icns" "$stage/mnt/.VolumeIcon.icns"
SetFile -a C "$stage/mnt"
hdiutil detach -quiet "$stage/mnt"
hdiutil convert -quiet "$stage/rw.dmg" -format UDZO -o "$dmg"
codesign --sign "$identity" --timestamp "$dmg"

echo "4. Notarize and staple the DMG"
notarize "$dmg"
retry xcrun stapler staple "$dmg"

echo "5. Check"
spctl --assess --type open --context context:primary-signature --verbose=2 "$dmg"
retry xcrun stapler validate "$dmg"
hdiutil attach -quiet -nobrowse -readonly -mountpoint "$stage/mnt" "$dmg"
retry xcrun stapler validate "$stage/mnt/Sudo Play.app"
spctl --assess --type execute --verbose=2 "$stage/mnt/Sudo Play.app"
[ -f "$stage/mnt/.VolumeIcon.icns" ] || { echo "The DMG has no volume icon." >&2; exit 1; }
hdiutil detach -quiet "$stage/mnt"

echo
echo "Done: $dmg"
shasum -a 256 "$dmg"
