# Sudo Play: Requirements (v1)

## 1. Goal

A small, fun, open-source macOS menu bar app that suggests a daily Spotify playlist to developers, based on genre and a developer mood. The main goal of v1 is to attract interest (GitHub stars, contributors, shares), not revenue.

## 2. Users

Developers on macOS who listen to Spotify while coding. They want one click to get music that fits how they feel today.

## 3. Out of scope for v1

- Spotify login or any use of the Spotify Web API
- Windows and Linux builds
- Apple Music, YouTube Music (the catalog format must allow adding them later)
- User accounts, cloud sync, analytics
- AI-generated playlists

## 4. Catalog

The catalog is a YAML file in the repository: `catalog/playlists.yaml`. The community adds playlists through pull requests.

### 4.1 Format

```yaml
version: 1

genres:
  - id: classical
    label: Classical
  - id: rock
    label: Rock

moods:
  - id: friday-deploy
    label: Friday Deploy
    emoji: "🚀"
    description: Shipping to production on a Friday. Be brave.

playlists:
  - id: rock-friday-deploy-01          # unique slug
    title: Riffs for risky releases
    url: https://open.spotify.com/playlist/XXXXXXXXXXXXXXXXXXXXXX
    genre: rock                        # must exist in genres
    moods: [friday-deploy, flow-state] # each must exist in moods, at least one
    curator: github-handle             # optional
```

### 4.2 Initial genres and moods

Genres: `classical`, `rock`, `pop`, `indie`, `electronic`, `jazz`, `lofi`.

Moods:

| id | Label | Meaning |
|----|-------|---------|
| `shipped-it` | Shipped It | Happy, something just went live |
| `coffee-needed` | Coffee Needed | Tired, slow start |
| `blue-monday` | Blue Monday | Low energy, start of the week |
| `flow-state` | Flow State | Deep focus, no distractions |
| `debugging` | Debugging | Calm and patient, long investigation |
| `friday-deploy` | Friday Deploy | Brave, energetic, a bit risky |

Genres and moods are defined in the catalog, so new ones need no code change.

### 4.3 Validation rules

- `version` must be `1`
- Genre, mood and playlist ids are unique lowercase slugs
- `url` must be an `https://open.spotify.com/playlist/{id}` link. A query string is allowed and stripped. An optional locale segment is allowed and stripped, so `https://open.spotify.com/intl-it/playlist/{id}?si=abc` normalises to `https://open.spotify.com/playlist/{id}`
- `{id}` is 22 characters, letters and digits only
- `genre` and each `moods` entry must reference existing ids
- Invalid playlists are skipped and logged, valid ones are still loaded
- Unknown fields on a playlist are ignored and logged, never a reason to skip it, so a later format can add fields without breaking older installs
- If the whole file is invalid (bad YAML, wrong version), keep the last good catalog
- If a file parses but ends with zero valid playlists, keep the last good catalog

### 4.4 Sync

- A copy of the catalog is bundled in the app as offline fallback
- At startup and once a day, the app downloads the latest file from a configurable URL (default: raw GitHub URL of `main`)
- The startup sync never blocks the UI. The window renders from the stored catalog first, the sync runs after, and the view refreshes if the catalog changed
- The download uses a short timeout (5 seconds) and a size limit (1 MB). A timeout is treated like a network error
- The parsed catalog is stored in SQLite and replaced only after a successful validation
- The footer date is the timestamp of the last successful sync, recorded by the app. The YAML has no date field, so contributors never have to remember to bump one
- No other network calls

## 5. Daily pick (rotation)

- Input: date, genre, mood, list of playlists
- Candidates: playlists with that genre that include that mood
- "Date" always means the local calendar date on the user's Mac, format `YYYY-MM-DD`
- The pick is **deterministic per day**: same date + genre + mood + candidate list always gives the same playlist (so users can share "today's pick")
- Algorithm: sort candidates by id (byte order), then
  - `index = crc32("{date}|{genre}|{mood}") % count`
  - `yesterdayIndex = crc32("{yesterday}|{genre}|{mood}") % count`, computed over the **same** candidate list
  - if `count > 1` and `index === yesterdayIndex`, use `(index + 1) % count`
- The rule stays a pure function. Nothing about yesterday is stored, so two users with the same catalog always see the same pick
- **Next** button: cycles through the other candidates for today, in the same sorted order, wrapping around. The position is in memory only: it resets when the app restarts and when the local date changes
- **Fallback** when no candidate: same mood any genre, then same genre any mood. The same deterministic rule runs over the fallback candidate list. The UI shows a short note ("No rock playlist for Debugging yet, here is a jazz one. Add one on GitHub!")
- If both fallbacks are empty the pick is null. The UI shows an empty state with a link to the contributing page, and Play is disabled
- **Surprise me**: random genre and mood with at least one candidate

## 6. Playing

The user chooses the player mode in settings:

1. **Spotify app, autoplay** (default): runs AppleScript `tell application "Spotify" to play track "spotify:playlist:{id}"`. Starts playback directly.
2. **Spotify app, open only**: opens the `spotify:playlist:{id}` URI. The user presses play.
3. **Web player**: opens the `https://open.spotify.com/playlist/{id}` URL in the default browser.

If autoplay fails (Spotify not installed, AppleScript error), fall back to the web player and show a short message. macOS may ask permission to control Spotify the first time: the app explains this in one line.

## 7. User interface

### 7.1 Menu bar window (main)

- Menu bar icon (template image, works in light and dark mode)
- Click opens a small window (about 360 x 480)
- Terminal-style look: monospace font for the header line, Tailwind for the rest
- Header shows a live command line that changes with the selection:
  `$ sudo play --genre rock --mood friday-deploy`
- Genre chips and mood chips (single select each), mood chips show emoji
- Today's pick card: title, genre, mood, curator (if any)
- Buttons: **Play** (primary), **Next**, **Surprise me**
- Footer: catalog last updated date, link "Add a playlist" (opens the GitHub contributing page), Settings, Quit
- Last selected genre and mood are remembered
- Empty state when there is no pick at all: a short line ("No playlists in the catalog yet"), the "Add a playlist" link, and Play, Next and Surprise me disabled

### 7.2 Settings window

- Default genre and mood
- Player mode (section 6)
- Daily reminder: on/off, time (default 09:00), weekdays only (default on)
- Launch at login: on/off
- Catalog URL (advanced, for forks), button "Update catalog now"
- About: version, GitHub link, "Not affiliated with Spotify"
- About also shows: a one line privacy summary with a link to `PRIVACY.md` on GitHub, and a Credits section listing the curators found in the current catalog plus a link to the GitHub contributors page. Curators come from the catalog, so the list updates with a sync and needs no release

### 7.3 Daily reminder

- Native macOS notification at the chosen time:
  "☕ Coffee Needed? Today's pick is ready: {title}"
  (uses the default mood and genre, and the plain daily pick, never a cycled Next result)
- Clicking the notification opens the menu bar window, if NativePHP supports notification click events. If not, the notification is informational only.

## 8. Non-functional requirements

- macOS 13+, Apple Silicon and Intel if NativePHP supports both
- App starts and shows the menu bar icon in under 3 seconds on a normal Mac
- Works offline with the bundled or cached catalog
- No personal data leaves the machine
- MIT license
- All UI text in English, plain language, no em dashes

## 9. Repository extras

- `README.md`: what it is, screenshot or GIF, install, how to add a playlist, a short privacy line linking to `PRIVACY.md`, "not affiliated with Spotify"
- `PRIVACY.md`: what the app sends and stores, in plain English. One network call, no accounts, no analytics. Linked from the README and from the About panel
- `CONTRIBUTING.md`: rules for adding playlists (public Spotify playlists only, one PR can add several, correct genre and mood, no duplicates, keep it work-friendly)
- GitHub Action that validates `catalog/playlists.yaml` on every PR, using the same validator as the app (`php artisan catalog:validate`)
- GitHub Action for tests, Larastan, Pint, vue-tsc, and `albertoarena/envaudit` on `.env.example`

## 10. Success criteria for v1

- A developer can install the DMG, pick genre + mood, and hear music in two clicks
- A contributor can add a playlist with a single YAML edit, and CI tells them if it is wrong

### 10.1 Release gate

The repository ships `catalog/playlists.yaml` with genres and moods but an empty `playlists` list, because real Spotify playlist ids are chosen by the maintainer and are never invented by a tool. A build is therefore not releasable until the list is filled.

- `php artisan catalog:validate --release` fails if the catalog has zero playlists or if any field still contains `TODO`
- The release workflow runs that command. Normal PR validation does not, so an empty catalog on `main` is fine during development
- v1 cannot be tagged until it passes

## 11. Designed for later

v1 does not build any of this. These are the seams that must stay intact, so the work is possible later without a rewrite. If a milestone would break one, stop and ask.

### 11.1 Per user preferences

Later we may want separate preferences for separate people on one Mac.

- Every read and write of a setting goes through `SettingsStore`. No controller, player, picker or Vue page touches a settings facade or a settings table directly
- The store is a flat key to value interface. A later implementation can scope keys by profile without any caller changing
- Nothing else in the app holds user state. The picker is pure, the Next position is transient

### 11.2 A paid or private catalog

Later we may want a second catalog that is not the public one.

- The catalog URL is already a setting, not a constant, so the app can point anywhere
- `CatalogSync` works on one source in v1, but takes its source as a dependency rather than reading config inline, so a list of sources is an additive change
- The format carries `version`, and a catalog with an unknown version is rejected in full and the last good one is kept. An old app meeting a new catalog degrades safely instead of half parsing it
- Unknown fields on a playlist are ignored and logged, not rejected, so a later `tier` or `source` field does not break older installs

Note that a paid tier conflicts with the current product rules in `AGENTS.md`: no accounts, no network calls beyond the catalog, no personal data leaving the machine. Those rules would have to change first. This section only promises that the code will not be in the way.

### 11.3 Other players

Apple Music and YouTube Music are out of scope, and section 3 already says the format must allow them.

- `PlayerInterface` takes a playlist and returns a result. Nothing outside the `Player/` namespace knows what Spotify is
- The URL normaliser is Spotify specific and stays behind its own class
