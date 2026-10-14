# Sudo Play

```
$ sudo play --mood friday-deploy
```

That is the tagline, not a command. There is nothing to run in a terminal. The app shows that line inside its window, and it changes as you pick a genre and a mood.

A small macOS menu bar app that picks today's Spotify playlist for you, based on what you are coding and how you feel about it.

Pick a genre. Pick a developer mood. Press play.

The documentation lives at [sudoplay.dev](https://sudoplay.dev).

## Status

The first build is out: [1.0.0 beta 1](https://github.com/albertoarena/sudo-play/releases/tag/v1.0.0-beta.1), for Macs with Apple silicon. It is not signed yet. A signed and notarized build follows in a few days, as soon as Apple approves the developer account.

Follow the Status list in [docs/INSTRUCTIONS.md](docs/INSTRUCTIONS.md) to see how far along it is.

## How it works

You choose a genre, like `rock` or `lofi`, and a mood, like Friday Deploy or Debugging. The app suggests one playlist for today.

The pick is the same all day, and the same for everyone who chooses the same genre and mood. So if a playlist fits your afternoon, you can tell someone else how to find it. Tomorrow it changes.

Press Play and it opens in Spotify. A short line under the buttons says what is playing. If Spotify is closed, Sudo Play starts it first and then plays, which can take a few seconds. If Spotify is still starting after about 20 seconds, the line asks you to press Play again. If you chose "open only" or the web player in Settings, the playlist opens in Spotify or in your browser and you press play there, so the line says where it opened. You can cycle to another playlist with Next, or hit Surprise me and let it choose both for you.

It works from the keyboard too. Press Enter to play, `N` for Next, `S` for Surprise me and Esc to close the window. Tab moves between the genre chips, the mood chips and the buttons, and the arrow keys pick a chip. Screen readers read a mood by its name, without the emoji.

There is an optional daily reminder, so a playlist is waiting when you start work.

## Moods

| Mood | When |
|---|---|
| Shipped It | Something just went live |
| Coffee Needed | Slow start, low fuel |
| Blue Monday | Low energy, start of the week |
| Flow State | Deep focus, no distractions |
| Debugging | Calm and patient, long investigation |
| Friday Deploy | Brave, energetic, a bit risky |

Genres and moods live in the catalog, so adding one takes no code.

## Install

### Install the beta

> **Temporary.** This build is not signed yet. Sudo Play's Apple Developer account is waiting for Apple's approval, and a signed and notarized build follows in a few days. That build opens with no warning, and the steps below go away.

**You need** a Mac with Apple silicon, M1 or later, and the Spotify app or a browser. There is no Intel build yet.

1. **Download** [Sudo-Play-1.0.0-beta.1-arm64.dmg](https://github.com/albertoarena/sudo-play/releases/download/v1.0.0-beta.1/Sudo-Play-1.0.0-beta.1-arm64.dmg) (167 MB) from the [1.0.0 beta 1 release](https://github.com/albertoarena/sudo-play/releases/tag/v1.0.0-beta.1).
2. **Install.** Open the DMG and drag Sudo Play to Applications.

### Open it the first time

macOS cannot check who made an unsigned app, so it blocks the app the first time. You let it through once:

1. Open Sudo Play from Applications. macOS says **"Sudo Play.app" Not Opened**. Click **Done**, not Move to Bin.
2. Open **System Settings**, then **Privacy & Security**.
3. Scroll down to **Security**. Next to **"Sudo Play.app" was blocked to protect your Mac**, click **Open Anyway**.
4. A last dialog asks again. Click **Open Anyway**. Move to Bin is the highlighted button, so don't press Return.
5. Enter your password or use Touch ID if macOS asks.

Sudo Play starts, and its icon appears in the menu bar. From now on it opens like any other app.

Open Anyway only shows for about an hour after a blocked attempt. If you don't see it, open Sudo Play again, click Done, and look again.

If you would rather not, wait for the signed build, or [run it from source](#run-it-from-source).

### Check the download

This step is optional. In Terminal, run:

```
shasum -a 256 ~/Downloads/Sudo-Play-1.0.0-beta.1-arm64.dmg
```

It should print `8507c1af91e4f362376fb01bb72cb2512cbbf14ebfada94599b0795d36c52f1f`.

### Hear about the signed build

On this repository's GitHub page, choose **Watch**, then **Custom**, then **Releases**. GitHub tells you when the signed build is out.

## Run it from source

You need a Mac, PHP 8.4 with Composer, and Node 22 or later. The `.nvmrc` file pins Node 22, so `nvm use` picks it.

```bash
git clone https://github.com/albertoarena/sudo-play.git
cd sudo-play
composer setup
php artisan native:serve
```

`composer setup` installs the PHP and JavaScript dependencies, creates `.env` and the app key, sets up the local database and builds the frontend. Run it once.

`php artisan native:serve` starts the app. The first run also downloads Electron, so it takes a while. Stop it with Ctrl+C.

If you are changing the interface, run `composer native:dev` instead. It starts the app and the Vite dev server together, so your changes show up without a rebuild.

A copy of the catalog ships with the app, so it has playlists to suggest from the first run, even offline. To add one, see [Adding a playlist](#adding-a-playlist).

To run the tests and checks, see [CONTRIBUTING.md](CONTRIBUTING.md#changing-the-code).

## Adding a playlist

The catalog is one YAML file: `catalog/playlists.yaml`. Adding a playlist is a few lines and a pull request.

```yaml
- id: rock-friday-deploy-01
  title: Riffs for risky releases
  url: https://open.spotify.com/playlist/XXXXXXXXXXXXXXXXXXXXXX
  genre: rock
  moods: [friday-deploy, flow-state]
  curator: your-github-handle
```

Rules and examples are in [CONTRIBUTING.md](CONTRIBUTING.md). A bot checks your file when you open the pull request and tells you if something is wrong.

Playlists do not wait for a release. Once merged, yours reaches everyone within a day.

## Privacy

The app makes one network call, to download the catalog file. No accounts, no analytics, no tracking, and nothing about you leaves your Mac.

See [PRIVACY.md](PRIVACY.md).

## Contributing

Playlists, fixes and ideas are welcome. Start with [CONTRIBUTING.md](CONTRIBUTING.md). It explains how to add a playlist and how to run the checks. Forking to contribute needs no permission and keeps this project's name: [TRADEMARK.md](TRADEMARK.md) is about publishing your own version, not about pull requests.

## Documentation

- [sudoplay.dev](https://sudoplay.dev), the documentation site for the released app
- [CONTRIBUTING.md](CONTRIBUTING.md), how to add a playlist or change the code
- [PRIVACY.md](PRIVACY.md), what the app sends and stores
- [SECURITY.md](SECURITY.md), how to report a security problem
- [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md), what the app does
- [docs/INSTRUCTIONS.md](docs/INSTRUCTIONS.md), the build plan
- [docs/DECISIONS.md](docs/DECISIONS.md), why things are the way they are
- [docs/RELEASE.md](docs/RELEASE.md), how a release is cut

## Not affiliated with Spotify

This project is not affiliated with, endorsed by, or connected to Spotify. It uses no Spotify API and asks for no Spotify login. It only opens public playlist links that people have contributed.

Spotify is a trademark of Spotify AB.

## Licence

[MIT](LICENSE). Contributions are made under MIT too, see [CONTRIBUTING.md](CONTRIBUTING.md#licence-of-contributions).

The licence covers the code. The name, the app icon, the other art and the tagline are not part of it, and [TRADEMARK.md](TRADEMARK.md) says what you can do with them. Most things need no permission, including writing about Sudo Play and contributing.
