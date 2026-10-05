# Manual QA

Checks that tests cannot cover, because they need a real Mac, a real Spotify install or a real macOS permission prompt.

Run the player checks at the end of M4 against `native:serve`. Run the whole list at the end of M7 against the installed DMG, on a Mac that has never run the app.

## Player

- [ ] Autoplay mode plays a real public playlist in the Spotify app
- [ ] The first autoplay attempt shows the macOS Automation permission prompt, and the app explains it in one line before or alongside the prompt
- [ ] Denying that permission falls back to the web player and shows a short message
- [ ] Autoplay with Spotify closed either launches it and plays, or times out and falls back. It never hangs the window
- [ ] Open only mode opens the playlist in the Spotify app without starting playback
- [ ] Web player mode opens the playlist in the default browser

## Catalog and offline

- [ ] First run with no database loads the bundled catalog
- [ ] The app works with the network off, using the stored catalog
- [ ] A bad catalog URL in settings leaves the previous catalog in place and does not break the window
- [ ] "Update catalog now" refreshes the footer date

## Window and system

- [ ] The menu bar icon is readable in light mode and dark mode
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
