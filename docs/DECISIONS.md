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
