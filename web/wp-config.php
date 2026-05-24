<?php
/**
 * Bedrock — Config bootstrapper
 *
 * Bedrock requires its custom wp-config.php here in web/ which loads
 * config/application.php through the autoloader.
 */

if (!defined('ABSPATH')) {
    /**
     * Directory containing all of the site's files
     *
     * @var string
     */
    $root_dir = dirname(__DIR__);

    /**
     * Document Root
     *
     * @var string
     */
    $webroot_dir = $root_dir . '/web';

    /**
     * Load Composer autoloader
     */
    require_once $root_dir . '/vendor/autoload.php';

    /**
     * Load environment variables from .env
     */
    if (file_exists($root_dir . '/.env')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir);
        $dotenv->load();
    }

    /**
     * Load Bedrock application config
     */
    require_once $root_dir . '/config/application.php';
}

require_once ABSPATH . 'wp-settings.php';
