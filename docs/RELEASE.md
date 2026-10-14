# Release

How to cut a release of Sudo Play. The first signed build was made by hand on 2026-10-14, and the steps below are what worked.

Never put a secret in this file.

## What a release is

A git tag, `vMAJOR.MINOR.PATCH`, on a commit on `main`. The version shown in the About panel comes from that tag and is injected at build time. Nothing commits a version bump.

Playlist changes are not releases. They reach users through the `catalog-live` branch and the daily sync. Only code changes get a tag.

## Before tagging

- `main` is green and branch protection is satisfied
- `php artisan catalog:validate --release` passes, which means the catalog has real playlists and no `TODO` left
- `docs/QA.md` passes against a locally built DMG
- `PRIVACY.md` still matches what the app actually does, in particular the list of network calls

## Apple account and secrets

Signing and notarization need a paid Apple Developer account.

`release.yml` reads four secrets and two variables from the **`release` environment**, never from the repository, so only an approved release job can reach them. A local release uses the keychain instead, see below.

| Secret | What it is |
|---|---|
| `APPLE_CERT_P12_BASE64` | The Developer ID Application certificate and its private key, exported as .p12, base64 encoded |
| `APPLE_CERT_PASSWORD` | The password used when exporting that .p12 |
| `KEYCHAIN_PASSWORD` | Any strong value, used to create a temporary keychain on the runner |
| `APPLE_API_KEY` | The App Store Connect `.p8` key used by `notarytool`, base64 encoded |

| Variable | What it is |
|---|---|
| `APPLE_API_KEY_ID` | The key's id, as in its file name `AuthKey_<id>.p8` |
| `APPLE_API_ISSUER` | The issuer id shown above the Team Keys table |

The two ids are not secret on their own: they only work with the `.p8` file.

An App Store Connect API key is used rather than an Apple ID and an app specific password, because the key is scoped and can be revoked without touching the Apple account.

## Signed release, on a Mac

This is how `v1.0.0` was made on 2026-10-14, on an Apple silicon Mac with the Xcode command line tools. No full Xcode is needed.

### One-time setup

1. **Certificate.** Make a private key and a signing request outside any repository, then upload the request at developer.apple.com, Certificates, **Developer ID Application**, profile type **G2 Sub-CA** (the previous Sub-CA expires on 1 February 2027):
   ```
   openssl genrsa -out developer-id.key 2048
   openssl req -new -key developer-id.key -out developer-id.csr -subj "/emailAddress=<email>/CN=<name>/C=<country>"
   ```
2. Import the key, the downloaded certificate and Apple's G2 intermediate into the login keychain:
   ```
   curl -O https://www.apple.com/certificateauthority/DeveloperIDG2CA.cer
   security import DeveloperIDG2CA.cer -k ~/Library/Keychains/login.keychain-db
   security import developer-id.key -k ~/Library/Keychains/login.keychain-db -T /usr/bin/codesign
   security import developerID_application.cer -k ~/Library/Keychains/login.keychain-db
   security find-identity -v -p codesigning
   ```
   The last command must list `Developer ID Application: <name> (<team id>)`. The first signing asks for the login password: choose **Always Allow**, or it asks once per file.
3. **Notarization key.** At appstoreconnect.apple.com, Users and Access, Integrations, Team Keys, make a key with **Developer** access. The `.p8` file downloads once only. Store it in the keychain:
   ```
   xcrun notarytool store-credentials sudo-play-notary --key AuthKey_<key id>.p8 --key-id <key id> --issuer <issuer id>
   ```

Keep the private key and the `.p8` file out of every repository. The certificate expires after about a year: signed builds keep working, but a new release then needs a new certificate.

### Each release

1. Clone `main` into a fresh folder, so no local `.env`, database or private note ends up in the bundle
2. Use the Node version in `.nvmrc` (`nvm use`), then `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`
3. Copy `.env.example` to `.env`, set `APP_ENV=production` and `APP_DEBUG=false` (debug on opens the inspector on every window), run `php artisan key:generate`, and add `NATIVEPHP_APP_VERSION`, `NATIVEPHP_APP_AUTHOR`, `NATIVEPHP_APP_COPYRIGHT`, `NATIVEPHP_APP_DESCRIPTION` and `NATIVEPHP_APP_WEBSITE`
4. Build unsigned, so electron-builder leaves signing to us:
   ```
   CSC_IDENTITY_AUTO_DISCOVERY=false php artisan native:build mac arm64
   ```
   The NativePHP notarize hook prints a warning and skips. That is expected
5. Sign, package, notarize and staple:
   ```
   scripts/macos/release.sh <version> arm64
   ```
   It checks the bundle id and version, applies `scripts/macos/info.json` to the Info.plist, signs every binary with the hardened runtime and `scripts/macos/entitlements.plist`, makes and signs the DMG, waits for notarization, staples the ticket and checks both with `spctl`. It ends with the DMG's path and SHA-256. The first notarization of a new account took about half an hour, later ones are usually minutes
6. Test the DMG as a download: `xattr -w com.apple.quarantine "0081;$(printf %x $(date +%s));Safari;" <dmg>`, reset the Spotify permission with `tccutil reset AppleEvents com.albertoarena.sudoplay`, install, open, press Play. Expect only the "downloaded from the internet" question, then **"Sudo Play.app" wants access to control "Spotify.app"** with the line from `info.json`, then playback in the Spotify app, not the browser
7. Tag the commit, create the GitHub Release, attach the DMG and put the SHA-256 in the notes

### Why the signing is ours, not NativePHP's

Notarization requires the hardened runtime. Under it, macOS refuses to let an app control Spotify unless the app has the `com.apple.security.automation.apple-events` entitlement and an `NSAppleEventsUsageDescription`. NativePHP has neither and no setting to add them, and its files in `vendor/` are overwritten on update. Without them, Play is refused silently and falls back to the browser, which looks fine unless tested. The same step removes the camera, microphone, Bluetooth and folder usage strings that NativePHP and Electron add, because Sudo Play uses none of them. `tests/Feature/Native/MacSigningTest.php` keeps these files in step with NativePHP and Electron.

## Unsigned beta, before the Apple account

`v1.0.0-beta.1` shipped unsigned on 2026-10-14, while the Apple Developer account waited for approval. It was built like a release, steps 1 to 4, then signed ad hoc instead of step 5: `codesign --force --deep --sign - "dist/mac-arm64/Sudo Play.app"`, with the DMG made by `hdiutil` as in `release.sh`. Without that ad hoc signature, a downloaded copy says it is damaged, with no way to open it. With it, macOS shows the "cannot verify" prompt and Open Anyway in System Settings.

## Automating it afterwards

`.github/workflows/release.yml` does steps 1 to 5 of "Each release" on a `v*` tag, on a `macos-15` runner (Apple silicon), and attaches the DMG to a **draft** release with its SHA-256. Steps 6 and 7 stay with the maintainer: download the draft's DMG, test it, write the notes and publish.

- The job runs in the `release` environment, which needs the maintainer's approval, so a stray tag cannot reach the secrets or make a release
- Run it by hand from the Actions tab with an existing tag for a **dry run**: it builds, signs and notarizes, keeps the DMG as a workflow artifact for 7 days, and creates no release
- A version with a dash, such as `1.1.0-beta.1`, becomes a pre-release
- Signing and notarization go through the same `scripts/macos/release.sh` as a local release, with the `.p8` key passed directly instead of a keychain profile
- The app comes from the tag, `scripts/macos` from the commit the workflow runs from, so a dry run of an older tag signs with today's scripts
- First dry run, on `v1.0.1`, passed on 2026-10-14: signed, notarized and stapled in 9 minutes, approval included (the job itself took 7 and a half)

macOS runners are free for public repositories but bill at ten times the Linux rate on private ones, which is why only this workflow uses one.

### Setting up the secrets

Once, on the maintainer's Mac, from the folder with the key and the certificate:

```
openssl x509 -inform der -in developerID_application.cer -out developer-id.pem
openssl pkcs12 -export -legacy -inkey developer-id.key -in developer-id.pem -out developer-id.p12 -name "Developer ID Application"
base64 -i developer-id.p12 | gh secret set APPLE_CERT_P12_BASE64 --env release
gh secret set APPLE_CERT_PASSWORD --env release
gh secret set KEYCHAIN_PASSWORD --env release
base64 -i AuthKey_<key id>.p8 | gh secret set APPLE_API_KEY --env release
gh variable set APPLE_API_KEY_ID --env release --body <key id>
gh variable set APPLE_API_ISSUER --env release --body <issuer id>
```

`-legacy` keeps the .p12 readable by the macOS `security` tool, which does not read OpenSSL 3's default encryption. The two `gh secret set` lines without a pipe ask for the value, so it never lands in the shell history.

## Hotfixes

Branch from the tag, not from `main`, so unreleased work is not picked up.

```
git switch -c hotfix/1.0.1 v1.0.0
# fix, pull request, merge
git tag v1.0.1
```

Then merge the hotfix branch back into `main` and delete it.
