# Sudo Play: Build Instructions for Claude CLI

Work through the milestones in order. Each milestone:

1. Read the related sections of `docs/REQUIREMENTS.md`
2. Write the tests listed (and any others you need), run them, confirm they fail
3. Implement until green, then refactor
4. Run the full check: `composer test && composer analyse && composer lint && npm run typecheck`
5. Commit, write a short summary (what was built, test count, open questions)
6. **Stop and wait for approval** before the next milestone

If something in the requirements is unclear or NativePHP does not support a feature, stop and ask. Do not invent a workaround silently.

Every choice the plan leaves open goes in `docs/DECISIONS.md` as one short entry, on the day it is made.

## Status

- [x] M0 Scaffold
- [x] M1 Catalog parsing and validation
- [x] M2 Daily rotation
- [x] M3 Storage, settings and sync
- [x] M4 Player
- [ ] M5 Menu bar UI
- [ ] M6 Settings window and daily reminder
- [ ] M7 Repository, CI and release

---

## M0. Scaffold

- New Laravel app with Inertia + Vue 3 + TypeScript + Tailwind
- Install NativePHP Desktop following the **current** official docs (check them first)
- Install Pest, Larastan (level 8), Pint
- Install Vitest and Vue Test Utils, so rule 1 in `AGENTS.md` holds for the Vue layer too
- Add scripts: `composer test`, `composer analyse`, `composer lint`, `npm run typecheck`, `npm test`
- Create the folder structure described in `AGENTS.md`
- Add `LICENSE` (MIT) and a placeholder `README.md`
- One smoke test: the home route returns the Inertia `MenuBar` page
- One smoke Vitest test, so the frontend harness is proven before M5 needs it
- `.github/workflows/ci.yml`, per requirements 12.3. Brought forward from M7, because every later milestone arrives as a pull request that needs checking independently of whatever the author reports

**Done when:** `php artisan native:serve` opens an empty app and all checks pass.

## M1. Catalog parsing and validation

Requirements: section 4.1 to 4.3.

- Readonly value objects: `Genre`, `Mood`, `Playlist`, `Catalog`
- `CatalogParser` turns YAML into a `Catalog` and a list of validation errors
- Spotify URL normaliser: link to playlist id, id to `spotify:` URI and to web URL

Tests (use fixtures in `tests/fixtures/catalogs/`):

- parses a valid catalog
- rejects wrong version and broken YAML as a whole
- skips a playlist with unknown genre, unknown mood, empty moods, bad URL, duplicate id, and reports why
- strips query strings from Spotify URLs
- strips the locale segment, so `intl-it/playlist/{id}?si=abc` normalises to `playlist/{id}`
- rejects an id that is not 22 letters and digits
- keeps a playlist that carries an unknown extra field, and logs it
- converts a URL to URI and back

Also add `php artisan catalog:validate {path?} {--release}` that exits non-zero on errors (used by CI later). With `--release` it also fails on zero playlists or any `TODO` field, per requirements 10.1.

## M2. Daily rotation

Requirements: section 5. Pure PHP, no framework.

- `DailyPicker` with methods for: today's pick, next candidate, fallback, surprise
- Inject the date and the random source so tests are deterministic. The date is the local calendar date, passed in, never read from `now()` inside the picker
- The no-repeat rule is part of the pure function, nothing is stored

Tests:

- same date + genre + mood returns the same playlist
- different date can return a different playlist
- today differs from yesterday when there are 2+ candidates
- consecutive days never land on the same index, for every candidate count from 2 up
- the step is coprime with the candidate count, and is 1 when there are 2 candidates
- one candidate returns that candidate every day
- next cycles through all candidates and wraps
- fallback order: same mood any genre, then same genre any mood, then null
- the fallback pick is deterministic over the fallback candidate list
- an empty catalog returns null and does not throw
- fallback result says which fallback was used (for the UI note)
- surprise only picks combinations with at least one candidate

## M3. Storage, settings and sync

Requirements: section 4.4. Settings land here, not in M6, because M4 and M5 both read them.

- Migrations and models to store the active catalog (simple tables or one JSON column, choose the simplest, log the choice in `docs/DECISIONS.md`)
- `SettingsStore` interface with one implementation and an in-memory fake for tests. It holds: default genre, default mood, last used genre, last used mood, player mode, reminder on/off, reminder time, weekdays only, launch at login, catalog URL. No settings UI yet, that is M6
- `CatalogSync`: download from the configured URL with Laravel HTTP client (5 second timeout, 1 MB limit), validate, replace the stored catalog only on success. Record the sync timestamp, which is the date shown in the footer
- The default catalog URL is the raw GitHub URL of the `catalog-live` branch, not `main`, see requirements 12.2. While the repository is private that URL returns 404, which the sync already treats as a network error, so M3 is fully buildable before the repository goes public
- A `CatalogSource` that resolves the bundled `catalog/playlists.yaml` through one method, because the path differs between `native:serve` and a packaged app. Decide and log it here, do not discover it in M7
- On first run with an empty database, load the bundled catalog
- Schedule sync daily, and run it after startup, never blocking the first render

Tests (use `Http::fake()`):

- first run loads the bundled catalog
- successful download replaces the catalog and updates the sync timestamp
- invalid download keeps the previous catalog and leaves the timestamp alone
- network error keeps the previous catalog
- timeout is handled like a network error
- a response over the size limit is rejected
- partially invalid download keeps valid playlists only
- a download that parses but has zero valid playlists keeps the previous catalog
- settings round trip through the store, and unknown keys return their default

## M4. Player

Requirements: section 6.

- `PlayerInterface` with `play(Playlist $playlist): PlayResult`
- `AppleScriptPlayer` (runs `osascript` through Laravel `Process`), `OpenAppPlayer` and `WebPlayer` (through NativePHP `Shell::openExternal`, wrapped in a small `UrlOpener` interface)
- `PlayerResolver` chooses the player from the M3 `SettingsStore` and falls back to web on failure
- The AppleScript call gets a timeout, because `tell application "Spotify"` hangs while Spotify launches

Tests (use `Process::fake()` and a fake `UrlOpener`):

- AppleScript player sends the right script with the right URI
- AppleScript failure falls back to web player and returns a message
- open-app player opens the `spotify:` URI
- web player opens the https URL
- resolver returns the player matching the setting

Tests cannot cover the macOS Automation permission prompt. Record the manual checks in `docs/QA.md` and run them at the end of this milestone: a real public playlist plays, a denied permission falls back to the web player, and Spotify not installed falls back too.

## M5. Menu bar UI

Requirements: section 7.1.

- Configure the menu bar in `NativeAppServiceProvider` (icon as template image, window size about 360 x 480)
- Inertia page `MenuBar.vue` with typed props (`resources/js/types`)
- Endpoints: get state, select genre/mood, play, next, surprise
- Remember last genre and mood through the M3 `SettingsStore`
- The Next cycle position lives in memory and resets on restart and on date change

Tests (Feature):

- state endpoint returns genres, moods, today's pick and catalog date
- selecting genre/mood updates the pick and is remembered
- play calls the player (fake bound in the container)
- next and surprise return the expected playlist
- fallback note is included when a fallback was used
- an empty catalog returns the empty state and the actions are disabled

Frontend: keep components small (`CommandLine.vue`, `ChipGroup.vue`, `PickCard.vue`). Strict TypeScript, no `any`. Each component gets a Vitest test written before it: the command line renders the current selection, the chip group emits the selected id, the pick card renders a pick and renders the empty state.

## M6. Settings window and daily reminder

Requirements: section 7.2 and 7.3. The settings store already exists from M3, this milestone adds the window and the reminder.

- Settings window opened from the menu bar footer, writing through the M3 `SettingsStore`
- `DailyReminder` decides IF and WHAT to notify (pure logic, testable), a thin adapter sends the NativePHP notification
- Launch at login through NativePHP if supported

Tests:

- reminder fires at the chosen time, not before
- weekdays-only skips Saturday and Sunday
- disabled reminder never fires
- notification text uses default mood emoji, label and today's pick title, from the plain daily pick and not a cycled Next result
- saving settings validates input (time format, known genre and mood, valid player mode, https catalog URL)

## M7. Repository, CI and release

Requirements: section 9.

- `README.md`, `CONTRIBUTING.md` and `PRIVACY.md` (plain English, short, no em dashes). `PRIVACY.md` is already drafted, review it against what the app actually does before release
- The About panel links `PRIVACY.md` and lists catalog curators, see requirements 7.2
- Seed `catalog/playlists.yaml` with the genres and moods from section 4.2 and an empty `playlists` list. Do not invent real Spotify playlist ids: the maintainer fills them in before the tag, and `--release` enforces it
- `CONTRIBUTING.md` carries the licensing paragraph from requirements 9. It has to be there before the repository goes public
- GitHub Actions, per requirements 12.3. `ci.yml` already exists from M0. Add the other two Linux ones, and add `albertoarena/envaudit` on `.env.example` to `ci.yml`:
  - `catalog.yml`: `php artisan catalog:validate` on PRs that touch `catalog/`
  - `catalog-publish.yml`: fast forwards `catalog-live` from `main` when `catalog/**` changes
- Turn on branch protection for `main` with `ci.yml` as a required check. Create the `catalog-live` branch
- Build, sign and notarize the DMG **by hand** on the maintainer's Mac, and write down what actually worked in `docs/RELEASE.md`. Name the four secrets, do not add them
- Only then write `release.yml` from those same commands, on a `v*` tag, on macOS, behind a protected environment
- Run the full `docs/QA.md` list against the signed DMG, not against `native:serve`

**Done when:** CI is green, branch protection is on, a signed DMG installs with no Gatekeeper warning, `docs/QA.md` passes, and `docs/RELEASE.md` describes a release someone else could repeat.

Note on order: the repository goes public at this milestone. Before flipping it, check that `CONTRIBUTING.md` has the licensing paragraph and that `catalog-live` exists, because both are harder to add once people are watching.

---

## After v1 (do not build now)

- Homebrew cask
- Landing page (Astro + Tailwind)
- Apple Music and YouTube Music links in the catalog
- Share today's pick as a link or image
