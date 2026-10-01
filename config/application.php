<?php

declare(strict_types=1);

/**
 * Base production configuration. Environment-specific overrides go in
 * config/environments/{{WP_ENV}}.php.
 *
 * Deviate from production as little as possible — define as much as you can here.
 */

use Roots\WPConfig\Config;

use function Env\env;

/** Directory containing all the site's files */
$root_dir = dirname(__DIR__);

/** Document root */
$webroot_dir = $root_dir . '/web';

/*
 * Load .env (.env.local overrides it when present).
 */
if (file_exists($root_dir . '/.env')) {
    $env_files = file_exists($root_dir . '/.env.local') ? ['.env', '.env.local'] : ['.env'];

    $dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir, $env_files, false);
    $dotenv->load();

    $dotenv->required(['WP_HOME', 'WP_SITEURL']);

    if (!env('DATABASE_URL')) {
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
    }
}

/*
 * Environment. Defaults to production so a missing WP_ENV never exposes a site.
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

/* Infer WP_ENVIRONMENT_TYPE from WP_ENV */
if (!env('WP_ENVIRONMENT_TYPE') && in_array(WP_ENV, ['production', 'staging', 'development', 'local'], true)) {
    Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
}

/* URLs */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/* Custom content directory */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/*
 * Default theme. There is deliberately no register-theme-directory mu-plugin: themes
 * live in WP_CONTENT_DIR/themes, which WordPress finds natively, so only the default
 * needs declaring. Not read from .env, because the active theme is the same in every
 * environment. `sightline` is the directory name Composer pins via installer-name.
 */
Config::define('WP_DEFAULT_THEME', 'sightline');

/* Database */
if (env('DB_SSL')) {
    Config::define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}

Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

if (env('DATABASE_URL')) {
    $dsn = (object) parse_url(env('DATABASE_URL'));

    Config::define('DB_NAME', substr($dsn->path, 1));
    Config::define('DB_USER', $dsn->user);
    Config::define('DB_PASSWORD', $dsn->pass ?? null);
    Config::define('DB_HOST', isset($dsn->port) ? "{$dsn->host}:{$dsn->port}" : $dsn->host);
}

/*
 * Authentication keys and salts. Generate with `composer generate-salts >> .env`.
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/* Custom settings */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);

// No plugin/theme file editing in the admin.
Config::define('DISALLOW_FILE_EDIT', true);

// No plugin/theme installation or updates from the admin — Composer owns dependencies.
Config::define('DISALLOW_FILE_MODS', true);

Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?? true);

/* Debugging */
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('WP_DEBUG_LOG', false);
Config::define('SCRIPT_DEBUG', false);
ini_set('display_errors', '0');

/*
 * Advanced Custom Fields PRO.
 *
 * This activates the plugin at runtime. It is NOT the credential Composer uses to
 * download the package — that is HTTP-basic auth configured globally per machine:
 *
 *   composer config --global http-basic.connect.advancedcustomfields.com <KEY> http://localhost
 */
Config::define('ACF_PRO_LICENSE', env('ACF_PRO_LICENSE'));

/* Contact Form 7 */
Config::define('WPCF7_RECAPTCHA_SITEKEY', env('RECAPTCHA_SITEKEY'));
Config::define('WPCF7_RECAPTCHA_SECRET', env('RECAPTCHA_SECRET'));

/* TinyPNG */
Config::define('TINY_API_KEY', env('TINY_API_KEY'));

/*
 * WP Rocket. Optional, and not required by this base — a licence is bought per client,
 * so it is added to the client project rather than inherited by every clone. See
 * docs/deploying.md.
 *
 * WP_CACHE is what makes web/app/advanced-cache.php run at all. Without it the drop-in
 * that post-install-cmd copies into place is dead code: WordPress never loads it, the
 * output buffer never starts, and every buffer-based feature — page cache, lazyload,
 * delayed JavaScript — silently does nothing while the settings screen says otherwise.
 * Defaults to on, because production is the case that matters; development turns it off.
 *
 * These stay defined when the plugin is absent. There is then no drop-in for WP_CACHE to
 * load and nothing reads the credentials, so the cost is three unused constants — the
 * alternative is config that changes shape depending on which plugins a site happens to
 * have, which is harder to reason about than a constant that does nothing.
 */
Config::define('WP_CACHE', env('WP_CACHE') ?? true);
Config::define('WP_ROCKET_EMAIL', env('WP_ROCKET_EMAIL'));
Config::define('WP_ROCKET_KEY', env('WP_ROCKET_KEY'));

/*
 * Let WordPress detect HTTPS behind a reverse proxy or load balancer.
 *
 * @see https://developer.wordpress.org/reference/functions/is_ssl/
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/* Bootstrap WordPress */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
