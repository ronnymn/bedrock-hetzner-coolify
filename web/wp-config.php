<?php
/**
 * Bedrock — Config bootstrapper
 *
 * WP's wp-load.php defines ABSPATH before requiring this file, so the
 * config-loading block must NOT be wrapped in `if (!defined('ABSPATH'))` —
 * doing so silently skips the entire Bedrock config, leaving WP with no
 * DB credentials.
 */

$root_dir = dirname(__DIR__);

require_once $root_dir . '/vendor/autoload.php';

if (file_exists($root_dir . '/.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir);
    $dotenv->load();
}

require_once $root_dir . '/config/application.php';

require_once ABSPATH . 'wp-settings.php';
