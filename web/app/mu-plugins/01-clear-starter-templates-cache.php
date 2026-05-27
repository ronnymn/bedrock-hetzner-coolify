<?php
/**
 * Plugin Name: Clear Starter Templates Cache (one-shot)
 * Description: Wipes Astra Starter Templates transients on every admin_init so the catalog re-fetches. Remove this file once the templates list loads.
 */

add_action('admin_init', function () {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '\\_transient\\_astra%'
            OR option_name LIKE '\\_transient\\_timeout\\_astra%'
            OR option_name LIKE '\\_transient\\_ast%'
            OR option_name LIKE '\\_transient\\_timeout\\_ast%'
            OR option_name LIKE '\\_transient\\_starter%'
            OR option_name LIKE '\\_transient\\_timeout\\_starter%'"
    );
});
