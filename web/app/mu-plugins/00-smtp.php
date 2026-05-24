<?php
/**
 * Plugin Name: SMTP via coolify-smtp-relay
 * Description: Routes wp_mail() through the shared Postfix relay container
 *              (`relay:25` on the Coolify docker network). The relay handles
 *              Brevo auth, rate limiting, and the daily quota — apps don't
 *              hold any credentials and don't see Brevo directly.
 * Author:      ronny
 *
 * Behaviour: only takes effect when SMTP_HOST is set. In local dev with no
 * SMTP_HOST env var (e.g. `composer install` + `wp` on a laptop), wp_mail()
 * falls back to PHP's default mail() — which usually does nothing locally,
 * which is what you want.
 */

if (defined('ABSPATH') === false) {
    exit;
}

add_action('phpmailer_init', function ($mail) {
    $host = getenv('SMTP_HOST') ?: '';
    if ($host === '') {
        return;
    }

    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->Port       = (int) (getenv('SMTP_PORT') ?: 25);
    $mail->SMTPAuth   = false;
    $mail->SMTPSecure = false;          // internal docker network only
    $mail->SMTPAutoTLS = false;
    $mail->XMailer    = ' ';            // suppress PHPMailer version header
});

add_filter('wp_mail_from', function ($from) {
    return getenv('MAIL_FROM') ?: $from;
});

add_filter('wp_mail_from_name', function ($name) {
    return getenv('MAIL_FROM_NAME') ?: $name;
});
