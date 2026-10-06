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
