<?php

declare(strict_types=1);

/**
 * Configuration overrides for WP_ENV === 'development'.
 */

use Roots\WPConfig\Config;

use function Env\env;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);
Config::define('DISALLOW_INDEXING', true);

ini_set('display_errors', '1');

// Allow plugin/theme installation from the admin while developing.
Config::define('DISALLOW_FILE_MODS', false);

// Serving a cached page while you are editing the thing that produced it wastes more
// time than the cache saves. Flip this to true temporarily to test cache behaviour.
Config::define('WP_CACHE', false);
