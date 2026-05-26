<?php
// FrankenPHP worker for Bedrock/WordPress.
// Sits outside web/ so it can't be served as a script.

ignore_user_abort(true);

$handler = static function (): void {
    require __DIR__ . '/web/index.php';
};

for ($running = true; $running;) {
    $running = \frankenphp_handle_request($handler);
    gc_collect_cycles();
}
