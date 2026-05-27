<?php
/**
 * Plugin Name: S3 Uploads — Bunny.net compatibility
 * Description: Configures humanmade/s3-uploads for Bunny.net storage:
 *   - path-style URLs (Bunny requires; vendor README confirms)
 *   - relaxed checksums (AWS SDK 3.337+ default breaks Bunny's S3 API)
 */

if (!defined('S3_UPLOADS_BUCKET')) {
    return;
}

add_filter('s3_uploads_s3_client_params', function (array $params): array {
    $params['use_path_style_endpoint']    = true;
    $params['request_checksum_calculation']  = 'when_required';
    $params['response_checksum_validation']  = 'when_required';
    return $params;
});
