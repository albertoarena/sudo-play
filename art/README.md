# Art

The Sudo Play mark: a dim terminal prompt followed by three green equaliser
bars. It says "terminal" and "music" in two shapes, and uses the same pairing
as the command line header in the app: a dim sign, a bright green command.

| File | What it is |
| --- | --- |
| `icon/sudo-play-icon.svg` | The app icon source, 1024 by 1024 on the macOS grid (an 824 body, 100 margin) |
| `icon/sudo-play-icon-1024.png` | The app icon exported at 1024 |
| `icon/SudoPlay.icns` | The app icon for macOS, every size from 16 to 1024, built with `iconutil` |
| `menu-bar/menu-bar-template-1x.svg` | The menu bar mark at 1x, drawn on a 22 by 16 pixel grid |
| `menu-bar/menu-bar-template-2x.svg` | The menu bar mark at 2x, drawn on a 44 by 32 pixel grid |

The menu bar images the app loads are `public/menuBarIconTemplate.png` and
`public/menuBarIconTemplate@2x.png`, exported from the two menu bar sources.
They are black on transparent. macOS reads the `Template` in the name and
tints them for light and dark menu bars.

Each size of the menu bar mark is drawn separately on its own pixel grid, not
scaled from the other, so the bars stay sharp at 16 pixels.

## Colours

From the scheme in `resources/css/app.css`: panel `#09090b`, prompt `#9f9fa9`,
bars `#00d492`.

## Exporting again

Render each SVG at its exact size in a browser with a transparent background,
then rebuild the `.icns` from an iconset:

    mkdir SudoPlay.iconset
    # icon_16x16.png, icon_16x16@2x.png, ... icon_512x512@2x.png
    iconutil -c icns SudoPlay.iconset -o SudoPlay.icns

`iconutil` exists only on macOS.
