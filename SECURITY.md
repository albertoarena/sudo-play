# Security

## Reporting a problem

Please do not open a public issue for a security problem.

Report it privately instead. On GitHub, open the **Security** tab of this repository and choose **Report a vulnerability**. If that does not work for you, write to **hello@albertoarena.it**.

Say what you found, how to reproduce it, and which version of Sudo Play you ran. You will get a reply within a week. Once a fix is released, you are credited in the release notes unless you would rather not be.

## Supported versions

Only the latest release gets security fixes. There is one signed build at a time, so the fix is to update.

## What counts

Sudo Play is small on purpose. It downloads one public catalog file and opens Spotify playlist links. Things worth reporting include:

- A catalog entry that makes the app open anything other than an `https://open.spotify.com/playlist/` link
- A catalog file that crashes the app, hangs it, or gets past the download size limit or timeout
- The app running a command or AppleScript built from catalog text
- The app sending anything over the network other than the catalog request described in [PRIVACY.md](PRIVACY.md)
- A build that is not signed and notarized, or that Gatekeeper warns about

A playlist that is offensive, private or wrong is not a security problem. Open an issue or a pull request for it, see [CONTRIBUTING.md](CONTRIBUTING.md).
