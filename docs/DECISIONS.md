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
