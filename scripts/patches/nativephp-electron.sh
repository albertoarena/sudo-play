#!/usr/bin/env bash
#
# Rebuilds patches/nativephp-electron-php-server-restart.patch from the edited
# files in vendor/. See docs/DECISIONS.md, 2026-10-22.
#
# The patch only touches resources/js/electron-plugin/dist, the compiled JS
# the build ships. To change it:
#
#   1. Edit the files under vendor/nativephp/electron/resources/js/electron-plugin/dist
#   2. Run this script
#   3. npm test, then commit the patch, patches.lock.json and the tests
#
# The script diffs vendor/ against a clean copy of the locked version from
# `composer archive`, writes the patch, relocks it, then reinstalls the
# package so Composer applies the patch itself. It fails if the result is not
# exactly the files you edited.
#
# Usage: scripts/patches/nativephp-electron.sh

set -euo pipefail

root="$(cd "$(dirname "$0")/../.." && pwd)"
package="nativephp/electron"
subdir="resources/js/electron-plugin/dist"
patch="patches/nativephp-electron-php-server-restart.patch"
vendor_dist="$root/vendor/$package/$subdir"

cd "$root"

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

version="$(composer show "$package" --locked --format=json | php -r 'echo json_decode(stream_get_contents(STDIN), true)["versions"][0];')"
echo "Clean copy of $package $version..."
composer archive "$package" "$version" --format=zip --dir="$work" --file=clean --no-plugins --quiet
unzip -q "$work/clean.zip" -d "$work/clean"

mkdir -p "$work/diff/a/$(dirname "$subdir")" "$work/diff/b/$(dirname "$subdir")"
cp -R "$work/clean/$subdir" "$work/diff/a/$subdir"
cp -R "$vendor_dist" "$work/diff/b/$subdir"
cp -R "$vendor_dist" "$work/edited"

# git diff --no-index exits 1 when the trees differ. A new file gets a
# "b/... b/..." header, which git apply refuses, so it is rewritten.
(cd "$work/diff" && git diff --no-index --no-prefix a b || true) \
    | sed -E 's#^diff --git b/([^ ]+) b/#diff --git a/\1 b/#; /^index [0-9a-f]+\.\.[0-9a-f]+/d' \
    > "$work/new.patch"

if [ ! -s "$work/new.patch" ]; then
    echo "vendor/$package/$subdir has no changes. Nothing to write." >&2
    exit 1
fi

(cd "$work/clean" && git init -q && git apply --check -p1 "$work/new.patch")

if cmp -s "$work/new.patch" "$patch"; then
    echo "$patch is already up to date."
    exit 0
fi

cp "$work/new.patch" "$patch"
echo "Wrote $patch."

composer patches-relock --quiet
composer reinstall "$package" --no-interaction --quiet

if ! diff -r "$work/edited" "$vendor_dist" > /dev/null; then
    echo "After reinstalling, vendor/$package/$subdir differs from your edits." >&2
    exit 1
fi

echo "Relocked and reinstalled: vendor/ matches your edits."
