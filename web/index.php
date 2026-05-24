<?php
/**
 * WordPress view bootstrapper for Bedrock
 *
 * Loads WordPress core from /web/wp/
 */

define('WP_USE_THEMES', true);

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp/wp-blog-header.php';
