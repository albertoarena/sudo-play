# Sudo Play

```
$ sudo play --mood friday-deploy
```

That is the tagline, not a command. There is nothing to run in a terminal. The app shows that line inside its window, and it changes as you pick a genre and a mood.

A small macOS menu bar app that picks today's Spotify playlist for you, based on what you are coding and how you feel about it.

Pick a genre. Pick a developer mood. Press play.

## Status

The app is built and works in development. There is no release yet: the first one waits for a signed and notarized build.

Follow the Status list in [docs/INSTRUCTIONS.md](docs/INSTRUCTIONS.md) to see how far along it is.

## How it works

You choose a genre, like `rock` or `lofi`, and a mood, like Friday Deploy or Debugging. The app suggests one playlist for today.

The pick is the same all day, and the same for everyone who chooses the same genre and mood. So if a playlist fits your afternoon, you can tell someone else how to find it. Tomorrow it changes.

Press Play and it opens in Spotify. You can cycle to another playlist with Next, or hit Surprise me and let it choose both for you.

It works from the keyboard too. Press Enter to play, `N` for Next, `S` for Surprise me and Esc to close the window. Tab moves between the genre chips, the mood chips and the buttons, and the arrow keys pick a chip.

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

Nothing to install yet. The first release will be a signed DMG attached to a GitHub Release, so it opens without a security warning.

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

Playlists, fixes and ideas are welcome. Start with [CONTRIBUTING.md](CONTRIBUTING.md). It explains how to add a playlist and how to run the checks.

## Documentation

- [CONTRIBUTING.md](CONTRIBUTING.md), how to add a playlist or change the code
- [PRIVACY.md](PRIVACY.md), what the app sends and stores
- [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md), what the app does
- [docs/INSTRUCTIONS.md](docs/INSTRUCTIONS.md), the build plan
- [docs/DECISIONS.md](docs/DECISIONS.md), why things are the way they are
- [docs/RELEASE.md](docs/RELEASE.md), how a release is cut

## Not affiliated with Spotify

This project is not affiliated with, endorsed by, or connected to Spotify. It uses no Spotify API and asks for no Spotify login. It only opens public playlist links that people have contributed.

Spotify is a trademark of Spotify AB.

## Licence

[MIT](LICENSE). Contributions are made under MIT too, see [CONTRIBUTING.md](CONTRIBUTING.md#licence-of-contributions).
