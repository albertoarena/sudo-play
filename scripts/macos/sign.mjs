// Signs a built Sudo Play.app with the Developer ID and the hardened runtime.
//
// NativePHP has no setting for entitlements or Info.plist keys, so the build
// is made unsigned and this script finishes it: it applies info.json to the
// Info.plist, then signs every binary in the bundle with entitlements.plist.
// See docs/RELEASE.md.
//
// Usage: node scripts/macos/sign.mjs "<path>/Sudo Play.app"
// SUDO_PLAY_SIGN_IDENTITY picks the certificate, "Developer ID Application" by default.

import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');
const app = process.argv[2];

if (!app || !app.endsWith('.app')) {
    console.error('Usage: node scripts/macos/sign.mjs "<path>/Sudo Play.app"');
    process.exit(1);
}

const plist = join(app, 'Contents', 'Info.plist');
const info = JSON.parse(readFileSync(join(here, 'info.json'), 'utf8'));
const entitlements = join(here, 'entitlements.plist');
const identity = process.env.SUDO_PLAY_SIGN_IDENTITY || 'Developer ID Application';

const plutil = (...args) => execFileSync('plutil', args, { encoding: 'utf8' }).trim();

const bundleId = plutil('-extract', 'CFBundleIdentifier', 'raw', plist);

if (bundleId !== info.bundle_id) {
    console.error(`Bundle id is ${bundleId}, expected ${info.bundle_id}. Set NATIVEPHP_APP_ID and build again.`);
    process.exit(1);
}

for (const [key, value] of Object.entries(info.set)) {
    plutil('-replace', key, '-string', value, plist);
}

for (const key of info.remove) {
    try {
        plutil('-remove', key, plist);
    } catch {
        // Not there, nothing to remove.
    }
}

const keys = Object.keys(JSON.parse(plutil('-convert', 'json', '-o', '-', plist)));
const unexpected = keys.filter((key) => key.endsWith('UsageDescription') && !(key in info.set));

if (unexpected.length > 0) {
    console.error(`Unexpected usage strings: ${unexpected.join(', ')}. Add them to "remove" in scripts/macos/info.json.`);
    process.exit(1);
}

const require = createRequire(join(root, 'vendor/nativephp/electron/resources/js/package.json'));
const { signAsync } = require('@electron/osx-sign');

await signAsync({
    app,
    identity,
    platform: 'darwin',
    type: 'distribution',
    optionsForFile: () => ({ hardenedRuntime: true, entitlements }),
});

console.log(`Signed ${app} as ${identity}`);
