<?php
/**
 * Bedrock application configuration
 * Hetzner + Coolify + Bunny.net Object Storage
 */

use Roots\WPConfig\Config;
use function Env\env;

$root_dir    = dirname(__DIR__);
$webroot_dir = $root_dir . '/web';

// ─── Reverse proxy: trust X-Forwarded-Proto from Coolify/Traefik ─────────────
// Traefik terminates TLS and forwards as HTTP; without this WP loops trying
// to upgrade wp-admin to HTTPS.
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// ─── URLs ────────────────────────────────────────────────────────────────────
Config::define('WP_HOME',    env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL') ?: env('WP_HOME') . '/wp');

// ─── Directories ─────────────────────────────────────────────────────────────
Config::define('CONTENT_DIR',    '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

// ─── Database ────────────────────────────────────────────────────────────────
Config::define('DB_NAME',     env('DB_NAME'));
Config::define('DB_USER',     env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST',     env('DB_HOST') ?: 'mysql');
Config::define('DB_CHARSET',  'utf8mb4');
Config::define('DB_COLLATE',  '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

// ─── Environment ─────────────────────────────────────────────────────────────
define('WP_ENV', env('WP_ENV') ?: 'production');

$is_production  = WP_ENV === 'production';
$is_staging     = WP_ENV === 'staging';
$is_development = WP_ENV === 'development';

// ─── Auth Keys & Salts ───────────────────────────────────────────────────────
Config::define('AUTH_KEY',         env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY',  env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY',    env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY',        env('NONCE_KEY'));
Config::define('AUTH_SALT',        env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT',   env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT',       env('NONCE_SALT'));

// ─── Bunny.net Object Storage (via S3 Uploads) ───────────────────────────────
Config::define('S3_UPLOADS_BUCKET',     env('S3_UPLOADS_BUCKET'));
Config::define('S3_UPLOADS_REGION',     env('S3_UPLOADS_REGION')   ?: 'eu-central-1');
Config::define('S3_UPLOADS_KEY',        env('S3_UPLOADS_KEY'));
Config::define('S3_UPLOADS_SECRET',     env('S3_UPLOADS_SECRET'));
Config::define('S3_UPLOADS_ENDPOINT',   env('S3_UPLOADS_ENDPOINT') ?: 'https://storage.bunnycdn.com');
Config::define('S3_UPLOADS_BUCKET_URL', env('S3_UPLOADS_BUCKET_URL'));

// ─── Redis Object Cache ──────────────────────────────────────────────────────
if (env('REDIS_HOST')) {
    Config::define('WP_REDIS_HOST',         env('REDIS_HOST'));
    Config::define('WP_REDIS_PORT',         env('REDIS_PORT') ?: 6379);
    Config::define('WP_REDIS_DATABASE',     env('REDIS_DB')   ?: 0);
    Config::define('WP_REDIS_TIMEOUT',      1);
    Config::define('WP_REDIS_READ_TIMEOUT', 1);
}

// ─── Performance ─────────────────────────────────────────────────────────────
Config::define('WP_CACHE',            true);
Config::define('COMPRESS_CSS',        $is_production);
Config::define('COMPRESS_SCRIPTS',    $is_production);
Config::define('CONCATENATE_SCRIPTS', false);
Config::define('ENFORCE_GZIP',        true);

// ─── Security ────────────────────────────────────────────────────────────────
Config::define('DISALLOW_FILE_EDIT',  true);
Config::define('DISALLOW_FILE_MODS',  $is_production);
Config::define('FORCE_SSL_ADMIN',     $is_production);
Config::define('WP_AUTO_UPDATE_CORE', $is_development ? true : 'minor');

// ─── Debug ───────────────────────────────────────────────────────────────────
Config::define('WP_DEBUG',         !$is_production);
Config::define('WP_DEBUG_LOG',     !$is_production);
Config::define('WP_DEBUG_DISPLAY', !$is_production);
Config::define('SCRIPT_DEBUG',     !$is_production);

// ─── Limits ──────────────────────────────────────────────────────────────────
Config::define('WP_MEMORY_LIMIT',     '256M');
Config::define('WP_MAX_MEMORY_LIMIT', '512M');

Config::apply();

if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
