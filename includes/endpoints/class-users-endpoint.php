<?php
/**
 * Users endpoints - user management
 */

defined('ABSPATH') || exit;

class MCP_Users_Endpoint {

    public function register_routes(): void {
        // List users
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users', [
            'methods' => 'GET',
            'callback' => [$this, 'list_users'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'role' => ['type' => 'string'],
                'per_page' => ['type' => 'integer', 'default' => 20],
                'page' => ['type' => 'integer', 'default' => 1],
                'search' => ['type' => 'string'],
                'orderby' => ['type' => 'string', 'default' => 'registered'],
                'order' => ['type' => 'string', 'default' => 'DESC'],
            ],
        ]);

        // Get single user
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Create user
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users', [
            'methods' => 'POST',
            'callback' => [$this, 'create_user'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'username' => ['required' => true, 'type' => 'string'],
                'email' => ['required' => true, 'type' => 'string'],
                'password' => ['type' => 'string'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'role' => ['type' => 'string', 'default' => 'subscriber'],
                'send_notification' => ['type' => 'boolean', 'default' => true],
            ],
        ]);

        // Update user
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_user'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete user
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_user'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'reassign' => ['type' => 'integer', 'description' => 'Reassign posts to this user ID'],
            ],
        ]);

        // List roles
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/roles', [
            'methods' => 'GET',
            'callback' => [$this, 'list_roles'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Update user role
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)/role', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_role'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'role' => ['required' => true, 'type' => 'string'],
            ],
        ]);

        // Get user meta
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)/meta', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_meta'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Update user meta
        register_rest_route(MCP_Endpoints::NAMESPACE, '/users/(?P<id>\d+)/meta', [
            'methods' => 'POST',
            'callback' => [$this, 'update_user_meta'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'meta' => ['required' => true, 'type' => 'object'],
            ],
        ]);
    }

    public function list_users(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'number' => absint($request->get_param('per_page')),
            'paged' => absint($request->get_param('page')),
            'orderby' => sanitize_key($request->get_param('orderby')),
            'order' => strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC',
        ];

        if ($request->has_param('role') && $request->get_param('role')) {
            $args['role'] = sanitize_key($request->get_param('role'));
        }

        if ($request->has_param('search') && $request->get_param('search')) {
            $args['search'] = '*' . sanitize_text_field($request->get_param('search')) . '*';
        }

        $query = new WP_User_Query($args);
        $users = [];

        foreach ($query->get_results() as $user) {
            $users[] = [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user->roles,
                'registered' => $user->user_registered,
            ];
        }

        return MCP_Endpoints::success([
            'users' => $users,
            'total' => $query->get_total(),
            'page' => absint($request->get_param('page')),
        ]);
    }

    public function get_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = absint($request->get_param('id'));
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        return MCP_Endpoints::success([
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'nickname' => $user->nickname,
            'description' => $user->description,
            'url' => $user->user_url,
            'roles' => $user->roles,
            'capabilities' => array_keys(array_filter($user->allcaps)),
            'registered' => $user->user_registered,
            'posts_count' => count_user_posts($user->ID),
        ]);
    }

    public function create_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $username = sanitize_user($request->get_param('username'));
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password') ?: wp_generate_password();

        if (username_exists($username)) {
            return MCP_Endpoints::error("Username already exists", 'username_exists', 400);
        }

        if (email_exists($email)) {
            return MCP_Endpoints::error("Email already exists", 'email_exists', 400);
        }

        $user_data = [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => sanitize_key($request->get_param('role')),
        ];

        if ($request->has_param('first_name')) {
            $user_data['first_name'] = sanitize_text_field($request->get_param('first_name'));
        }
        if ($request->has_param('last_name')) {
            $user_data['last_name'] = sanitize_text_field($request->get_param('last_name'));
        }

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        if ($request->get_param('send_notification')) {
            wp_new_user_notification($user_id, null, 'user');
        }

        return MCP_Endpoints::success([
            'id' => $user_id,
            'username' => $username,
            'created' => true,
        ]);
    }

    public function update_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = absint($request->get_param('id'));

        if (!get_user_by('ID', $user_id)) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        $user_data = ['ID' => $user_id];

        if ($request->has_param('email')) {
            $email = sanitize_email($request->get_param('email'));
            $existing = email_exists($email);
            if ($existing && $existing !== $user_id) {
                return MCP_Endpoints::error("Email already in use", 'email_exists', 400);
            }
            $user_data['user_email'] = $email;
        }

        if ($request->has_param('password')) {
            $user_data['user_pass'] = $request->get_param('password');
        }
        if ($request->has_param('first_name')) {
            $user_data['first_name'] = sanitize_text_field($request->get_param('first_name'));
        }
        if ($request->has_param('last_name')) {
            $user_data['last_name'] = sanitize_text_field($request->get_param('last_name'));
        }
        if ($request->has_param('display_name')) {
            $user_data['display_name'] = sanitize_text_field($request->get_param('display_name'));
        }
        if ($request->has_param('description')) {
            $user_data['description'] = sanitize_textarea_field($request->get_param('description'));
        }
        if ($request->has_param('url')) {
            $user_data['user_url'] = esc_url_raw($request->get_param('url'));
        }

        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'id' => $user_id,
            'updated' => true,
        ]);
    }

    public function delete_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/user.php';

        $user_id = absint($request->get_param('id'));
        $reassign = $request->get_param('reassign');

        if (!get_user_by('ID', $user_id)) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        // Don't allow deleting current user
        if ($user_id === get_current_user_id()) {
            return MCP_Endpoints::error("Cannot delete current user", 'cannot_delete_self', 400);
        }

        $result = wp_delete_user($user_id, $reassign);

        if (!$result) {
            return MCP_Endpoints::error("Failed to delete user", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $user_id,
            'deleted' => true,
            'posts_reassigned_to' => $reassign,
        ]);
    }

    public function list_roles(WP_REST_Request $request): WP_REST_Response {
        global $wp_roles;

        $roles = [];
        foreach ($wp_roles->roles as $key => $role) {
            $count = count(get_users(['role' => $key, 'fields' => 'ID']));
            $roles[] = [
                'slug' => $key,
                'name' => $role['name'],
                'capabilities' => array_keys(array_filter($role['capabilities'])),
                'count' => $count,
            ];
        }

        return MCP_Endpoints::success([
            'roles' => $roles,
            'count' => count($roles),
        ]);
    }

    public function update_role(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = absint($request->get_param('id'));
        $role = sanitize_key($request->get_param('role'));

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        global $wp_roles;
        if (!isset($wp_roles->roles[$role])) {
            return MCP_Endpoints::error("Invalid role", 'invalid_role', 400);
        }

        $user->set_role($role);

        return MCP_Endpoints::success([
            'id' => $user_id,
            'role' => $role,
            'updated' => true,
        ]);
    }

    public function get_user_meta(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = absint($request->get_param('id'));

        if (!get_user_by('ID', $user_id)) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        $meta = get_user_meta($user_id);

        // Flatten single values
        $result = [];
        foreach ($meta as $key => $values) {
            // Skip private meta
            if (strpos($key, '_') === 0) {
                continue;
            }
            $result[$key] = count($values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
        }

        return MCP_Endpoints::success([
            'user_id' => $user_id,
            'meta' => $result,
        ]);
    }

    public function update_user_meta(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = absint($request->get_param('id'));
        $meta = $request->get_param('meta');

        if (!get_user_by('ID', $user_id)) {
            return MCP_Endpoints::error("User not found", 'not_found', 404);
        }

        $updated = [];
        foreach ($meta as $key => $value) {
            $key = sanitize_key($key);
            update_user_meta($user_id, $key, $value);
            $updated[] = $key;
        }

        return MCP_Endpoints::success([
            'user_id' => $user_id,
            'updated_keys' => $updated,
        ]);
    }
}
