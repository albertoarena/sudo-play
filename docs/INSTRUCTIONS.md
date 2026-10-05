# Sudo Play: Build Instructions for Claude CLI

Work through the milestones in order. Each milestone:

1. Read the related sections of `docs/REQUIREMENTS.md`
2. Write the tests listed (and any others you need), run them, confirm they fail
3. Implement until green, then refactor
4. Run the full check: `composer test && composer analyse && composer lint && npm run typecheck`
5. Commit, write a short summary (what was built, test count, open questions)
6. **Stop and wait for approval** before the next milestone

If something in the requirements is unclear or NativePHP does not support a feature, stop and ask. Do not invent a workaround silently.

---

## M0. Scaffold

- New Laravel app with Inertia + Vue 3 + TypeScript + Tailwind
- Install NativePHP Desktop following the **current** official docs (check them first)
- Install Pest, Larastan (level 8), Pint
- Add scripts: `composer test`, `composer analyse`, `composer lint`, `npm run typecheck`
- Create the folder structure described in `CLAUDE.md`
- Add `LICENSE` (MIT) and a placeholder `README.md`
- One smoke test: the home route returns the Inertia `MenuBar` page

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
- converts a URL to URI and back

Also add `php artisan catalog:validate {path?}` that exits non-zero on errors (used by CI later).

## M2. Daily rotation

Requirements: section 5. Pure PHP, no framework.

- `DailyPicker` with methods for: today's pick, next candidate, fallback, surprise
- Inject the date and the random source so tests are deterministic

Tests:

- same date + genre + mood returns the same playlist
- different date can return a different playlist
- today differs from yesterday when there are 2+ candidates
- one candidate returns that candidate every day
- next cycles through all candidates and wraps
- fallback order: same mood any genre, then same genre any mood, then null
- fallback result says which fallback was used (for the UI note)
- surprise only picks combinations with at least one candidate

## M3. Storage and sync

Requirements: section 4.4.

- Migrations and models to store the active catalog (simple tables or one JSON column, choose the simplest)
- `CatalogSync`: download from the configured URL with Laravel HTTP client, validate, replace the stored catalog only on success
- On first run with an empty database, load the bundled `catalog/playlists.yaml`
- Schedule sync daily, and run it at app startup

Tests (use `Http::fake()`):

- first run loads the bundled catalog
- successful download replaces the catalog
- invalid download keeps the previous catalog
- network error keeps the previous catalog
- partially invalid download keeps valid playlists only

## M4. Player

Requirements: section 6.

- `PlayerInterface` with `play(Playlist $playlist): PlayResult`
- `AppleScriptPlayer` (runs `osascript` through Laravel `Process`), `OpenAppPlayer` and `WebPlayer` (through NativePHP `Shell::openExternal`, wrapped in a small `UrlOpener` interface)
- `PlayerResolver` chooses the player from settings and falls back to web on failure

Tests (use `Process::fake()` and a fake `UrlOpener`):

- AppleScript player sends the right script with the right URI
- AppleScript failure falls back to web player and returns a message
- open-app player opens the `spotify:` URI
- web player opens the https URL
- resolver returns the player matching the setting

Check by hand at the end: run the app and play a real public playlist.

## M5. Menu bar UI

Requirements: section 7.1.

- Configure the menu bar in `NativeAppServiceProvider` (icon as template image, window size about 360 x 480)
- Inertia page `MenuBar.vue` with typed props (`resources/js/types`)
- Endpoints: get state, select genre/mood, play, next, surprise
- Remember last genre and mood

Tests (Feature):

- state endpoint returns genres, moods, today's pick and catalog date
- selecting genre/mood updates the pick and is remembered
- play calls the player (fake bound in the container)
- next and surprise return the expected playlist
- fallback note is included when a fallback was used

Frontend: keep components small (`CommandLine.vue`, `ChipGroup.vue`, `PickCard.vue`). Strict TypeScript, no `any`.

## M6. Settings and daily reminder

Requirements: section 7.2 and 7.3.

- Settings stored with NativePHP settings or a simple table (choose the simplest, explain the choice in the summary)
- Settings window opened from the menu bar footer
- `DailyReminder` decides IF and WHAT to notify (pure logic, testable), a thin adapter sends the NativePHP notification
- Launch at login through NativePHP if supported

Tests:

- reminder fires at the chosen time, not before
- weekdays-only skips Saturday and Sunday
- disabled reminder never fires
- notification text uses default mood emoji, label and today's pick title
- saving settings validates input (time format, known genre and mood, valid player mode, https catalog URL)

## M7. Repository, CI and release

Requirements: section 9.

- `README.md` and `CONTRIBUTING.md` (plain English, short, no em dashes)
- Seed `catalog/playlists.yaml` with the genres and moods from section 4.2 and **placeholder playlists marked `TODO`**. Do not invent real Spotify playlist ids: the maintainer fills them in.
- GitHub Actions:
  - `ci.yml`: Pest, Larastan, Pint, vue-tsc, `albertoarena/envaudit` on `.env.example`
  - `catalog.yml`: runs `php artisan catalog:validate` on PRs that touch `catalog/`
- Build a DMG with NativePHP. Document code signing and notarization steps in `docs/RELEASE.md` (do not add secrets)

**Done when:** CI is green and a local DMG installs and runs.

---

## After v1 (do not build now)

- Homebrew cask
- Landing page (Astro + Tailwind)
- Apple Music and YouTube Music links in the catalog
- Share today's pick as a link or image
