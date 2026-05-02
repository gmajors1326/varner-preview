<?php
/**
 * Plugin Name: Varner OS Plugin v23
 * Description: Version 1.23.0 - React-powered inventory management for Varner Equipment.
 * Version: 1.23.0
 * Author: hwy559.com
 */

if (!defined('ABSPATH'))
    exit;

// Include backend CPT and ACF registrations
require_once plugin_dir_path(__FILE__) . 'varner-backend.php';

// ─── Activation: create audit tables ────────────────────────────────────────

register_activation_hook(__FILE__, 'varner_os_activate');
function varner_os_activate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $sessions = $wpdb->prefix . 'varner_user_sessions';
    $ledger   = $wpdb->prefix . 'varner_inventory_ledger';

    $sql = "CREATE TABLE {$sessions} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        session_token varchar(191) NOT NULL DEFAULT '',
        login_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        logout_at datetime DEFAULT NULL,
        ip varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        ended_reason varchar(32) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_login_idx (user_id, login_at)
    ) {$charset};

    CREATE TABLE {$ledger} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        action varchar(32) NOT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        display_name varchar(191) DEFAULT NULL,
        initials varchar(24) DEFAULT NULL,
        summary varchar(255) DEFAULT NULL,
        details longtext,
        request_id varchar(64) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_created_idx (post_id, created_at),
        KEY user_created_idx (user_id, created_at),
        KEY request_idx (request_id)
    ) {$charset};";

    dbDelta($sql);

    if (!wp_next_scheduled('varner_os_cleanup_sessions')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'varner_os_cleanup_sessions');
    }
}

register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('varner_os_cleanup_sessions');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'varner_os_cleanup_sessions');
    }
});

// ─── Session logging hooks ──────────────────────────────────────────────────

add_action('wp_login', 'varner_os_record_login', 10, 2);
function varner_os_record_login($user_login, $user) {
    global $wpdb;
    $table   = $wpdb->prefix . 'varner_user_sessions';
    $token   = wp_get_session_token();
    $ip      = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent   = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    $wpdb->insert(
        $table,
        array(
            'user_id'       => $user->ID,
            'session_token' => $token ?: '',
            'login_at'      => current_time('mysql'),
            'ip'            => $ip,
            'user_agent'    => $agent,
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
}

add_action('wp_logout', 'varner_os_record_logout');
function varner_os_record_logout() {
    if (!is_user_logged_in()) return;

    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';
    $user  = wp_get_current_user();
    $token = wp_get_session_token();

    // Find the most recent open session for this token/user
    $where_sql = $token ? 'session_token = %s AND logout_at IS NULL' : 'user_id = %d AND logout_at IS NULL';
    $param     = $token ? $token : $user->ID;
    $open_id   = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE {$where_sql} ORDER BY login_at DESC LIMIT 1", $param));

    if ($open_id) {
        $wpdb->update(
            $table,
            array(
                'logout_at'    => current_time('mysql'),
                'ended_reason' => 'logout',
            ),
            array('id' => intval($open_id)),
            array('%s', '%s'),
            array('%d')
        );
    }
}

add_action('wp_login_failed', 'varner_os_record_login_failed');
function varner_os_record_login_failed($username) {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';

    $user = get_user_by('login', $username);
    $ip    = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    $wpdb->insert(
        $table,
        array(
            'user_id'       => $user ? intval($user->ID) : 0,
            'session_token' => '',
            'login_at'      => current_time('mysql'),
            'logout_at'     => current_time('mysql'),
            'ip'            => $ip,
            'user_agent'    => $agent,
            'ended_reason'  => 'failed_login',
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );
}

add_action('varner_os_cleanup_sessions', 'varner_os_cleanup_sessions');
function varner_os_cleanup_sessions() {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';
    $cutoff_ts = current_time('timestamp') - 2 * DAY_IN_SECONDS;
    $cutoff = gmdate('Y-m-d H:i:s', $cutoff_ts);

    $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET logout_at = CASE WHEN logout_at IS NULL THEN %s ELSE logout_at END, ended_reason = CASE WHEN logout_at IS NULL THEN 'expiry' ELSE ended_reason END WHERE login_at < %s",
        current_time('mysql'),
        $cutoff
    ));
}

/**
 * Register Top-Level Admin Menu
 */
add_action('admin_menu', function() {
    add_menu_page(
        'Varner OS',
        'Varner OS',
        'manage_options',
        'varner-os',
        'varner_render_dashboard_page',
        'dashicons-hammer',
        2 // High priority position
    );
    add_submenu_page(
        'varner-os',
        'Configuration',
        'Configuration',
        'manage_options',
        'varner-os-config',
        'varner_render_configuration_page',
        1
    );
    
    // Hide the Equipment CPT from the menu entirely
    remove_menu_page('edit.php?post_type=equipment');
    
    // Remove the default dashboard for a cleaner experience if desired
    // remove_menu_page('index.php'); 
}, 999);

/**
 * Redirect Login to Varner OS
 */
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        return admin_url('admin.php?page=varner-os');
    }
    return $redirect_to;
}, 10, 3);

/**
 * Render the Custom Dashboard Page
 */
function varner_render_dashboard_page() {
    echo '<div class="wrap" style="margin: 0; padding: 0;">';
    echo '  <div id="varner-inventory-app" class="varner-inventory-app-mount" style="min-height: 90vh;"></div>';
    echo '</div>';
    
    echo '<style>
        #wpcontent { padding-left: 0 !important; }
        .wrap { max-width: 100% !important; }
        #wpfooter { display: none !important; }
        #varner-inventory-app { background: #f8fafc; }
    </style>';
}

/**
 * Render Configuration Page (shares React app mount)
 */
function varner_render_configuration_page() {
    echo '<div class="wrap" style="margin: 0; padding: 0;">';
    echo '  <div id="varner-inventory-app" class="varner-inventory-app-mount" style="min-height: 90vh;"></div>';
    echo '</div>';
    echo '<style>
        #wpcontent { padding-left: 0 !important; }
        .wrap { max-width: 100% !important; }
        #wpfooter { display: none !important; }
        #varner-inventory-app { background: #f8fafc; }
    </style>';

    // Quick link to WP All Import Pro for inventory imports
    $wpaip_link = admin_url('admin.php?page=pmxi-admin-import');
    echo '<div class="wrap" style="padding: 16px 24px; margin-top: -8px;">';
    echo '  <div class="notice notice-info" style="padding: 12px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px;">';
    echo '    <div><strong>Import Inventory:</strong> Use WP All Import Pro to run or schedule inventory imports.</div>';
    echo '    <a class="button button-primary" href="' . esc_url($wpaip_link) . '">Open WP All Import</a>';
    echo '  </div>';
    echo '</div>';

    // Lightweight server-rendered sessions panel as a fallback
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';
    $sessions = $wpdb->get_results("SELECT id, user_id, login_at, logout_at, ip, ended_reason FROM {$table} ORDER BY login_at DESC LIMIT 25");
    echo '<div class="wrap" style="padding: 16px 24px;">';
    echo '<h2 style="margin:16px 0 8px;">Recent Sessions (last 25)</h2>';
    echo '<table class="widefat fixed striped" style="max-width: 900px;">';
    echo '<thead><tr><th>ID</th><th>User</th><th>Login</th><th>Logout</th><th>IP</th><th>Status</th></tr></thead><tbody>';
    if ($sessions) {
        foreach ($sessions as $s) {
            $user = $s->user_id ? get_user_by('id', $s->user_id) : null;
            $name = $user ? esc_html($user->display_name) : '—';
            $status = $s->logout_at ? 'closed' : 'active';
            if (!empty($s->ended_reason)) {
                $status .= ' (' . esc_html($s->ended_reason) . ')';
            }
            echo '<tr>';
            echo '<td>' . intval($s->id) . '</td>';
            echo '<td>' . $name . '</td>';
            echo '<td>' . esc_html($s->login_at) . '</td>';
            echo '<td>' . esc_html($s->logout_at ?: '—') . '</td>';
            echo '<td>' . esc_html($s->ip ?: '—') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">No sessions found.</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p style="margin-top:8px;">For full history and filters, use the REST endpoint: <code>/wp-json/varner/v1/sessions?active_only=1</code>.</p>';
    echo '</div>';
}

/**
 * Shared Asset Loader
 */
function varner_enqueue_react_assets()
{
    $dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';
    $dist_url = plugin_dir_url(__FILE__) . 'dist/assets/';

    $js_files = glob($dist_path . '*.js');
    $css_files = glob($dist_path . '*.css');

    $pick_latest = function($files) {
        if (empty($files)) return null;
        usort($files, function($a, $b) { return filemtime($b) <=> filemtime($a); });
        // Prefer main.* when present
        foreach ($files as $f) {
            if (strpos(basename($f), 'main.') === 0) return $f;
        }
        return $files[0];
    };

    $js = $pick_latest($js_files);
    $css = $pick_latest($css_files);

    if ($js) {
        $ver = filemtime($js);
        wp_enqueue_script('varner-react-app', $dist_url . basename($js), array(), $ver, true);
        wp_localize_script('varner-react-app', 'varnerData', array(
            'post_id' => get_the_ID(),
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url())
        ));
    } else {
        error_log("Varner OS: JS assets not found in " . $dist_path);
    }
    
    if ($css) {
        $ver = filemtime($css);
        wp_enqueue_style('varner-tailwind', $dist_url . basename($css), array(), $ver);
    } else {
        error_log("Varner OS: CSS assets not found in " . $dist_path);
    }
}

/**
 * Render the Meta Box for the inventory app
 */
function varner_render_meta_box()
{
    // Anchor point for the React application - Empty to avoid "extra blocks" feel
    echo '<div id="varner-inventory-app" class="varner-responsive-container varner-inventory-app-mount" style="min-height: 600px; background: #f8fafc; border-radius: 4px; overflow: hidden;"></div>';
    
    echo '<style>
        @media (min-width: 783px) {
            .varner-responsive-container { margin: -12px -12px -12px -12px; }
        }
        #varner_os_editor .inside { padding: 0 !important; margin: 0 !important; }
        #varner_os_editor { border: none !important; background: transparent !important; box-shadow: none !important; margin-top: 0 !important; }
        #varner_os_editor .postbox-header { display: none !important; }
        
        /* Hide WordPress Admin Title and Header elements for a clean interface */
        .wp-heading-inline, .page-title-action, #titlediv, .wp-header-end { display: none !important; }
        #poststuff { padding-top: 0 !important; }
    </style>';
}

/**
 * Add the Meta Box to the Equipment CPT
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'varner_os_editor',
        'Varner OS - Inventory Management',
        'varner_render_meta_box',
        'equipment',
        'normal',
        'high'
    );
});

/**
 * Register Public Showroom Shortcode
 */
add_shortcode('varner_showroom', function() {
    // Shared container for the React app
    return '<div id="varner-inventory-app" class="varner-inventory-app-mount varner-public-showroom"></div>';
});

/**
 * Enqueue React Assets for Front-End (Showroom)
 */
add_action('wp_enqueue_scripts', function() {
    // Only load if the showroom shortcode is present on the page
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'varner_showroom')) {
        varner_enqueue_react_assets();
    }
});

/**
 * Support for Vite ESM Modules in WordPress
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ('varner-react-app' !== $handle) {
        return $tag;
    }
    // Add type="module" to the script tag
    return '<script type="module" src="' . esc_url($src) . '" id="varner-react-app-js"></script>';
}, 10, 3);

/**
 * Enqueue React Assets and Localize Data
 */
add_action('admin_enqueue_scripts', function ($hook) {
    global $post;
    
    // Load on Equipment post type OR our custom Dashboard page
    $is_equipment = isset($post->post_type) && $post->post_type === 'equipment';
    $is_varner_page = (isset($_GET['page']) && in_array($_GET['page'], array('varner-os', 'varner-os-config'), true));
    $is_block_editor = (function_exists('get_current_screen') && get_current_screen() && get_current_screen()->is_block_editor());

    if (!$is_equipment && !$is_varner_page && !$is_block_editor) return;

    varner_enqueue_react_assets();
}, 20);
