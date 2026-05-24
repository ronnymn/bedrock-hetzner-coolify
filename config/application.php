<?php
/**
 * Bedrock application configuration
 * Hetzner + Coolify + Bunny.net Object Storage
 */

use Roots\WPConfig\Config;
use function Env\env;

// ─── Directories ────────────────────────────────────────────────────────────
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', dirname(__DIR__) . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

// ─── Database ────────────────────────────────────────────────────────────────
Config::define('DB_NAME',     env('DB_NAME'));
Config::define('DB_USER',     env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST',     env('DB_HOST') ?: 'mysql');
Config::define('DB_CHARSET',  'utf8mb4');
Config::define('DB_COLLATE',  '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

// ─── DEBUG: log the DB connection attempt ────────────────────────────────────
$debug_log = '/tmp/db-debug.log';
$debug_msg = sprintf(
    "[%s] DB_HOST=%s DB_USER=%s DB_NAME=%s DB_PASSWORD_LEN=%d\n",
    date('c'),
    env('DB_HOST') ?: 'mysql',
    env('DB_USER'),
    env('DB_NAME'),
    strlen((string) env('DB_PASSWORD'))
);

$mysqli = @new mysqli(
    env('DB_HOST') ?: 'mysql',
    env('DB_USER'),
    env('DB_PASSWORD'),
    env('DB_NAME')
);
if ($mysqli->connect_error) {
    $debug_msg .= "  CONNECT_ERROR: " . $mysqli->connect_error . "\n";
} else {
    $debug_msg .= "  CONNECT_OK\n";
}
file_put_contents($debug_log, $debug_msg, FILE_APPEND);
error_log($debug_msg);

// ─── URLs ────────────────────────────────────────────────────────────────────
Config::define('WP_HOME',    env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL') ?: env('WP_HOME') . '/wp');

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

// ─── Redis Object Cache ───────────────────────────────────────────────────────
if (env('REDIS_HOST')) {
    Config::define('WP_REDIS_HOST',     env('REDIS_HOST'));
    Config::define('WP_REDIS_PORT',     env('REDIS_PORT') ?: 6379);
    Config::define('WP_REDIS_DATABASE', env('REDIS_DB')   ?: 0);
    Config::define('WP_REDIS_TIMEOUT',  1);
    Config::define('WP_REDIS_READ_TIMEOUT', 1);
}

// ─── Performance ──────────────────────────────────────────────────────────────
Config::define('WP_CACHE',           true);
Config::define('COMPRESS_CSS',       $is_production);
Config::define('COMPRESS_SCRIPTS',   $is_production);
Config::define('CONCATENATE_SCRIPTS', false);
Config::define('ENFORCE_GZIP',       true);

// ─── Security ────────────────────────────────────────────────────────────────
Config::define('DISALLOW_FILE_EDIT',    true);
Config::define('DISALLOW_FILE_MODS',    $is_production);
Config::define('FORCE_SSL_ADMIN',       $is_production);
Config::define('WP_AUTO_UPDATE_CORE',   $is_development ? true : 'minor');

// ─── Debug — TEMPORARILY ON ──────────────────────────────────────────────────
Config::define('WP_DEBUG',         true);
Config::define('WP_DEBUG_LOG',     true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('SCRIPT_DEBUG',     true);

// ─── Limits ───────────────────────────────────────────────────────────────────
Config::define('WP_MEMORY_LIMIT',     '256M');
Config::define('WP_MAX_MEMORY_LIMIT', '512M');

Config::apply();

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/web/wp/');
}
