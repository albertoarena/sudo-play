# Plan: a Windows release

**Status:** a proposal, not started. Nothing here is committed to.
`docs/REQUIREMENTS.md` section 3 still lists Windows as out of scope, and that
stays true until this plan is accepted.

Written on 2026-10-15, one day after v1.0.1 went live on macOS.

## The decision

Go ahead only if the macOS release shows real interest. The app has no
analytics, so the signals have to come from public places:

- GitHub stars and forks, and how fast they grow after launch
- Issues or discussions asking for Windows, and the people behind them
- Download counts per release asset, from
  `gh api repos/albertoarena/sudo-play/releases --jq '.[].assets[] | {name, download_count}'`
- Replies to launch posts that mention Windows

A rough bar: Windows requests keep arriving without being asked for, and the Mac
downloads show the app is used, not only starred. Set the actual numbers when
the time comes.

## What carries over unchanged

NativePHP builds for Windows already. `php artisan native:build win` exists,
`nativephp/php-bin` ships a Windows x64 PHP, and the Electron builder config has
an NSIS installer and Azure signing hooks. These parts do not depend on macOS:

- The catalog, the daily rotation, the settings and SQLite
- The catalog sync child process
- The whole Vue interface
- `OpenAppPlayer`: Windows hands the `spotify:` URI to the Spotify desktop app
- `WebPlayer`: the browser fallback
- The login item: `App::openAtLogin` is Electron's own, and Windows supports it
- The daily reminder: Electron shows it as a Windows toast

## What has to change

### 1. Autoplay

`AppleScriptPlayer` is the default player mode, and it needs `osascript` and
`open -g -a`. Windows has neither. The Spotify desktop app on Windows has no
scripting interface, and the Spotify Web API is ruled out by rule 2 in
`AGENTS.md`.

Proposal: on Windows, hide the autoplay mode and make "open in Spotify" the
default. The user presses Play once. Sending a media Play key after opening the
URI could work, but it is fragile and plays whatever was last loaded if it fires
too early, so it is not part of this plan.

This needs one seam: a small `Platform` interface the settings options and
`PlayerResolver` ask, so the logic stays testable on Linux CI with a fake.

### 2. Menu bar becomes a system tray icon

`MenuBar::create()` becomes a tray icon, and the window opens near the clock.

- `menuBarIconTemplate.png` is recoloured by macOS only. Windows needs its own
  tray icon, legible on light and dark taskbars, as `.ico` or a PNG pair
- `showOnAllWorkspaces()` and `showDockIcon(false)` are macOS settings. Check
  that they do nothing harmful on Windows
- `NativeMenuBarWindow` posts to `menu-bar/hide` to close the window on Esc. Check
  that this still works
- Check where the window opens with the taskbar at the bottom, on the side and
  on a second screen

### 3. Art

- An app icon, `icon.ico`, with the sizes Windows asks for (16 to 256). Remove
  the "No `icon.ico`" line from `docs/DECISIONS.md` when it lands
- The tray icon from point 2

### 4. Words

- The Automation error in `app/Player/AppleScriptPlayer.php` never shows on
  Windows once autoplay is hidden, but check every other message for macOS
  terms: "menu bar", "System Settings", "Dock"
- Comments in `Settings.vue` and `MenuBar.vue` that describe macOS behaviour
- `README.md`, `PRIVACY.md`, `CONTRIBUTING.md` and sudoplay.dev say macOS

### 5. Requirements and decisions

- Move Windows out of section 3 of `docs/REQUIREMENTS.md`, and write what the
  Windows version does and does not do (no autoplay)
- A `docs/DECISIONS.md` entry for each choice below

## Building and releasing

- A `windows-latest` job in `release.yml`, running
  `php artisan native:build win x64`. The repository is public, so standard
  GitHub runners are free. The old cost reason for keeping macOS to one workflow
  no longer applies
- The draft release gets the installer next to the DMG, with its SHA-256 in the
  notes, and follows the same flow as `docs/RELEASE.md`: draft, test the
  download, publish
- `docs/RELEASE.md` gets a Windows section

### Signing

An unsigned installer shows SmartScreen's "Windows protected your PC" warning.
For a small app from an unknown publisher, that warning loses many users.

| Option | Cost | Notes |
|---|---|---|
| Azure Trusted Signing | about $10 a month | NativePHP reads the `NATIVEPHP_AZURE_*` variables already. Check whether it accepts an individual outside the US and Canada |
| SignPath Foundation | free | For open source projects. They decide whether to accept the project |
| OV certificate | a few hundred dollars a year | Needs a hardware token or a cloud key. SmartScreen still warns until the certificate builds reputation |
| Unsigned | free | The README explains the warning and how to get past it |

## Testing

Windows checks need a real Windows machine. On an Apple Silicon Mac that means
Windows 11 ARM in Parallels or UTM, which runs the x64 build under emulation. A
second check on a real x64 PC is worth having before the first release.

`docs/QA.md` gets a Windows section: the tray icon and window, the reminder
toast and its click, the login item, the `spotify:` handoff with Spotify
installed and not installed, the browser fallback, and uninstalling.

## Milestones

**W0, spike (half a day).** Build an unsigned Windows installer from `main`, run
it in a VM, and write down what works and what breaks. It answers the unknowns
in point 2 before any code is planned. No pull request unless something is
worth keeping.

**W1, the app on Windows.** The `Platform` seam, autoplay hidden on Windows, the
tray icon and `icon.ico`, the words, the requirements and decisions. TDD as
always. Done when the suite is green and the W0 problems are fixed in a VM.

**W2, the release.** The Windows job in `release.yml`, signing, the Windows
section of `docs/QA.md` run in full, `docs/RELEASE.md`, the README and the docs
site.

## Decisions left open

1. Can Windows ship without autoplay? This plan assumes yes
2. Which signing option
3. Installer only, or also `winget` and Scoop later
4. Whether the same version number ships on both platforms at once, or Windows
   can lag behind

## Out of scope

- Linux
- A native Windows on ARM build: php-bin has no ARM build for Windows
- Any autoplay workaround on Windows
- The auto updater, which is off on macOS too
