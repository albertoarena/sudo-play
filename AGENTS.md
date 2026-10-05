# AGENTS.md

## Project

**Sudo Play** is an open-source macOS menu bar app for developers.
The user picks a genre and a developer mood, and the app suggests today's Spotify playlist and plays it.

Tagline: `$ sudo play --mood friday-deploy`

- Requirements: `docs/REQUIREMENTS.md` (source of truth for WHAT to build)
- Build plan: `docs/INSTRUCTIONS.md` (source of truth for HOW and in which ORDER)

Read both before starting any work. If they conflict with this file, ask.

Two supporting files, neither is a source of truth:

- `docs/DECISIONS.md` (log of choices the plan left open, append one entry when you make one)
- `docs/QA.md` (manual checks that tests cannot cover)

## Stack

- PHP 8.4+, latest Laravel version supported by NativePHP
- NativePHP Desktop (check https://nativephp.com/docs for the current package name, install steps and APIs before using them, do not rely on memory)
- Inertia + Vue 3 + **TypeScript** (`<script setup lang="ts">`) + Tailwind CSS
- SQLite (NativePHP default database)
- Pest for backend tests, Vitest and Vue Test Utils for components
- Larastan for static analysis, Pint for code style
- `vue-tsc` for type checking the frontend

## Non-negotiable rules

1. **TDD always.** Write a failing test first, run it, see it fail, then write the code to make it pass, then refactor. No production code without a test that required it. Pest for PHP, Vitest for Vue components. UI glue (NativePHP facades) is wrapped behind interfaces so the logic can be tested with fakes.
2. **No Spotify Web API.** No OAuth, no client ID, no API calls to Spotify. The app only opens public playlist links. This is a product decision, not a temporary shortcut.
3. **No accounts, no analytics, no tracking.** The only network call is downloading the public catalog file.
4. **Keep it simple.** No event sourcing, no queues, no extra packages unless a milestone in `docs/INSTRUCTIONS.md` asks for them. Plain Eloquent and plain PHP classes are fine.
5. **Stop after each milestone.** Summarise what was done, show the test results, and wait for human approval before starting the next milestone.

## Architecture

```
app/
  Catalog/         # parsing, validation, sync of the playlist catalog (pure PHP + one HTTP client)
  Rotation/        # daily pick logic (pure PHP, no framework dependencies)
  Player/          # PlayerInterface + implementations (AppleScript, open app, web)
  Notifications/   # DailyReminder, wraps NativePHP Notification behind an interface
  Http/Controllers # thin Inertia controllers only
  Providers/NativeAppServiceProvider.php  # menu bar, windows, scheduler wiring
resources/js/
  Pages/           # Inertia pages (MenuBar.vue, Settings.vue)
  Components/
  types/           # shared TypeScript types (Playlist, Mood, Genre, Settings)
catalog/
  playlists.yaml   # the community catalog, also bundled as offline fallback
tests/
  Unit/            # Catalog, Rotation, Player logic
  Feature/         # controllers, sync, settings
  fixtures/        # sample catalog files (valid and broken)
```

Business logic lives in `Catalog/` and `Rotation/` and must not depend on NativePHP. Controllers stay thin.

Settings are read and written only through `SettingsStore`. Nothing else touches a settings facade or table. Section 11 of `docs/REQUIREMENTS.md` lists the seams that keep later work possible, read it before changing an interface.

## Commands

```bash
composer test          # pest
composer analyse       # larastan
composer lint          # pint --test
npm test               # vitest run
npm run typecheck      # vue-tsc --noEmit
php artisan native:serve   # run the app in development
```

Add these scripts to `composer.json` and `package.json` in milestone 0 if they do not exist.

## Conventions

- Strict types in every PHP file: `declare(strict_types=1);`
- Final classes by default, readonly value objects for catalog data
- UI copy and docs: plain English, short sentences, no em dashes
- Never use the Spotify logo. Text only: "Open in Spotify". The README must say the project is not affiliated with Spotify.

## Git conventions

- Small conventional commits: `feat:`, `fix:`, `test:`, `refactor:`, `docs:`, `chore:`
- One commit per green TDD cycle is fine, do not squash everything at the end
- Commit format: `type: short subject` (max 50 chars), then a body paragraph explaining what and why, not how
- Use a heredoc for multi-line commit messages
- Never attribute commits or pull requests to Claude Code, Codex or any other tool. No "Generated with Claude Code", no `Co-Authored-By` trailer for a tool
- Every commit goes through a pull request, never straight to `main`
- Only exception: a commit that touches markdown files only can go straight to `main`

## Definition of done (every milestone)

- All tests green, no skipped tests
- `composer analyse`, `composer lint`, `npm test`, `npm run typecheck` pass
- New behaviour is covered by tests written before the code
- Any choice the plan left open is logged in `docs/DECISIONS.md`
- The milestone is ticked in the Status list in `docs/INSTRUCTIONS.md`
- Short summary posted for review, then wait
