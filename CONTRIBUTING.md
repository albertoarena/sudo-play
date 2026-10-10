# Contributing

Thank you for helping. The easiest way to help is to add a playlist. It takes a few lines of YAML and one pull request, and you need no code.

## Adding a playlist

All playlists live in one file: [`catalog/playlists.yaml`](catalog/playlists.yaml). Add an entry under `playlists`:

```yaml
- id: rock-friday-deploy-01
  title: Riffs for risky releases
  url: https://open.spotify.com/playlist/XXXXXXXXXXXXXXXXXXXXXX
  genre: rock
  moods: [friday-deploy, flow-state]
  curator: your-github-handle
```

| Field | What to put |
|---|---|
| `id` | A unique lowercase slug: letters and digits with single hyphens. Use `{genre}-{mood}-{number}`, like `jazz-debugging-02` |
| `title` | A short title. It is shown in the app, so make it fun |
| `url` | The Spotify share link of the playlist. Copy it from Spotify with Share, then Copy link |
| `genre` | One genre id from the top of the file |
| `moods` | One or more mood ids from the top of the file |
| `curator` | Your GitHub handle, without the `@`. Optional. The app lists curators in its About panel |

You can paste the link as Spotify gives it to you. The app removes the `?si=...` part and a locale segment like `/intl-it/`.

### Rules

- **Public playlists only.** If it needs a login to see, it cannot go in.
- **One pull request can add several playlists.** You do not need one pull request for each.
- **Pick the right genre and mood.** Look at the mood table in the [README](README.md#moods). A playlist can fit more than one mood.
- **No duplicates.** Search the file for the playlist id before you add it. Each `id` must be unique too.
- **Keep it work-friendly.** Nothing you would not play in a shared office.

### Check your change

A check runs on your pull request and tells you if something is wrong, for example a mood that does not exist or a broken link format. Fix what it reports and push again.

To check before you push, run this from a clone of the repository:

```bash
composer install
php artisan catalog:validate
```

The check catches a malformed file. It cannot tell if a playlist is good, or if a link still works. A person reviews that.

### After the merge

Playlists do not wait for a release. When your pull request is merged, the catalog is published to the `catalog-live` branch, and every app downloads it within a day.

## Adding a genre or a mood

Genres and moods are defined at the top of `catalog/playlists.yaml`, so a new one needs no code. Open an issue first and explain why it is useful. A new genre or mood with no playlists only shows an empty state.

## Changing the code

Read [AGENTS.md](AGENTS.md) first. It has the stack, the architecture and the rules, and it applies to people as well as tools. In short:

- **Tests first.** Write a failing test, make it pass, then clean up. Pest for PHP, Vitest for Vue
- **No Spotify Web API**, no accounts, no analytics. The only network call is the catalog download
- Small commits in the `type: short subject` format, like `fix: keep the pick after a sync`
- **Colours come from the tokens** in `resources/css/app.css`, not from Tailwind palette classes. A test measures every pair against WCAG AA and fails on a palette class in a component
- Every change goes through a pull request

Run all the checks before you open a pull request. CI runs the same ones:

```bash
composer test
composer analyse
composer lint
npm test
npm run typecheck
```

The app itself runs only on macOS: `php artisan native:serve`.

## Licence of contributions

Sudo Play is released under the [MIT licence](LICENSE). By opening a pull request, you agree that your contribution is licensed under the MIT licence too.

Future versions of Sudo Play may be released under other terms. The MIT licence allows this, and contributions already released under MIT stay available under MIT.

## Not affiliated with Spotify

Sudo Play is not affiliated with Spotify. Please do not add the Spotify logo anywhere. Use the text "Open in Spotify".
