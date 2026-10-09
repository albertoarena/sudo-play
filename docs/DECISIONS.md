# Decisions

One short entry per choice the plan leaves open. Append only, newest at the bottom. Date, choice, reason, and what would make us change our mind.

The format is deliberately small. If an entry needs more than six lines it probably belongs in `docs/REQUIREMENTS.md` instead.

---

## 2026-10-05: Rotation tiebreak stays a pure function

The daily pick must be deterministic and must differ from yesterday. crc32 collides on consecutive days sooner or later, so a tiebreak is needed.

**Choice:** recompute yesterday's index with the same formula over the same candidate list, and shift by one when they collide. Nothing about yesterday is stored.

**Why:** storing yesterday's pick would make the result depend on local history, so two users with the same catalog could see different picks and could not share "today's pick".

**Would change if:** we ever want a per user rotation that avoids repeats over a longer window. That needs stored state and gives up shareability.

## 2026-10-05: Footer date is the sync timestamp

**Choice:** the catalog has no date field. The app records the time of the last successful sync and shows that.

**Why:** a date inside the YAML has to be bumped by hand in every pull request, and contributors will forget. The sync time answers the question the user actually has, which is whether their catalog is fresh.

**Would change if:** we start publishing catalog releases, where a content version would be more meaningful.

## 2026-10-05: Settings move to M3

**Choice:** the `SettingsStore` interface and its implementation land in M3, not M6. M6 keeps the settings window and the reminder.

**Why:** M4 reads the player mode and M5 remembers the last genre and mood. Leaving storage until M6 would mean building it twice.

## 2026-10-05: Vitest for the Vue layer

**Choice:** install Vitest and Vue Test Utils in M0 and write component tests before components.

**Why:** rule 1 in `AGENTS.md` says no production code without a failing test. The stated exception covers NativePHP facades, not the frontend. Either the tooling exists or the rule has a hole.

**Would change if:** the component layer stays so thin that typecheck plus Feature tests genuinely cover it. Revisit after M5.

## 2026-10-05: Ship an empty playlist list, gate the release

**Choice:** `catalog/playlists.yaml` ships with genres and moods and no playlists. `catalog:validate --release` fails on an empty list or a leftover `TODO`, and the release workflow runs it.

**Why:** the old plan had placeholder playlists marked `TODO`, which would either ship an app that plays nothing or invent Spotify ids. An explicit gate makes the empty state a development state, not a release state.

---

## 2026-10-05: Privacy and credits are in scope for v1

**Choice:** add `PRIVACY.md` at the repository root, link it from the README and the About panel, and add a Credits section to About that lists the curators in the current catalog plus a link to the GitHub contributors page.

**Why:** the app's privacy story is a selling point, not a legal chore, so it should be stated plainly rather than left implicit. Curators are named in the catalog already and the people who contribute playlists should be visible somewhere other than a pick card.

**Would change if:** the curator list grows past what a small panel can show. Then it moves to the README or a generated page.

## 2026-10-05: Per user preferences and paid tiers are deferred, with seams

**Choice:** v1 stays single user and free. Section 11 of `docs/REQUIREMENTS.md` records the seams that keep both possible: all settings behind `SettingsStore`, the catalog URL as a setting rather than a constant, `CatalogSync` taking its source as a dependency, strict version rejection with tolerant unknown fields, and `PlayerInterface` hiding Spotify from the rest of the app.

**Why:** building either now would add profiles, identity or a licence server to an app whose rules say no accounts and no network calls beyond the catalog. The cost of keeping the door open is close to zero, the cost of walking through it now is not.

**Would change if:** a paid tier is actually wanted. That conversation starts by amending `AGENTS.md`, not by writing code.

## 2026-10-06: The repository is public at launch

**Choice:** `sudo-play` goes public before release. The catalog is served from it, community playlist pull requests come to it, and the README points to it.

**Why:** the app downloads the catalog over plain https with no token, because it ships no secrets and has no accounts. A private repository returns 404 for that URL. Everything else in the plan already assumed an open source project with community contributions.

**Note:** development is unaffected. While the repository is private the sync treats 404 as a network error and keeps the bundled catalog, so M3 can be built and tested in full before the repository opens.

## 2026-10-06: Main plus tags, no release branch

**Choice:** `main` is the only long lived code branch, protected, with `ci.yml` required. Releases are tags. Hotfix branches are created from a tag only when needed, then deleted. The version comes from the tag and is injected at build time.

**Why:** a tag is already an immutable pointer to an approved release, and people install a DMG from a GitHub Release, not a branch. A standing `live` branch would be a second thing to keep in sync and a second approval of a diff the same person already approved when merging. Deriving the version from the tag also keeps the release workflow from pushing to `main`, which the git rules forbid.

**Would change if:** the project ever supports an old major while developing the next, or gains co-maintainers shipping in parallel.

## 2026-10-06: Shipped apps read a catalog-live branch

**Choice:** the default catalog URL points at a `catalog-live` branch. `catalog-publish.yml` fast forwards it from `main` whenever `catalog/**` changes, so a merged playlist is live within a day. Playlist changes never need a release.

**Why:** the catalog URL is frozen into every binary. Pointing at `main` would lock every installed copy to `main` forever, and a review step could only be added by shipping a new version that people have to install. The indirection costs one branch and a small workflow, and behaves identically until the policy needs to change.

**Would change if:** volume or co-maintainers make a review step worth the friction. Then the workflow is turned off and promotion becomes manual, with no app release.

## 2026-10-06: Three Linux workflows, CI blocks the merge

**Choice:** `ci.yml` on every pull request and push to `main` with no path filter, `catalog.yml` as fast validator-only feedback on catalog pull requests, `catalog-publish.yml` for the branch copy. Branch protection on `main` with `ci.yml` required.

**Why:** no path filter on `ci.yml` because a broken catalog can break a test that reads the bundled file. `catalog.yml` exists so a contributor adding one line gets an answer in seconds rather than waiting for the full suite. Only the release build needs macOS, and those runners bill at ten times the Linux rate.

## 2026-10-06: Signed and notarized, with an API key

**Choice:** pay for an Apple Developer account and ship a signed, notarized DMG. Notarization uses an App Store Connect API key rather than an Apple ID.

**Why:** an unsigned build makes macOS warn that it cannot be checked for malware, and users have to allow it by hand. For a project whose stated goal is attracting interest, that warning costs more than the 99 USD a year. A key is scoped and revocable without touching the Apple account.

## 2026-10-06: First release by hand, automate after

**Choice:** build, sign and notarize the first DMG manually in M7, record the exact commands in `docs/RELEASE.md`, then write `release.yml` from them.

**Why:** notarization fails in dull ways, and each debugging attempt inside a workflow is a full run on a machine you cannot inspect. Doing it locally first is slower to set up and much faster to get right, and it leaves a document that explains the process rather than a workflow nobody understands.

## 2026-10-06: Contributions are MIT, with a relicensing line

**Choice:** `CONTRIBUTING.md` states that contributions are made under MIT and that future versions may be released under other terms. It must be in place before the repository goes public.

**Why:** contributors keep copyright on their own lines. Without this, changing the licence later needs the agreement of every one of them, which in practice means never. Most contributions here are playlist data rather than code, so the burden is small.

**Accepted cost:** a few developers read a relicensing clause as a reason not to contribute code.

## 2026-10-06: A paid tier would be a hosted catalog

**Choice:** if a paid tier ever happens, it is access to a premium catalog hosted by the maintainer. The app stays free and MIT. Nothing is built now.

**Why:** you cannot sell code in a desktop app, because the DMG contains readable PHP and a licence check inside it is a speed bump rather than a lock. What a fork cannot copy is a catalog you host and keep curating.

**Before any of it:** the no accounts, no analytics and one network call rules in `AGENTS.md` would have to be amended deliberately, and `PRIVACY.md` would be the first file to change.

## 2026-10-06: CI moved forward from M7 to M0

**Choice:** `.github/workflows/ci.yml` ships with the M0 scaffold instead of M7. It runs Pest, Larastan, Pint, Vitest and vue-tsc on every pull request and on pushes to `main`. The other workflows stay in M7, as do the `envaudit` step and branch protection.

**Why:** from M1 onward each milestone arrives as a pull request written by an agent that ran the suite in its own container and reported the result. Without CI, the reviewer is trusting that report. `AGENTS.md` also already told agents that `ci.yml` is a required check, which was not true. One Linux job of two to four minutes closes both gaps.

**Detail worth keeping:** `npm run build` has to run before `composer test`. The feature test renders the Blade layout, which calls `@vite` and fails without `public/build/manifest.json`.

## 2026-10-07: `symfony/yaml` is the one package M1 adds

**Choice:** add `symfony/yaml` to `require` in `composer.json`. It is the only package M1 adds.

**Why:** the catalog is YAML, so `CatalogParser` has to read YAML. The `yaml` PHP extension is not installed and CI does not install it, and the package was only in the lock file as a dev dependency of `laravel/sail`, so production code could not use it. The alternatives were worse: a hand written parser is real code to own, and it would reject valid YAML a contributor wrote in good faith, while moving the catalog to JSON contradicts requirements 4.1 and makes community pull requests harder to read.

**Why this package:** it is a first party Symfony component already resolved in `composer.lock`, so no new vendor enters the dependency tree.

**Would change if:** the app ever needs to dump YAML as well as read it, which would not change the package, only how much of it is used.

## 2026-10-07: `catalog:validate` stays in M1

**Choice:** `php artisan catalog:validate {path?} {--release}` ships with M1, as `docs/INSTRUCTIONS.md` M1 says, even though it is a command rather than one of the three classes M1 is named after.

**Why:** the build plan is the source of truth for scope and order, and the command is the only caller that proves the parser reports errors in a way a human can read. M7 also assumes it exists: `catalog.yml` runs it on contributor pull requests and `release.yml` runs it with `--release`. Leaving it out would mean writing the parser's error output twice.

**Where the release gate lives:** the zero playlists and leftover `TODO` checks from requirements 10.1 sit in a small `ReleaseGate` beside the parser, not inside `CatalogParser`. They are release policy, not catalog validity, and a catalog with no playlists is a normal development state that the app has to handle.

## 2026-10-07: The parser returns errors and warnings, and logs nothing

**Choice:** `CatalogParser::parse()` returns a `CatalogParseResult` with three things: a `Catalog` or null, a list of error messages, and a list of warning messages. All messages are plain strings. The parser never writes to a log.

**Why:** the format needs two channels, not one. An unknown field on a playlist must be reported and the playlist still kept, per requirements 4.3, so it cannot be an error. If it were, `catalog:validate` would fail a catalog the app is required to load. A null catalog is the separate signal that the whole file was rejected and the last good catalog should be kept.

**Why plain strings:** the messages are read by people, in command output and in a log line. A message object would need a renderer before it said anything more, and nothing in v1 filters or groups them.

**Why no logging:** `app/Catalog/` must not depend on the framework. The caller decides, so the command prints and the M3 sync logs.

**Would change if:** the settings window ever shows catalog problems in the UI. Then the messages need a code and a row reference, not a sentence.

## 2026-10-07: The daily pick walks the list with a coprime step

The rule in requirements 5 compared today's raw crc32 index with yesterday's raw index and shifted by one on a collision. The shift then changed the pick, so the number it compared against was not what the user saw yesterday. Over 400 days and twelve genre and mood pairs the same playlist came back two days running on 23 per cent of days with three candidates, and on 42 per cent with two.

**Choice:** sort the candidates, then walk the list one step per day: `index = (seed + dayNumber * step) % count`, with the seed and the step taken from the genre and the mood, and the step adjusted until it shares no factor with the number of candidates. This supersedes the entry of 2026-10-05.

**Why:** a step that is coprime with the count is never a multiple of it, so two days running cannot land on the same place and the promise holds by construction rather than by a tiebreak that cannot see its own effect. The walk also visits every candidate before coming round again. The pick stays a pure function of the day, the genre, the mood and the candidate list, so it is still shareable.

**Accepted cost:** the order is a rotation, so with three playlists a user sees a three day cycle rather than something that looks random.

**Would change if:** we want an order that looks random and still never repeats. That needs stored state, and gives up two users seeing the same pick.

## 2026-10-07: The picker takes a calendar date, not a moment

**Choice:** a readonly `CalendarDate` carries the day as `YYYY-MM-DD`. `CalendarDate::fromDateTime()` is the only place where a clock becomes a date, and `dayNumber()` counts whole days in UTC.

**Why:** requirements 5 says the date is the local calendar date on the user's Mac. A `DateTimeImmutable` carries a time and a zone as well, so a caller could hand the picker a moment in the wrong zone and get yesterday's pick. Counting the day number in UTC keeps every day 24 hours long, so a clock change at home cannot make two calendar days share a number or skip one.

**Would change if:** nothing foreseen. The type has no dependencies and M5 and M6 only need to call `fromDateTime()`.

## 2026-10-07: A pick carries the genre and the mood it was made for

**Choice:** `Pick` holds the playlist, the genre, the mood and a `PickSource`. The source is one of exact, same mood any genre, or same genre any mood.

**Why:** the caller does not always know the pair. "Surprise me" chooses it, so without carrying it the window could not show what was chosen. The source is what the fallback note in requirements 5 is built from, and keeping it an enum rather than a sentence leaves the wording to the UI, where it can be translated later.

**Would change if:** a third fallback is added. Then the order belongs in the enum itself rather than in the picker.

## 2026-10-07: The catalog is one JSON column

**Choice:** `catalog_snapshots` holds one row with the whole catalog as a JSON payload, plus the last successful sync time.

**Why:** tables for genres, moods, playlists and the moods on each playlist would be four tables and a pivot, and nothing would ever query them. The rotation is a pure function over the whole candidate list, so the app reads the catalog whole, uses it whole and replaces it whole.

**Reading it back is all or nothing:** the YAML parser is lenient because a contributor wrote that file, but this payload was written by the app, so a row that does not fit means a damaged database. The store then reports no catalog, which sends the caller to the bundled copy.

**Would change if:** the settings window ever needs to list or search playlists without loading them all.

## 2026-10-07: Settings go in our own table, not the NativePHP store

**Choice:** `SettingsStore` is backed by a `settings` key and value table in the app's SQLite database. Values are stored as JSON, so a checkbox comes back a boolean rather than the string "1".

**Why:** the NativePHP `Settings` facade writes to an Electron store over the local API, so every read needs an Electron process answering. That makes settings unreadable in a plain `artisan` run and turns every test that touches one into an HTTP fake. The catalog already needs the database, so one storage mechanism is simpler than two.

**Would change if:** settings ever have to be read by the Electron side rather than by PHP.

## 2026-10-07: A setting that was never changed has no row

**Choice:** defaults live in the `SettingKey` enum, not in the database. A key with no row answers with its default. A known key always answers with its own default, even when the caller offers another.

**Why:** seeding defaults on install freezes them into every existing database, so improving one would need a migration. This way a default changes with an app update. It also means the store stays the flat key to value interface requirements 11.1 asks for, without magic strings at the call sites.

## 2026-10-07: The reminder and launch at login start off

**Choice:** `reminder_enabled` and `launch_at_login` both default to false. The default genre and the default mood default to null, meaning not chosen yet.

**Why:** requirements 7.2 gives defaults for the reminder time and for weekdays only, but not for the reminder itself or for launch at login. An app that notifies you at 09:00 and adds itself to your login items without being asked is the kind of thing that gets uninstalled, so the quiet reading wins. A null genre and mood let the window open on the first pair the catalog offers, so no default can name an id a fork's catalog does not have.

**Worth confirming:** this is a gap in the requirements rather than something they state. If the reminder is meant to be on out of the box, it is a one line change.

## 2026-10-07: The bundled catalog is found with base_path()

**Choice:** `BundledCatalogSource::bundled()` reads `base_path('catalog/playlists.yaml')`, and that one method is the only place that knows where the file is.

**Why:** NativePHP starts PHP with the Laravel project root as its working directory in both modes. In development that is the project on disk; in a packaged app it is the copy inside the bundle, unpacked from the archive. The build copies the whole project apart from the patterns in `cleanup_exclude_files`, and `catalog` is not one of them. Checked against the installed package source, not from memory.

**Would change if:** a future NativePHP version serves the app from a read only archive without unpacking it. Then the file has to be copied out on first run.

## 2026-10-07: The no-playlists rule protects a catalog, it does not create one

**Choice:** a download that parses but ends with no playlists is refused and the stored catalog is kept, per requirements 4.3. A first run with an empty database still loads a bundled catalog that has no playlists.

**Why:** the rule exists so one bad pull request cannot take the catalog away from people who already have a good one. On a first run there is nothing to protect, and the genres and the moods are still needed for the window to draw its chips and its empty state.

## 2026-10-07: The bundled catalog ships from M3, not M7

**Choice:** `catalog/playlists.yaml` lands in this milestone with the genres and moods from requirements 4.2 and an empty `playlists` list. The build plan puts it in M7.

**Why:** M3 has to load the bundled catalog on a first run, and there was nothing to load. The file is data with no code behind it, so bringing it forward costs nothing and M7 keeps the review of it.

**Unchanged:** no playlist ids are invented. They are the maintainer's to add, and `catalog:validate --release` already refuses to ship a catalog without them.

## 2026-10-07: The startup sync is a child process, the daily one is the scheduler

**Choice:** `Schedule::command('catalog:sync')->daily()->withoutOverlapping()` in the console routes, and a NativePHP child process running the same command just after startup, behind a `SyncLauncher` interface.

**Why:** NativePHP runs `artisan schedule:run` every minute while the app is open, so the daily half needs no NativePHP API at all. The startup half cannot run inside the request that draws the window, because requirements 4.4 says the sync never blocks the first render. A child process returns at once and the window draws from the stored catalog.

**Known gap:** `daily()` fires at midnight. A Mac asleep at midnight misses that slot, and the scheduler restarting on resume does not make it up, so such a machine syncs only when the app is launched again. Worth revisiting in M6, where a "last synced more than a day ago" check could run hourly instead.

## 2026-10-07: PlayerMode is a setting's value, so it sits with the settings

**Choice:** the `PlayerMode` enum lives in `app/Settings/`, not in `app/Player/`.

**Why:** M3 defines the `player_mode` setting, and a setting cannot have a default without its allowed values. Keeping the enum here means `app/Player/` stays about players: M4 maps a case to a player, M6 uses the cases to validate what the settings window saves.

## 2026-10-07: The resolver is the player the app uses

**Choice:** `PlayerResolver` implements `PlayerInterface` itself. It reads `player_mode` on every play, plays through the matching player, and falls back to `WebPlayer` when that player returns a failed result. The container binds `PlayerInterface` to it.

**Why:** the fallback in requirements 6 applies whatever the mode, so it belongs in one place rather than inside each player. M5 then depends on `PlayerInterface` alone, and its tests can bind a fake there. Reading the setting on every play means a change in the settings window needs no restart.

**Messages:** each player says what went wrong in one sentence. The resolver adds "Opened the playlist in your browser instead." The raw error goes to the log, never to the window.

## 2026-10-07: AppleScript gives up after 8 seconds

**Choice:** `osascript` runs with an 8 second timeout. A timeout counts as a failure, so the playlist opens in the browser.

**Why:** `tell application "Spotify"` waits while Spotify launches, and the user is waiting on the Play button. A cold start usually takes a few seconds. After 8 seconds the browser is the better answer.

**Accepted cost:** if Spotify finishes launching just after the timeout, the script may still play, and the browser opens as well. This is rare and harmless, and better than a window that hangs.

**Would change if:** the manual checks in `docs/QA.md` show cold starts regularly taking longer on a normal Mac.

## 2026-10-07: A denied Automation permission gets its own message

**Choice:** when `osascript` fails with error -1743 ("Not authorized to send Apple events"), the message tells the user where to allow it: System Settings, Privacy & Security, Automation. Every other failure says "Could not start Spotify."

**Why:** this is the one failure only the user can fix, and macOS only shows its own prompt once. Without the hint, autoplay silently becomes the web player forever.

## 2026-10-07: Open only mode cannot see a missing Spotify app

**Choice:** `OpenAppPlayer` falls back only when the link cannot be handed to Electron at all.

**Why:** NativePHP's `Shell::openExternal` returns nothing, so when macOS has no app for a `spotify:` link the app is never told. Checking for Spotify on disk would be a second, macOS specific code path for a mode the user chose on purpose. The default mode, autoplay, does detect it and falls back.

**Would change if:** NativePHP starts returning the result of `shell.openExternal`.

## 2026-10-08: Today is read from the Mac's time zone

**Choice:** a `Today` interface gives the menu bar its date. `LocalToday` reads the zone from `TZ`, then from the `/etc/localtime` link that macOS keeps, then falls back to the app zone.

**Why:** Laravel runs in UTC and Electron does not pass the zone to PHP. Without this, a user in New York would see tomorrow's pick every evening from 20:00. Requirements 5 says the date is the local calendar date.

**Would change if:** NativePHP starts passing the system zone to PHP.

## 2026-10-08: The window keeps the Next count, the server checks its day

**Choice:** the state carries `presses` and `date`. The window sends both back with Next and with a refresh. If the day is not today, the server starts again at zero. Choosing a genre or mood also starts again at zero.

**Why:** NativePHP serves every request from a fresh PHP process, so the server has no memory between clicks. The window does, and it is gone on a restart. Sending the day with the count makes the reset on a date change a rule on the server, not a timer in the window.

## 2026-10-08: The selection opens on last, then default, then first

**Choice:** the window opens on the last genre and mood, if the catalog still has them. Otherwise the default from settings, otherwise the first in the catalog. Surprise me is remembered like a choice made by hand.

**Why:** a remembered id that a sync removed must not leave the window with no selection. Remembering a surprise means the window opens on what the user last saw.

## 2026-10-08: Surprise me stays on when only the chosen pair is empty

**Choice:** Play and Next are off when there is no pick. Surprise me is off only when the catalog has no playlists at all. The empty state line says which case it is: "No playlists in the catalog yet" or "No playlists for this genre or mood yet".

**Why:** requirements 7.1 describes the empty state of an empty catalog. A pair with no playlist and no fallback is a different case, and Surprise me is the quickest way out of it.

**Would change if:** the maintainer reads 7.1 as "all three off whenever there is no pick". That is a one line change in `MenuBarState`.

## 2026-10-08: The fallback note is written on the server

**Choice:** `FallbackNote` builds the sentence from the `PickSource`, in PHP. For the same genre in another mood it names the mood too: "No rock playlist for Flow State yet, here is a rock one for Friday Deploy. Add one on GitHub!" The card then shows the playlist's own mood, not the one the user asked for.

**Why:** the M5 tests ask for the note in the endpoint answer. The entry of 2026-10-07 left the wording to the UI layer, and the server is that layer here, as the window only draws. The pick itself still carries an enum, so translation later stays possible.

## 2026-10-08: The menu bar replaces the window, links go through the server

**Choice:** `NativeAppServiceProvider` creates a 360 by 480 menu bar window, not resizable, with no Dock icon, and opens no other window. "Add a playlist" and Quit call the server, which uses `UrlOpener` and a small `Quitter` interface. The window asks for the state again each time it gets focus.

**Why:** a plain link in the menu bar window would open GitHub inside that window. Asking again on focus is how a catalog replaced by the background sync, or a new day, shows up, per requirements 4.4, without polling.

**Gap:** the Settings link in the footer arrives with the settings window in M6. A link that does nothing would be worse than no link.

## 2026-10-08: Menu bar code lives in app/MenuBar

**Choice:** a new `app/MenuBar/` folder holds the state, the service behind the controller, the fallback note and the `Today` and `Quitter` interfaces.

**Why:** the controller has to stay thin and the logic does not belong in `Catalog/` or `Rotation/`, which must stay free of the framework and the settings. It is the same shape as `Player/`.

## 2026-10-08: Only the top of the menu bar window scrolls

**Choice:** the window has two parts. The command line, the chips and the pick card scroll when they do not fit. Play, Next, Surprise me, the status line and the footer stay fixed at the bottom, under one divider. The spacing is a little tighter than in M5.

**Why:** the window is 360 by 480 and not resizable. With a fallback note and the autoplay hint both shown, the content was about 550 pixels tall, so the footer and Quit went below the window edge. The catalog decides how many genres and moods there are, and a sync can add more, so no fixed spacing fits every case. Tighter spacing makes the note alone or the hint alone fit without scrolling.

**Would change if:** the catalog grows enough genres or moods to need more rows. The scrolling part is the safety net for that.

## 2026-10-08: The menu bar window is 520 tall

**Choice:** the window is 360 by 520, not 360 by 480.

**Why:** at 480, a fallback note and the autoplay hint together still pushed part of the note out of view, and macOS hides the scrollbar until you scroll. At 520 the worst case with the current catalog fits with a little room left. Requirements 7.1 says "about 360 x 480", and the maintainer chose 520.

## 2026-10-08: The reminder fires in the chosen minute, and nothing is stored

**Superseded** by the entry of 2026-10-09, "A missed reminder is sent up to 4 hours late". The every minute schedule below still holds. The "chosen minute only" rule and the accepted cost do not.

**Choice:** the scheduler runs `reminder:send` every minute. `DailyReminder` answers yes only when the local clock shows the chosen `HH:MM`. Nothing records that a reminder was sent.

**Why:** one minute a day means one reminder a day, with no "sent today" flag to keep in the settings store or anywhere else. Every minute, rather than `dailyAt()`, because the time is a setting that can change at any moment.

**Accepted cost:** a Mac asleep in that minute gets no reminder that day. A "today's pick" banner at four in the afternoon would be worse.

**Would change if:** users ask for a missed reminder on wake. That needs a stored date and a cut-off time. That is what the 2026-10-09 entry does.

## 2026-10-08: The reminder speaks for the default pair, or the first in the catalog

**Choice:** the reminder uses the default genre and mood. When one is not chosen, or a sync removed it, it uses the first one in the catalog, the same as the window. The text is "{emoji} {mood}? Today's pick is ready: {title}", with the emoji left out when the mood has none. When there is no pick at all, there is no reminder.

**Why:** requirements 7.3 says default, not last used. The defaults start as not chosen (entry of 2026-10-07), so a fallback is needed, and the window already has one. A banner saying a pick is ready when there is none would be a lie.

## 2026-10-08: A click on the reminder opens the menu bar window

**Choice:** NativePHP sends a `NotificationClicked` event with the reference the notification was sent with. The reminder always uses the reference `sudo-play-daily-reminder`, and a listener shows the menu bar when it sees it.

**Why:** requirements 7.3 asks for this when NativePHP supports it, and the installed version does. Checked in the package source, not from memory.

## 2026-10-08: Launch at login is saved only once macOS accepts it

**Choice:** `NativeLoginItem` calls `App::openAtLogin()` when the setting changes. The setting is stored only after that call worked. If it fails, the other settings are still saved and the window says launch at login could not be changed.

**Why:** a setting that says on while macOS has it off is worse than a short message. The call is made only on a change, so saving the window does not touch the login items every time.

**Note:** under `native:serve` this registers the development Electron binary, so the real check belongs to the signed DMG in M7.

## 2026-10-08: The settings window has a Save button

**Choice:** the settings window is its own 440 by 680 window, not resizable, with a Save button at the bottom. "Update catalog now" downloads from the saved URL while the user waits, which takes 5 seconds at most.

**Why:** the values depend on each other, for example a catalog URL and the update button. One save with one validation answer is easier to follow than a save per field. A synchronous update lets the window show the new date at once.

## 2026-10-08: Privacy and credits in About wait for M7

**Choice:** About in M6 shows the version, the GitHub link and "Not affiliated with Spotify". The privacy line, the `PRIVACY.md` link and the Credits section are left to M7.

**Why:** requirements 7.2 lists all of them, but the build plan gives the privacy link and the curators to M7 by name, and the build plan decides the order. M7 also reviews `PRIVACY.md` against what the app does, so the link and the review land together.

**Version:** About reads `nativephp.version`, which the release build sets from the tag. Nothing commits a version.

## 2026-10-08: Settings window code lives in app/SettingsWindow

**Choice:** the service behind the settings controller, the values it shows and saves, and the `LoginItem` interface sit in `app/SettingsWindow/`. `SettingsStore` and `SettingKey` stay in `app/Settings/`.

**Why:** the same shape as `app/MenuBar/`. `app/Settings/` is the store that every part of the app uses. The window is one user of it.

## 2026-10-09: A missed reminder is sent up to 4 hours late

**Choice:** `reminder:send` still runs every minute. `DailyReminder` answers yes when all of these hold: the chosen time has passed, it is no more than `DailyReminder::CATCH_UP_HOURS` (4) hours ago, it is still the same local day, and no reminder was sent today. The last minute counts in full, so with 09:00 the window ends at 13:00:59. The reminder names the pick of the day it is sent. Disabled, weekdays only and "no pick, no reminder" work as before.

**Why 4 hours, not 2:** this fixes a reminder that silently did not arrive, so the error to avoid is no reminder, not a late one. One banner at 12:30 saying today's pick is ready costs almost nothing. Silence costs the feature. With 2 hours, a slow start at 11:30 still gets nothing, which is the complaint this fix answers.

**Why not until the end of the day:** the user chose the time on purpose. A banner at 22:00 about a 09:00 habit is the same failure from the other side.

**Same day:** the window never passes midnight. With 22:00 chosen, 23:59 still gets the reminder, 01:00 the next day does not. Yesterday's reminder never arrives in the night. "Day" is the local calendar date from `Today` (`LocalToday` in the app), never UTC.

**Sent today:** the local date of the last reminder is stored as `SettingKey::ReminderSentOn`, through `SettingsStore` like every other stored value. It is not a choice the user makes, so the settings window does not show it. The command stores it only after the notification was shown. If showing it fails, the next minute in the window tries again. The cost is at most one log line a minute for 4 hours, on a Mac where notifications do not work at all.

**Side effect:** turning the reminder on, or moving the time earlier, inside the window sends the reminder at once. For example, turn it on at 10:00 with 09:00 chosen, and it arrives in the next minute. That is accepted, as it is the same banner the user just asked for.

**Replaces:** the "chosen minute only" rule of the 2026-10-08 entry.

## 2026-10-09: The Laravel auth scaffolding is removed

**Choice:** the `User` model, its factory, the seeder that used it, `config/auth.php`, and the `users` and `password_reset_tokens` tables are gone. The `sessions` table stays, in a migration renamed to `create_sessions_table`, because the session driver is the database. A test checks that the account tables stay out.

**Why:** the app has no accounts (`AGENTS.md` rule 3). The scaffolding came with the Laravel skeleton in M0 and nothing used it. Code that is never used still has to be read, analysed and kept up to date.

**Existing databases:** the renamed migration creates the table only when it is missing. A database made before this change already has it, from the old users migration, so migrating does not fail. The old `users` and `password_reset_tokens` tables stay in such a database. They are empty and nothing reads them. No release has shipped, so no user has one.
