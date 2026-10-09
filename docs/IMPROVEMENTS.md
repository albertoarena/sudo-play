# Improvements

Ideas and polish noticed while using the app, kept here so they are not lost and
not confused with the build plan.

This file is not a source of truth and nothing here is committed to. It is not a
bug list either: anything broken goes in a milestone or a fix branch. What lands
here is the smaller kind of thing, worth doing when the surrounding work is open
anyway, or worth deciding not to do.

Each entry says what was seen, why it matters, and where it would be changed.
Entries are removed when they ship or are rejected, with the reason.

## Menu bar

### The Quit item needs a better icon and a shortcut

Noticed 2026-10-08, running `native:serve` on macOS.

The menu shows a boxed X beside "Quit", which reads as "close" or "cancel"
rather than "quit the application". Docker Desktop uses a power symbol and shows
the `⌘Q` shortcut beside the item, which is the convention macOS users expect
from a menu bar app.

**Why it matters:** the menu is short, so each item carries weight. An icon that
suggests "close this window" next to an action that quits the whole app is the
kind of small wrongness that makes an app feel unfinished.

**Where:** `NativeAppServiceProvider`, where the menu is built. Check what the
current NativePHP version supports for item icons and accelerators before
assuming either is available.

### The menu shows the development app name

Noticed 2026-10-08, in the same screenshot: the item reads "Quit sudo-play-dev",
not "Quit Sudo Play".

That is presumably the dev build's name and should not survive into a packaged
build, but it is worth confirming during M7 rather than discovering it on a
signed DMG.

**Where:** the app name in the NativePHP configuration and the build
configuration, and the M7 release checks.

## Database

### The sessions table still has a user_id column

Noticed 2026-10-09, while removing the authentication scaffolding.

The app has no accounts, so nothing ever writes a user id, yet the `sessions`
table still carries a nullable `user_id`. In a project whose README says no
accounts, that column invites a question it cannot answer.

**Why it is still there:** removing it would break sessions at runtime rather
than in the tests. Laravel's `DatabaseSessionHandler::addUserInformation()`
sets `$payload['user_id']` whenever `Guard` is bound in the container, which it
still is even with no authentication configured. The write would then fail
against a table with no such column, and no test would catch it because the
session driver is not exercised the same way there.

**What it would take:** either stop `Guard` being bound at all, which means
understanding what else in the framework assumes it, or move sessions off the
database driver, which M7 should not be tempted into because the writability of
a session path inside a signed and notarized app is its own question.

**Verdict for now:** leave it. One nullable column nobody writes to costs
nothing, and the alternative risks a failure that only appears in a packaged
build.
