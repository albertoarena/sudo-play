# Manual QA

Checks that tests cannot cover, because they need a real Mac, a real Spotify install or a real macOS permission prompt.

Run the player checks at the end of M5, not M4, against `native:serve`. M4 builds the players but gives no way to trigger one: the menu bar arrives in M5, and there is no command that plays. The two permission checks need M5 as well, because macOS grants Automation permission per application, so it has to be the app asking and not a terminal. Run the whole list at the end of M7 against the installed DMG, on a Mac that has never run the app.

Tick checks as they pass, with the date, so a milestone is verified while it
is fresh rather than months later. A tick means "verified in development".
It does not carry over to the installed app, which has different paths, a
sandbox, its own Automation permission identity and no dev server. So M7
clears every box before the DMG run, and the ticks it then makes are the ones
that count for the release.

**Release run, started 2026-10-14 against the signed builds.** The development
ticks were cleared. A tick now reads _(1.0.1, date)_ or _(1.0.0, date)_: the
signed build it was seen on. A note in italics under a check records a partial
result or a failure, and the check stays open until it fully passes.

## Player

- [x] Autoplay mode plays a real public playlist in the Spotify app _(1.0.1, 2026-10-14)_
- [x] The first autoplay attempt shows the macOS Automation permission prompt, and the app explains it in one line before or alongside the prompt _(1.0.0, 2026-10-14)_
- [ ] Denying that permission falls back to the web player and shows a short message
- [ ] With the permission denied, the message says where to allow it (System Settings, Privacy & Security, Automation)
- [ ] Autoplay with Spotify not installed falls back to the web player and shows a short message
- [ ] Autoplay with Spotify closed: quit Spotify, press Play. Spotify opens and plays the playlist, and no browser tab opens. Play stays disabled meanwhile, and the window stays open
- [ ] Autoplay with a slow Spotify start, if one can be produced (for example right after a restart, or with a large Spotify cache): press Play. Either Spotify plays the playlist, or after about 20 seconds the window stays open and says `# spotify is still starting, press Play again`. No browser tab opens. A second Play then plays the playlist
- [ ] Autoplay with Spotify not installed does not show a "Where is Spotify?" dialog
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
- [ ] With two Spaces on one display, clicking the icon from the second Space opens the window there, without switching to the first. Same on a second display
- [ ] The footer stays visible without scrolling at the default window size, with a fallback comment, the "Add a ... playlist" link and the autoplay hint all showing
- [ ] No unthemed band appears below the content in dark mode, at any scroll position
- [ ] The buttons and the footer stay put while the chips and the pick card scroll
- [ ] "Add a playlist" opens the contributing page in the default browser, not inside the window
- [ ] Before the first sync the footer says "Built-in playlists"
- [ ] The footer links underline on hover and on focus, are easy to hit, and a short line sets Quit apart from Settings
- [ ] Buttons, chips and footer links show the pointer cursor. Play while it runs shows the plain one
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
- [ ] Pick a genre and mood with no playlist, so a fallback plays: the command line keeps the command as asked and shows a dim `#` comment line under it, the card shows a muted link that names the asked genre, such as "Add a classical playlist", and nothing on screen is amber
- [ ] The "Add a ... playlist" link on the card opens the contributing page in the default browser
- [ ] With a test catalog in Settings that leaves a mood with no playlist at all (the shipped catalog covers every mood, so it never gets here), pick a genre and mood that has nothing, not even a fallback: the header shows a dim `# no playlists yet` line under the command, and the card keeps its sentence and its "Add a playlist" link
- [ ] Choose a long genre and mood, such as `--genre electronic --mood friday-deploy`: if the command does not fit, it breaks between flags with a dim ` \` at the end of the line and a two space indent on the next, never inside a flag. Make the window narrower if the NativePHP window allows it and check again
- [ ] The curator handle on the card is in the mono font, and sits well next to the genre and mood
- [ ] The Settings section headings look like the "today's pick" label: mono, small, grey, lower case
- [ ] The window uses the system font, with no flash of a fallback on open

## Keyboard

Open the window from the menu bar icon and use only the keyboard. Turn on
VoiceOver with Command F5 for the two screen reader checks.

- [ ] With nothing focused, Enter plays the pick, and so does Space
- [ ] `N` shows the next pick and `S` a surprise pick. With the catalog empty, neither does anything
- [ ] Esc hides the window, and a click on the icon opens it again on the same pick
- [ ] Esc still hides the window while a button or a chip has focus
- [ ] Command N, Command S and Command Q keep their usual meaning and do not trigger a shortcut
- [ ] Tab goes genre chips, mood chips, then Play, with one stop for each chip group. With a fallback, the "Add a ... playlist" link comes just before Play
- [ ] Inside a chip group, the arrow keys move and select, Home and End jump to the ends, and the command line follows
- [ ] VoiceOver reads a chip as a radio button with its place, such as "Jazz, radio button, 2 of 7"
- [ ] VoiceOver reads a mood chip by its label, "Shipped It", with no emoji name first. The same on the card's mood, and on the mood options in Settings, Defaults
- [ ] VoiceOver does not read the backslash of a wrapped command line
- [ ] Hovering Play, Next and Surprise me shows "Play (Enter)", "Next (N)" and "Surprise me (S)"

## Feedback

Open the window a few times from the menu bar icon, then use it with VoiceOver
on for the last two checks.

- [ ] Opening the window does not dim Play, Next and Surprise me, not even for a moment
- [ ] After a played Play, `▶ playing "<title>"` shows under the buttons in grey, not amber. It is still there when the window is opened again later, in every player mode, and goes when Next, Surprise me, a chip or another Play is pressed
- [ ] The line does not make the window jump: the footer stays where it was when the line comes and goes
- [ ] In Open only mode, a Play shows `▶ opened "<title>" in Spotify`, and Spotify opens the playlist without playing it
- [ ] In Web player mode, a Play shows `▶ opened "<title>" in your browser`, and the browser opens the playlist
- [ ] In autoplay mode, `▶ playing "<title>"` shows only once Spotify really plays, also after a cold start
- [ ] The permission hint shows in autoplay mode until the first Play that starts music, then never again, also after a restart
- [ ] With VoiceOver on, Next reads "Today's pick:" and the new title
- [ ] With VoiceOver on, a message such as a failed Settings click is read out when it appears

## Settings window

- [ ] Settings in the menu bar footer opens the settings window, with a title bar and no Dock icon
- [ ] A second click on Settings brings the open window to the front, it does not open a second one
- [ ] In `native:serve` with `APP_DEBUG=true`, the window opens without the Chrome inspector
- [ ] Buttons, links, the player mode radios and the checkboxes with their labels show the pointer cursor. The weekdays box, while the reminder is off, shows the plain one
- [ ] The window shows every section without the Save button going below the window edge
- [ ] A saved player mode is used by the next Play, without a restart
- [ ] Turning on launch at login adds Sudo Play to System Settings, General, Login Items, and turning it off removes it
- [ ] A saved default genre and mood is what the window opens on when nothing was used yet
- [ ] The time field takes a 24 hour time on a Mac set to a 12 hour clock
- [ ] The GitHub link opens in the default browser, not inside the window
- [ ] Quit from the footer actually quits

## Release

- [ ] The DMG installs on a Mac that has never run the app
- [x] Gatekeeper does not block the signed and notarized build _(1.0.1, 2026-10-14)_
  _Downloaded copies of 1.0.0 and 1.0.1 open after the single "downloaded from the internet" question. A copy carried out of the DMG with no network was not tried: in 1.0.1 only the DMG is stapled, not the app inside it. Fixed in scripts/macos/release.sh for the next release_
- [ ] The Sudo Play icon, not the NativePHP one, shows on the DMG volume, and on the app in Finder and Applications
  _Fails on 1.0.1, 2026-10-14: the app shows the Sudo Play icon (its icon.icns is the one in art/icon), but the DMG volume shows the generic disk icon, because the hand-made DMG had no .VolumeIcon.icns. Fixed in scripts/macos/release.sh for the next release_
- [ ] The Sudo Play icon shows in System Settings, General, Login Items, after turning on launch at login
- [ ] The Sudo Play icon shows on the daily reminder notification and in System Settings, Notifications
- [ ] The Sudo Play icon shows on the macOS prompt that asks to control Spotify, and in System Settings, Privacy & Security, Automation
  _Seen on 1.0.0, 2026-10-14: the prompt showed the generic Automation icon, gears with a hand, not the app icon. Check whether that is how macOS draws every Automation prompt, and look at the Automation list in System Settings_
- [x] The About panel shows the version, the GitHub link and "Not affiliated with Spotify" _(1.0.1, 2026-10-14)_
- [ ] The About panel links to `PRIVACY.md` and the link opens
- [ ] The Credits section lists the curators in the current catalog, and an empty catalog shows no broken list
  _Partial, 1.0.0, 2026-10-14: Credits lists @albertoarena for the shipped catalog. The empty catalog case is still to do_
- [ ] "Contributors on GitHub" in Credits opens the contributors page in the default browser
- [x] `PRIVACY.md` still matches what the app does, in particular the list of network calls _(1.0.1, 2026-10-14)_
  _Checked against the code: one outbound call, the catalog download in HttpCatalogDownloader, at startup and daily. The frontend only calls the local server. Links open through shell.openExternal. Since 1.0.0 the bundle declares no camera, microphone, Bluetooth or folder access either_
- [ ] With a network monitor such as LuLu or Little Snitch, the installed app makes no request other than the catalog download, from start to quit. In particular no update check: the NativePHP updater is off
  _Partial, 1.0.1, 2026-10-14: open connections of every app process sampled every 0.2 s from start to quit, over one minute. One outbound connection, from the bundled php, to GitHub on 443 (2606:50c0:8000::154). Nothing from Electron. Sampling can miss a very short connection, so LuLu is still to do_
- [x] No Spotify logo anywhere in the app or the README _(1.0.1, 2026-10-14)_
  _The only images are the app icon, the menu bar template, the favicon and the two screenshots of the app window_
