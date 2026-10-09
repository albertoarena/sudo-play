# Privacy

Sudo Play is a desktop app that runs on your Mac. It has no accounts, no servers and no analytics.

## What the app sends

One request, to one place: it downloads the playlist catalog file.

By default that file is `catalog/playlists.yaml` from the `catalog-live` branch of this repository, served by GitHub. The request is a plain file download. It carries no identifier for you, no device id and no usage data. GitHub, like any web server, can see the IP address the request comes from. We never see it, because we run no server of our own.

The app downloads the catalog when it starts and once a day after that.

You can change the catalog URL in Settings, which forks and private catalogs need. If you point the app somewhere else, that server sees the request instead, and its own privacy terms apply.

There are no other network calls. No telemetry, no crash reporting, no update ping. New versions are published on GitHub, and the app does not check for them.

Some buttons, like "Add a playlist", Privacy and GitHub, open a web page. They open it in your default browser. That is your browser visiting a page, and the app sends nothing with it.

## What the app stores

Everything stays on your Mac:

- The catalog you last downloaded, in a local SQLite database
- Your settings: default genre and mood, player mode, reminder, launch at login, catalog URL
- The genre and mood you picked last, so the window opens on them
- The date of the last successful catalog download
- The date the daily reminder was last shown, so it is shown once a day at most
- A log file with warnings and errors, for example a catalog row that was skipped or a playlist that could not play

Nothing is synced anywhere. Deleting the app and its application support folder removes all of it.

## Spotify

The app does not use the Spotify API, and you never log in to Spotify through it. It only opens public playlist links, either in the Spotify app or in your browser.

Once a playlist opens, you are using Spotify, and Spotify's own privacy policy applies to what happens there. That part is outside this app.

Sudo Play is not affiliated with Spotify.

## macOS permissions

If you choose the autoplay player mode, macOS asks for permission to control the Spotify app. That is a standard Automation permission. It lets this app send a play command to Spotify on the same machine. It gives us no data, and you can revoke it in System Settings under Privacy and Security.

The app asks for nothing else: no contacts, no files, no location, no microphone.

## Children

The app collects nothing, so there is nothing to collect from anyone of any age.

## Changes

This file lives in the repository. Changes to it are visible in the git history, like any other change.

## Questions

Open an issue on GitHub.
