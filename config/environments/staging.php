<?php

declare(strict_types=1);

/**
 * Configuration overrides for WP_ENV === 'staging'.
 *
 * Keep staging as close to production as possible. Override with `Config::define`
 * only when you must.
 *
 * Example: `Config::define('WP_DEBUG', true);`
 */

use Roots\WPConfig\Config;

Config::define('DISALLOW_INDEXING', true);
