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
- `url` must be an `https://open.spotify.com/playlist/{id}` link (query string allowed and stripped)
- `genre` and each `moods` entry must reference existing ids
- Invalid playlists are skipped and logged, valid ones are still loaded
- If the whole file is invalid (bad YAML, wrong version), keep the last good catalog

### 4.4 Sync

- A copy of the catalog is bundled in the app as offline fallback
- At startup and once a day, the app downloads the latest file from a configurable URL (default: raw GitHub URL of `main`)
- The parsed catalog is stored in SQLite and replaced only after a successful validation
- No other network calls

## 5. Daily pick (rotation)

- Input: date, genre, mood, list of playlists
- Candidates: playlists with that genre that include that mood
- The pick is **deterministic per day**: same date + genre + mood always gives the same playlist (so users can share "today's pick")
- Algorithm: sort candidates by id, index = `crc32("{date}|{genre}|{mood}") % count`
- If there is more than one candidate, today's pick must differ from yesterday's pick
- **Next** button: cycles through the other candidates for today
- **Fallback** when no candidate: same mood any genre, then same genre any mood. The UI shows a short note ("No rock playlist for Debugging yet, here is a jazz one. Add one on GitHub!")
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

### 7.2 Settings window

- Default genre and mood
- Player mode (section 6)
- Daily reminder: on/off, time (default 09:00), weekdays only (default on)
- Launch at login: on/off
- Catalog URL (advanced, for forks), button "Update catalog now"
- About: version, GitHub link, "Not affiliated with Spotify"

### 7.3 Daily reminder

- Native macOS notification at the chosen time:
  "☕ Coffee Needed? Today's pick is ready: {title}"
  (uses the default mood and genre)
- Clicking the notification opens the menu bar window, if NativePHP supports notification click events. If not, the notification is informational only.

## 8. Non-functional requirements

- macOS 13+, Apple Silicon and Intel if NativePHP supports both
- App starts and shows the menu bar icon in under 3 seconds on a normal Mac
- Works offline with the bundled or cached catalog
- No personal data leaves the machine
- MIT license
- All UI text in English, plain language, no em dashes

## 9. Repository extras

- `README.md`: what it is, screenshot or GIF, install, how to add a playlist, "not affiliated with Spotify"
- `CONTRIBUTING.md`: rules for adding playlists (public Spotify playlists only, one PR can add several, correct genre and mood, no duplicates, keep it work-friendly)
- GitHub Action that validates `catalog/playlists.yaml` on every PR, using the same validator as the app (`php artisan catalog:validate`)
- GitHub Action for tests, Larastan, Pint, vue-tsc, and `albertoarena/envaudit` on `.env.example`

## 10. Success criteria for v1

- A developer can install the DMG, pick genre + mood, and hear music in two clicks
- A contributor can add a playlist with a single YAML edit, and CI tells them if it is wrong
