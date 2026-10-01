<?php

declare(strict_types=1);

/**
 * Copy WP Rocket's advanced-cache drop-in into WP_CONTENT_DIR.
 *
 * Run automatically by composer's post-install-cmd / post-update-cmd.
 *
 * v1 did this with a bare `cp`, which fails on Windows and fails the whole install
 * when WP Rocket is not present (a --no-dev install on a site that dropped it, or any
 * install before the plugin has been fetched). This is a no-op when the source is
 * missing.
 */
$root = dirname(__DIR__);
$source = $root . '/web/app/plugins/wp-rocket/views/cache/advanced-cache.php';
$target = $root . '/web/app/advanced-cache.php';

if (!is_file($source)) {
    fwrite(STDOUT, "WP Rocket not installed — skipping advanced-cache.php.\n");

    exit(0);
}

if (!copy($source, $target)) {
    fwrite(STDERR, "Failed to copy advanced-cache.php into web/app/.\n");

    exit(1);
}

fwrite(STDOUT, "Copied advanced-cache.php into web/app/.\n");
