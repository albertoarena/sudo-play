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
