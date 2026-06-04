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
// Only activate when all required credentials are present — humanmade/s3-uploads
// checks `defined('S3_UPLOADS_BUCKET')` to decide whether to hook into uploads,
// so leaving the constants undefined cleanly disables the plugin.
if (env('S3_UPLOADS_BUCKET') && env('S3_UPLOADS_KEY') && env('S3_UPLOADS_SECRET')) {
    Config::define('S3_UPLOADS_BUCKET',     env('S3_UPLOADS_BUCKET'));
    Config::define('S3_UPLOADS_REGION',     env('S3_UPLOADS_REGION')   ?: 'de');
    Config::define('S3_UPLOADS_KEY',        env('S3_UPLOADS_KEY'));
    Config::define('S3_UPLOADS_SECRET',     env('S3_UPLOADS_SECRET'));
    Config::define('S3_UPLOADS_ENDPOINT',   env('S3_UPLOADS_ENDPOINT') ?: 'https://storage.bunnycdn.com');
    Config::define('S3_UPLOADS_BUCKET_URL', env('S3_UPLOADS_BUCKET_URL'));
}

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
Config::define(
    'DISALLOW_FILE_MODS',
    env('DISALLOW_FILE_MODS') !== null
        ? filter_var(env('DISALLOW_FILE_MODS'), FILTER_VALIDATE_BOOLEAN)
        : $is_production
);
Config::define('FORCE_SSL_ADMIN',     $is_production);
Config::define('WP_AUTO_UPDATE_CORE', $is_development ? true : 'minor');

// Use direct filesystem access — container's www-data owns the files,
// so WP can write uploads/generated assets without prompting for FTP creds.
Config::define('FS_METHOD', 'direct');

// ─── Debug ───────────────────────────────────────────────────────────────────
Config::define('WP_DEBUG',         !$is_production);
Config::define('WP_DEBUG_LOG',     !$is_production);
Config::define('WP_DEBUG_DISPLAY', !$is_production);
Config::define('SCRIPT_DEBUG',     !$is_production);

// ─── Limits ──────────────────────────────────────────────────────────────────
Config::define('WP_MEMORY_LIMIT',     '256M');
Config::define('WP_MAX_MEMORY_LIMIT', '512M');

// ─── Multisite (env-gated; no-op when WP_ALLOW_MULTISITE unset) ───────────────
// Two-phase enable:
//   Phase 1 — set WP_ALLOW_MULTISITE=true, deploy, run Network Setup in wp-admin.
//   Phase 2 — add MULTISITE=true + DOMAIN_CURRENT_SITE, deploy.
// This network is subdomain-only, so SUBDOMAIN_INSTALL is fixed true.
if (filter_var(env('WP_ALLOW_MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
    Config::define('WP_ALLOW_MULTISITE', true);

    if (filter_var(env('MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
        Config::define('MULTISITE',            true);
        Config::define('SUBDOMAIN_INSTALL',    true);
        Config::define('DOMAIN_CURRENT_SITE',  env('DOMAIN_CURRENT_SITE'));
        Config::define('PATH_CURRENT_SITE',    env('PATH_CURRENT_SITE') ?: '/');
        Config::define('SITE_ID_CURRENT_SITE', (int) (env('SITE_ID_CURRENT_SITE') ?: 1));
        Config::define('BLOG_ID_CURRENT_SITE', (int) (env('BLOG_ID_CURRENT_SITE') ?: 1));
    }
}

Config::apply();

if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
