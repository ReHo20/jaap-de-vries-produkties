<?php

declare(strict_types=1);

/**
 * Print WordPress authentication keys and salts as dotenv lines.
 *
 * Usage:
 *   composer generate-salts >> .env
 *
 * Replaces pollen-solutions/wp-salt, which pulled the abandoned laminas/laminas-math
 * (capped at ~8.4.0) and was the fleet's only blocker for PHP 8.5.
 *
 * Values are hex, so they never contain a character dotenv would need escaped —
 * unlike the punctuation-heavy alphabet wp-salt used.
 */
const KEYS = [
    'AUTH_KEY',
    'SECURE_AUTH_KEY',
    'LOGGED_IN_KEY',
    'NONCE_KEY',
    'AUTH_SALT',
    'SECURE_AUTH_SALT',
    'LOGGED_IN_SALT',
    'NONCE_SALT',
];

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");

    exit(1);
}

foreach (KEYS as $key) {
    printf("%s='%s'%s", $key, bin2hex(random_bytes(32)), PHP_EOL);
}
