<?php
/**
 * Media endpoints - upload, edit, organize media files
 */

defined('ABSPATH') || exit;

class MCP_Media_Endpoint {

    public function register_routes(): void {
        // List media
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media', [
            'methods' => 'GET',
            'callback' => [$this, 'list_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'per_page' => ['type' => 'integer', 'default' => 20],
                'page' => ['type' => 'integer', 'default' => 1],
                'mime_type' => ['type' => 'string'],
                'search' => ['type' => 'string'],
                'orderby' => ['type' => 'string', 'default' => 'date'],
                'order' => ['type' => 'string', 'default' => 'DESC'],
            ],
        ]);

        // Get single media item
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Upload media
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/upload', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Upload from URL
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/sideload', [
            'methods' => 'POST',
            'callback' => [$this, 'sideload_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'url' => ['required' => true, 'type' => 'string'],
                'filename' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'alt' => ['type' => 'string'],
                'caption' => ['type' => 'string'],
            ],
        ]);

        // Update media metadata
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete media
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'force' => ['type' => 'boolean', 'default' => true],
            ],
        ]);

        // Bulk delete media
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/bulk-delete', [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_delete_media'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'ids' => ['required' => true, 'type' => 'array'],
                'force' => ['type' => 'boolean', 'default' => true],
            ],
        ]);

        // Regenerate thumbnails
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/(?P<id>\d+)/regenerate', [
            'methods' => 'POST',
            'callback' => [$this, 'regenerate_thumbnails'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get upload stats
        register_rest_route(MCP_Endpoints::NAMESPACE, '/media/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);
    }

    public function list_media(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => absint($request->get_param('per_page')),
            'paged' => absint($request->get_param('page')),
            'orderby' => sanitize_key($request->get_param('orderby')),
            'order' => strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC',
        ];

        if ($request->has_param('mime_type') && $request->get_param('mime_type')) {
            $args['post_mime_type'] = sanitize_text_field($request->get_param('mime_type'));
        }

        if ($request->has_param('search') && $request->get_param('search')) {
            $args['s'] = sanitize_text_field($request->get_param('search'));
        }

        $query = new WP_Query($args);
        $media = [];

        foreach ($query->posts as $attachment) {
            $media[] = $this->format_media($attachment);
        }

        return MCP_Endpoints::success([
            'media' => $media,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
            'page' => absint($request->get_param('page')),
        ]);
    }

    public function get_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = absint($request->get_param('id'));
        $attachment = get_post($id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return MCP_Endpoints::error("Media not found", 'not_found', 404);
        }

        return MCP_Endpoints::success($this->format_media($attachment, true));
    }

    public function upload_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return MCP_Endpoints::error("No file uploaded", 'no_file', 400);
        }

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Update metadata if provided
        $params = $request->get_params();
        $this->update_attachment_data($attachment_id, $params);

        return MCP_Endpoints::success([
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'uploaded' => true,
        ]);
    }

    public function sideload_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $url = esc_url_raw($request->get_param('url'));
        $filename = $request->get_param('filename');

        // Download file
        $temp_file = download_url($url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Determine filename
        if (!$filename) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
        }

        $file_array = [
            'name' => sanitize_file_name($filename),
            'tmp_name' => $temp_file,
        ];

        $attachment_id = media_handle_sideload($file_array, 0);

        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            return $attachment_id;
        }

        // Update metadata if provided
        $params = $request->get_params();
        $this->update_attachment_data($attachment_id, $params);

        return MCP_Endpoints::success([
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'uploaded' => true,
            'source_url' => $url,
        ]);
    }

    public function update_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = absint($request->get_param('id'));
        $attachment = get_post($id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return MCP_Endpoints::error("Media not found", 'not_found', 404);
        }

        $post_data = ['ID' => $id];

        if ($request->has_param('title')) {
            $post_data['post_title'] = sanitize_text_field($request->get_param('title'));
        }
        if ($request->has_param('caption')) {
            $post_data['post_excerpt'] = sanitize_textarea_field($request->get_param('caption'));
        }
        if ($request->has_param('description')) {
            $post_data['post_content'] = sanitize_textarea_field($request->get_param('description'));
        }

        if (count($post_data) > 1) {
            wp_update_post($post_data);
        }

        if ($request->has_param('alt')) {
            update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($request->get_param('alt')));
        }

        return MCP_Endpoints::success([
            'id' => $id,
            'updated' => true,
        ]);
    }

    public function delete_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = absint($request->get_param('id'));
        $force = $request->get_param('force');

        $attachment = get_post($id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return MCP_Endpoints::error("Media not found", 'not_found', 404);
        }

        $result = wp_delete_attachment($id, $force);

        if (!$result) {
            return MCP_Endpoints::error("Failed to delete media", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $id,
            'deleted' => true,
        ]);
    }

    public function bulk_delete_media(WP_REST_Request $request): WP_REST_Response {
        $ids = $request->get_param('ids');
        $force = $request->get_param('force');

        $deleted = [];
        $failed = [];

        foreach ($ids as $id) {
            $id = absint($id);
            $attachment = get_post($id);

            if (!$attachment || $attachment->post_type !== 'attachment') {
                $failed[] = $id;
                continue;
            }

            $result = wp_delete_attachment($id, $force);
            if ($result) {
                $deleted[] = $id;
            } else {
                $failed[] = $id;
            }
        }

        return MCP_Endpoints::success([
            'deleted' => $deleted,
            'failed' => $failed,
            'deleted_count' => count($deleted),
        ]);
    }

    public function regenerate_thumbnails(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $id = absint($request->get_param('id'));
        $attachment = get_post($id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return MCP_Endpoints::error("Media not found", 'not_found', 404);
        }

        if (!wp_attachment_is_image($id)) {
            return MCP_Endpoints::error("Not an image", 'not_image', 400);
        }

        $file = get_attached_file($id);

        if (!$file || !file_exists($file)) {
            return MCP_Endpoints::error("Original file not found", 'file_not_found', 404);
        }

        // Regenerate metadata
        $metadata = wp_generate_attachment_metadata($id, $file);

        if (is_wp_error($metadata)) {
            return $metadata;
        }

        wp_update_attachment_metadata($id, $metadata);

        return MCP_Endpoints::success([
            'id' => $id,
            'regenerated' => true,
            'sizes' => array_keys($metadata['sizes'] ?? []),
        ]);
    }

    public function get_stats(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        // Count by mime type
        $mime_counts = $wpdb->get_results(
            "SELECT post_mime_type, COUNT(*) as count
             FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
             GROUP BY post_mime_type"
        );

        $by_type = [];
        $total = 0;
        foreach ($mime_counts as $row) {
            $by_type[$row->post_mime_type] = (int) $row->count;
            $total += (int) $row->count;
        }

        // Get upload directory info
        $upload_dir = wp_upload_dir();

        // Calculate total size (approximate from DB)
        $total_size = 0;
        $attachments = $wpdb->get_col(
            "SELECT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attachment_metadata'"
        );

        foreach ($attachments as $meta) {
            $data = maybe_unserialize($meta);
            if (isset($data['filesize'])) {
                $total_size += $data['filesize'];
            }
        }

        return MCP_Endpoints::success([
            'total' => $total,
            'by_type' => $by_type,
            'upload_path' => $upload_dir['basedir'],
            'upload_url' => $upload_dir['baseurl'],
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'max_upload_size' => wp_max_upload_size(),
            'max_upload_size_mb' => round(wp_max_upload_size() / 1024 / 1024, 2),
        ]);
    }

    private function format_media(WP_Post $attachment, bool $detailed = false): array {
        $metadata = wp_get_attachment_metadata($attachment->ID);

        $data = [
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'url' => wp_get_attachment_url($attachment->ID),
            'mime_type' => $attachment->post_mime_type,
            'date' => $attachment->post_date,
        ];

        if (wp_attachment_is_image($attachment->ID)) {
            $data['alt'] = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
            $data['width'] = $metadata['width'] ?? null;
            $data['height'] = $metadata['height'] ?? null;
        }

        if ($detailed) {
            $data['caption'] = $attachment->post_excerpt;
            $data['description'] = $attachment->post_content;
            $data['filename'] = basename(get_attached_file($attachment->ID));
            $data['filesize'] = $metadata['filesize'] ?? null;

            if (wp_attachment_is_image($attachment->ID)) {
                $sizes = [];
                foreach (get_intermediate_image_sizes() as $size) {
                    $img = wp_get_attachment_image_src($attachment->ID, $size);
                    if ($img) {
                        $sizes[$size] = [
                            'url' => $img[0],
                            'width' => $img[1],
                            'height' => $img[2],
                        ];
                    }
                }
                $data['sizes'] = $sizes;
            }

            $data['attached_to'] = $attachment->post_parent ?: null;
        }

        return $data;
    }

    private function update_attachment_data(int $id, array $params): void {
        $post_data = ['ID' => $id];

        if (!empty($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }
        if (!empty($params['caption'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['caption']);
        }
        if (!empty($params['description'])) {
            $post_data['post_content'] = sanitize_textarea_field($params['description']);
        }

        if (count($post_data) > 1) {
            wp_update_post($post_data);
        }

        if (!empty($params['alt'])) {
            update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($params['alt']));
        }
    }
}
