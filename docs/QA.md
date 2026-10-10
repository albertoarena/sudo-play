# Manual QA

Checks that tests cannot cover, because they need a real Mac, a real Spotify install or a real macOS permission prompt.

Run the player checks at the end of M5, not M4, against `native:serve`. M4 builds the players but gives no way to trigger one: the menu bar arrives in M5, and there is no command that plays. The two permission checks need M5 as well, because macOS grants Automation permission per application, so it has to be the app asking and not a terminal. Run the whole list at the end of M7 against the installed DMG, on a Mac that has never run the app.

Tick checks as they pass, with the date, so a milestone is verified while it
is fresh rather than months later. A tick means "verified in development".
It does not carry over to the installed app, which has different paths, a
sandbox, its own Automation permission identity and no dev server. So M7
clears every box before the DMG run, and the ticks it then makes are the ones
that count for the release.

## Player

- [x] Autoplay mode plays a real public playlist in the Spotify app _(dev, 2026-10-08)_
- [x] The first autoplay attempt shows the macOS Automation permission prompt, and the app explains it in one line before or alongside the prompt _(dev, 2026-10-08)_
- [ ] Denying that permission falls back to the web player and shows a short message
- [ ] With the permission denied, the message says where to allow it (System Settings, Privacy & Security, Automation)
- [ ] Autoplay with Spotify not installed falls back to the web player and shows a short message
- [ ] Autoplay with Spotify closed either launches it and plays, or times out and falls back. It never hangs the window
- [ ] Open only mode opens the playlist in the Spotify app without starting playback
- [ ] Open only mode with Spotify not installed: write down what macOS does. The app cannot detect it, see `docs/DECISIONS.md`
- [ ] Web player mode opens the playlist in the default browser

## Catalog and offline

- [ ] First run with no database loads the bundled catalog
- [ ] The app works with the network off, using the stored catalog
- [ ] A bad catalog URL in settings leaves the previous catalog in place and does not break the window
- [ ] "Update catalog now" refreshes the footer date
- [ ] The startup sync runs as its own process and finishes, leaving the catalog updated without the window ever waiting
- [ ] The daily schedule actually fires in a packaged app, where NativePHP runs the scheduler rather than cron

## Window and system

- [x] The menu bar icon is readable in light mode and dark mode _(dev, 2026-10-08)_
- [ ] Clicking the icon opens a window of 360 by 520 under the icon, and there is no Dock icon
- [x] The footer stays visible without scrolling at the default window size, with a fallback note and the autoplay hint both showing _(dev, 2026-10-08)_
- [x] No unthemed band appears below the content in dark mode, at any scroll position _(dev, 2026-10-08)_
- [x] The buttons and the footer stay put while the chips and the pick card scroll _(dev, 2026-10-08)_
- [ ] "Add a playlist" opens the contributing page in the default browser, not inside the window
- [ ] The pick follows the local date: just after local midnight it changes, even when UTC is still on the previous day
- [ ] Next, then quit and start again: the window is back on today's pick
- [ ] With the window open across midnight, the next time it gets focus it shows the new day's pick
- [ ] After the startup sync replaces the catalog, closing and opening the window shows the new footer date
- [ ] The icon appears in under 3 seconds from a cold start
- [ ] The window renders immediately, before the startup sync finishes
- [ ] Launch at login works after a reboot
- [ ] The daily reminder fires at the configured time, and not on a weekend when weekdays only is on
- [ ] Clicking the daily reminder opens the menu bar window
- [ ] With the Mac asleep at the reminder time, the reminder arrives once on wake, if that is no more than 4 hours later. It does not arrive a second time that day
- [ ] With the Mac asleep at the reminder time and woken more than 4 hours later, no reminder arrives that day

## Colours and focus

Run these in light mode and again in dark mode, switching with System
Settings, Appearance. The contrast numbers are already checked by
`resources/js/theme.test.ts`, so what is left is what only a screen shows.

- [ ] The command line header reads as a terminal in light mode: a dark panel on the white page, with its own edge visible
- [ ] Play, Save and the selected chip are legible, and the green is the app's green and not the macOS accent colour
- [ ] Next, Surprise me and the unselected chips show a clear edge, and the edge darkens on hover
- [ ] Tab through the menu bar window: every button and chip takes a green focus ring, 2px with a gap, and no ring is clipped by the window edge
- [ ] Tab through the settings window: the same ring on every field, checkbox, radio and button
- [ ] The focus ring stays green after changing the macOS accent colour to orange in System Settings, Appearance
- [ ] With the macOS accent colour set to orange, a ticked checkbox and a chosen radio in the settings window stay green
- [ ] With Play disabled, it is dimmed and the pointer stays an arrow
- [ ] A warning line, for example after denying the Automation permission, reads as a warning and not as body text
- [ ] The window uses the system font, with no flash of a fallback on open

## Settings window

- [ ] Settings in the menu bar footer opens the settings window, with a title bar and no Dock icon
- [ ] A second click on Settings brings the open window to the front, it does not open a second one
- [ ] The window shows every section without the Save button going below the window edge
- [ ] A saved player mode is used by the next Play, without a restart
- [ ] Turning on launch at login adds Sudo Play to System Settings, General, Login Items, and turning it off removes it
- [ ] A saved default genre and mood is what the window opens on when nothing was used yet
- [ ] The time field takes a 24 hour time on a Mac set to a 12 hour clock
- [ ] The GitHub link opens in the default browser, not inside the window
- [ ] Quit from the footer actually quits

## Release

- [ ] The DMG installs on a Mac that has never run the app
- [ ] Gatekeeper does not block the signed and notarized build
- [ ] The About panel shows the version, the GitHub link and "Not affiliated with Spotify"
- [ ] The About panel links to `PRIVACY.md` and the link opens
- [ ] The Credits section lists the curators in the current catalog, and an empty catalog shows no broken list
- [ ] "Contributors on GitHub" in Credits opens the contributors page in the default browser
- [ ] `PRIVACY.md` still matches what the app does, in particular the list of network calls
- [ ] With a network monitor such as LuLu or Little Snitch, the installed app makes no request other than the catalog download, from start to quit. In particular no update check: the NativePHP updater is off
- [ ] No Spotify logo anywhere in the app or the README
