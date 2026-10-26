<?php

declare(strict_types=1);

use Native\Desktop\Drivers\Electron\Traits\InstallsAppIcon;

/*
 * The app icon is not set in config/nativephp.php. NativePHP copies fixed file
 * names from public/ into its Electron build folder, and electron-builder takes
 * the macOS icon from there. So the build uses our icon only if these files
 * exist under the names NativePHP looks for, and match the art in art/icon.
 */

it('uses the file names NativePHP copies into the build', function (): void {
    $source = (string) file_get_contents(
        (string) (new ReflectionClass(InstallsAppIcon::class))->getFileName()
    );

    expect($source)
        ->toContain("public_path('icon.icns')")
        ->toContain("public_path('icon.png')");
});

it('ships the macOS icon from the art', function (): void {
    expect(public_path('icon.icns'))->toBeFile()
        ->and(md5_file(public_path('icon.icns')))
        ->toBe(md5_file(base_path('art/icon/SudoPlay.icns')));
});

it('ships the 1024 pixel icon from the art', function (): void {
    expect(public_path('icon.png'))->toBeFile()
        ->and(md5_file(public_path('icon.png')))
        ->toBe(md5_file(base_path('art/icon/sudo-play-icon-1024.png')));

    [$width, $height] = getimagesize(public_path('icon.png')) ?: [0, 0];

    expect([$width, $height])->toBe([1024, 1024]);
});
