# Release

How to cut a release of Sudo Play. Filled in properly during M7, after the first signed build is done by hand. Until then this is a checklist and a list of what is needed.

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

Four repository secrets. Names are indicative until M7 confirms them against the real workflow.

| Secret | What it is |
|---|---|
| `APPLE_CERT_P12_BASE64` | Developer ID Application certificate, exported as .p12, base64 encoded |
| `APPLE_CERT_PASSWORD` | Password used when exporting that .p12 |
| `KEYCHAIN_PASSWORD` | Any strong value, used to create a temporary keychain on the runner |
| `APPLE_API_KEY` | App Store Connect API key used by `notarytool` |

An App Store Connect API key is used rather than an Apple ID and an app specific password, because the key is scoped and can be revoked without touching the Apple account.

## First release, by hand

Do this once on a Mac before automating anything. Notarization fails in dull ways and is much faster to debug locally.

1. Build the app and the DMG with NativePHP
2. Sign with the Developer ID Application certificate
3. Submit to Apple with `notarytool` and wait for the result
4. Staple the ticket to the DMG
5. Verify with `spctl` that Gatekeeper accepts it
6. Install on a Mac that has never run the app and confirm no warning appears
7. Create the GitHub Release and attach the DMG

Write the exact commands here as you go. That record is the point of this document.

## Unsigned beta, before the Apple account

`v1.0.0-beta.1` shipped unsigned on 2026-10-14, while the Apple Developer account waited for approval. What worked, on an Apple silicon Mac:

1. Clone `main` into a fresh folder, so no local `.env`, database or private note ends up in the bundle
2. `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`
3. Copy `.env.example` to `.env`, set `APP_ENV=production` and `APP_DEBUG=false`, run `php artisan key:generate`, and add `NATIVEPHP_APP_VERSION`, `NATIVEPHP_APP_AUTHOR`, `NATIVEPHP_APP_COPYRIGHT`, `NATIVEPHP_APP_DESCRIPTION` and `NATIVEPHP_APP_WEBSITE`
4. `php artisan native:build mac arm64`. With no certificate, electron-builder skips signing and the notarize hook fails. The build still finishes, but the app's signature is broken, and a downloaded copy would say it is damaged, with no way to open it
5. Sign it ad hoc: `codesign --force --deep --sign - "dist/mac-arm64/Sudo Play.app"`, then check with `codesign --verify --deep --strict`
6. Make a new DMG from the fixed app: a folder with the app and a link to `/Applications`, then `hdiutil create -volname "Sudo Play" -srcfolder <folder> -format UDZO Sudo-Play-<version>-arm64.dmg`
7. Create a draft pre-release pinned to the built commit, attach the DMG, download it from the draft and open it with Open Anyway before publishing

An ad hoc signature gets the normal "cannot verify" prompt and Open Anyway in System Settings. The signed build replaces steps 4 to 6 with the steps above.

## Automating it afterwards

`release.yml` turns the steps above into a workflow. On a `v*` tag, on a macOS runner, behind a protected environment so a stray tag cannot publish or reach the secrets.

macOS runners bill at ten times the Linux rate, which is why only this workflow uses one.

## Hotfixes

Branch from the tag, not from `main`, so unreleased work is not picked up.

```
git switch -c hotfix/1.0.1 v1.0.0
# fix, pull request, merge
git tag v1.0.1
```

Then merge the hotfix branch back into `main` and delete it.
