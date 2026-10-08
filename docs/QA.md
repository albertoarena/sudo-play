# Manual QA

Checks that tests cannot cover, because they need a real Mac, a real Spotify install or a real macOS permission prompt.

Run the player checks at the end of M5, not M4, against `native:serve`. M4 builds the players but gives no way to trigger one: the menu bar arrives in M5, and there is no command that plays. The two permission checks need M5 as well, because macOS grants Automation permission per application, so it has to be the app asking and not a terminal. Run the whole list at the end of M7 against the installed DMG, on a Mac that has never run the app.

## Player

- [ ] Autoplay mode plays a real public playlist in the Spotify app
- [ ] The first autoplay attempt shows the macOS Automation permission prompt, and the app explains it in one line before or alongside the prompt
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

- [ ] The menu bar icon is readable in light mode and dark mode
- [ ] Clicking the icon opens a window of 360 by 520 under the icon, and there is no Dock icon
- [ ] "Add a playlist" opens the contributing page in the default browser, not inside the window
- [ ] The pick follows the local date: just after local midnight it changes, even when UTC is still on the previous day
- [ ] Next, then quit and start again: the window is back on today's pick
- [ ] With the window open across midnight, the next time it gets focus it shows the new day's pick
- [ ] After the startup sync replaces the catalog, closing and opening the window shows the new footer date
- [ ] The icon appears in under 3 seconds from a cold start
- [ ] The window renders immediately, before the startup sync finishes
- [ ] Launch at login works after a reboot
- [ ] The daily reminder fires at the configured time, and not on a weekend when weekdays only is on
- [ ] Quit from the footer actually quits

## Release

- [ ] The DMG installs on a Mac that has never run the app
- [ ] Gatekeeper does not block the signed and notarized build
- [ ] The About panel shows the version, the GitHub link and "Not affiliated with Spotify"
- [ ] The About panel links to `PRIVACY.md` and the link opens
- [ ] The Credits section lists the curators in the current catalog, and an empty catalog shows no broken list
- [ ] `PRIVACY.md` still matches what the app does, in particular the list of network calls
- [ ] No Spotify logo anywhere in the app or the README
