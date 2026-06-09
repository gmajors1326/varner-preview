<?php
/**
 * Plugin Name: Varner OS Plugin v23
 * Description: Version 1.23.86 - React-powered inventory management for Varner Equipment.
 * Version: 1.23.86
 * Author: hwy559.com
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'varner-backend.php';
require_once plugin_dir_path(__FILE__) . 'rest-api.php';

// ─── Database Version & Auto-Upgrade ─────────────────────────────────────────

define('VARNER_OS_DB_VERSION', '1.23.7');

add_action('plugins_loaded', 'varner_os_db_check');
function varner_os_db_check(): void {
    if (get_option('varner_os_db_version') !== VARNER_OS_DB_VERSION) {
        varner_os_activate();
        update_option('varner_os_db_version', VARNER_OS_DB_VERSION);
    }
}

// ─── Activation: Create Audit Tables ─────────────────────────────────────────

register_activation_hook(__FILE__, 'varner_os_activate');
function varner_os_activate(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset  = $wpdb->get_charset_collate();
    $sessions = $wpdb->prefix . 'varner_user_sessions';
    $ledger   = $wpdb->prefix . 'varner_inventory_ledger';

    dbDelta("CREATE TABLE {$sessions} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        session_token varchar(191) NOT NULL DEFAULT '',
        login_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        logout_at datetime DEFAULT NULL,
        last_activity_at datetime DEFAULT NULL,
        ip varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        ended_reason varchar(32) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_login_idx (user_id, login_at),
        KEY session_token_idx (session_token)
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
    ) {$charset};");

    if (!wp_next_scheduled('varner_os_cleanup_sessions')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'varner_os_cleanup_sessions');
    }
}

register_deactivation_hook(__FILE__, function (): void {
    $timestamp = wp_next_scheduled('varner_os_cleanup_sessions');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'varner_os_cleanup_sessions');
    }
});

// ─── Session Logging Hooks ──────────────────────────────────────────────────

add_action('wp_login', 'varner_os_record_login', 10, 2);
function varner_os_record_login(string $user_login, WP_User $user): void {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';
    $token = wp_get_session_token();
    $ip    = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

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
function varner_os_record_logout(): void {
    if (!is_user_logged_in()) return;

    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';
    $user  = wp_get_current_user();

    $token = '';
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = wp_unslash($_SERVER['HTTP_AUTHORIZATION']);
        if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $token = sanitize_text_field($matches[1]);
        }
    } elseif (isset($_GET['mobile_token'])) {
        $token = sanitize_text_field(wp_unslash($_GET['mobile_token']));
    }

    if ($token && strlen($token) === 16 && ctype_xdigit($token)) {
        delete_transient('varner_mobile_token_' . $token);
    } else {
        $token = wp_get_session_token();
    }

    $where_sql = $token ? 'session_token = %s AND logout_at IS NULL' : 'user_id = %d AND logout_at IS NULL';
    $param     = $token ?: $user->ID;
    $open_id   = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE {$where_sql} ORDER BY login_at DESC LIMIT 1", $param));

    if ($open_id) {
        $wpdb->update(
            $table,
            array('logout_at' => current_time('mysql'), 'ended_reason' => 'logout'),
            array('id' => intval($open_id)),
            array('%s', '%s'),
            array('%d')
        );
    }
}

add_action('wp_login_failed', 'varner_os_record_login_failed');
function varner_os_record_login_failed(string $username): void {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';

    $user  = get_user_by('login', $username);
    $ip    = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    $wpdb->insert(
        $table,
        array(
            'user_id'       => $user ? $user->ID : 0,
            'session_token' => '',
            // login_at and logout_at are both set to now for failed attempts.
            // This flags a 0-duration session with ended_reason='failed_login'.
            // logout_at is NOT null so these don't appear as 'active' sessions.
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
function varner_os_cleanup_sessions(): void {
    global $wpdb;
    $table    = $wpdb->prefix . 'varner_user_sessions';
    $cutoff_ts = current_time('timestamp') - 2 * DAY_IN_SECONDS;
    $cutoff   = gmdate('Y-m-d H:i:s', $cutoff_ts);

    $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET logout_at = CASE WHEN logout_at IS NULL THEN %s ELSE logout_at END, ended_reason = CASE WHEN logout_at IS NULL THEN 'expiry' ELSE ended_reason END WHERE login_at < %s",
        current_time('mysql'),
        $cutoff
    ));
}

// ─── Mobile Auth Filters (moved from rest-api.php) ─────────────────────────────────
// Note: rest_authentication_errors at priority 9 was removed. It validated the
// transient before determine_current_user (priority 15) had set the user, making
// it redundant. varner_authenticate_mobile_token (below) is the sole authenticator.

add_filter('determine_current_user', 'varner_authenticate_mobile_token', 15);
function varner_authenticate_mobile_token(int $user_id): int {
    if ($user_id) {
        return $user_id;
    }

    $token = '';
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = sanitize_text_field($matches[1]);
    } elseif (isset($_GET['mobile_token'])) {
        $token = sanitize_text_field(wp_unslash($_GET['mobile_token']));
    }

    if (empty($token)) {
        return $user_id;
    }

    $stored_user_id = get_transient('varner_mobile_token_' . $token);
    if ($stored_user_id) {
        set_transient('varner_mobile_token_' . $token, $stored_user_id, 1800);
        return intval($stored_user_id);
    }

    return $user_id;
}

// ─── Session Activity Tracker (moved from rest-api.php) ────────────────────────────

add_action('init', 'varner_update_session_activity');
function varner_update_session_activity(): void {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }

    // Transient rate-limit: only update DB at most once per 60 seconds per user.
    $rate_key = 'varner_sess_activity_' . $user_id;
    if (get_transient($rate_key)) {
        return;
    }
    set_transient($rate_key, 1, 60);

    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';

    $token     = '';
    $is_mobile = false;

    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token     = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']));
        $is_mobile = true;
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token     = sanitize_text_field($matches[1]);
        $is_mobile = true;
    } elseif (isset($_GET['mobile_token'])) {
        $token     = sanitize_text_field(wp_unslash($_GET['mobile_token']));
        $is_mobile = true;
    }

    if (empty($token)) {
        $token = wp_get_session_token();
    }

    if (empty($token)) {
        return;
    }

    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT id, last_activity_at, logout_at FROM {$table} WHERE session_token = %s",
        $token
    ));

    $ip    = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    if ($session) {
        if ($session->logout_at !== null) {
            $wpdb->update(
                $table,
                array('logout_at' => null, 'ended_reason' => null, 'last_activity_at' => current_time('mysql')),
                array('id' => $session->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            $wpdb->update(
                $table,
                array('last_activity_at' => current_time('mysql')),
                array('id' => $session->id),
                array('%s'),
                array('%d')
            );
        }
    } else {
        if ($is_mobile) {
            $active_mobile = $wpdb->get_results($wpdb->prepare(
                "SELECT id, session_token FROM {$table} WHERE user_id = %d AND logout_at IS NULL AND session_token != %s",
                $user_id,
                $token
            ));

            foreach ($active_mobile as $old_sess) {
                if (strlen($old_sess->session_token) === 16 && ctype_xdigit($old_sess->session_token)) {
                    $wpdb->update(
                        $table,
                        array('logout_at' => current_time('mysql'), 'ended_reason' => 'superseded'),
                        array('id' => $old_sess->id),
                        array('%s', '%s'),
                        array('%d')
                    );
                    delete_transient('varner_mobile_token_' . $old_sess->session_token);
                }
            }
        }

        $wpdb->insert(
            $table,
            array(
                'user_id'          => $user_id,
                'session_token'    => $token,
                'login_at'         => current_time('mysql'),
                'last_activity_at' => current_time('mysql'),
                'ip'               => $ip,
                'user_agent'       => $agent,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
}

// ─── Admin Menu ──────────────────────────────────────────────────────────────

add_action('admin_menu', function (): void {
    add_menu_page(
        'Varner OS',
        'Varner OS',
        'manage_options',
        'varner-os',
        'varner_render_dashboard_page',
        'dashicons-hammer',
        2
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
    remove_menu_page('edit.php?post_type=equipment');
}, 999);

// ─── Login Redirect ──────────────────────────────────────────────────────────

add_filter('login_redirect', function (string $redirect_to, $request, $user): string {
    // Only redirect users who can access the Varner OS admin panel.
    if (isset($user->roles) && is_array($user->roles) && $user->has_cap('edit_posts')) {
        return admin_url('admin.php?page=varner-os');
    }
    return $redirect_to;
}, 10, 3);

// ─── Admin Page Renderers ────────────────────────────────────────────────────

function varner_render_dashboard_page(): void {
    echo '<div class="wrap" style="margin:0;padding:0;"><div id="varner-inventory-app" class="varner-inventory-app-mount" style="min-height:90vh;"></div></div>';
}

function varner_render_configuration_page(): void {
    echo '<div class="wrap" style="margin:0;padding:0;"><div id="varner-inventory-app" class="varner-inventory-app-mount" style="min-height:90vh;"></div></div>';

    $wpaip_link = admin_url('admin.php?page=pmxi-admin-import');
    echo '<div class="wrap" style="padding:16px 24px;margin-top:-8px;">';
    echo '  <div class="notice notice-info" style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">';
    echo '    <div><strong>Import Inventory:</strong> Use WP All Import Pro to run or schedule inventory imports.</div>';
    echo '    <a class="button button-primary" href="' . esc_url($wpaip_link) . '">Open WP All Import</a>';
    echo '  </div>';
    echo '</div>';

    global $wpdb;
    $table    = $wpdb->prefix . 'varner_user_sessions';
    $sessions = $wpdb->get_results("SELECT id, user_id, login_at, logout_at, ip, ended_reason FROM {$table} ORDER BY login_at DESC LIMIT 25");
    echo '<div class="wrap" style="padding:16px 24px;">';
    echo '<h2 style="margin:16px 0 8px;">Recent Sessions (last 25)</h2>';
    echo '<table class="widefat fixed striped" style="max-width:900px;">';
    echo '<thead><tr><th>ID</th><th>User</th><th>Login</th><th>Logout</th><th>IP</th><th>Status</th></tr></thead><tbody>';
    if ($sessions) {
        foreach ($sessions as $s) {
            $user   = $s->user_id ? get_user_by('id', $s->user_id) : null;
            $name   = $user ? esc_html($user->display_name) : '&mdash;';
            $status = $s->logout_at ? 'closed' : 'active';
            if (!empty($s->ended_reason)) {
                $status .= ' (' . esc_html($s->ended_reason) . ')';
            }
            echo '<tr>';
            echo '<td>' . intval($s->id) . '</td>';
            echo '<td>' . $name . '</td>';
            echo '<td>' . esc_html($s->login_at) . '</td>';
            echo '<td>' . esc_html($s->logout_at ?: '&mdash;') . '</td>';
            echo '<td>' . esc_html($s->ip ?: '&mdash;') . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">No sessions found.</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p style="margin-top:8px;">For full history and filters, use <code>/wp-json/varner/v1/sessions?active_only=1</code>.</p>';
    echo '</div>';
}

// ─── Asset Loader ────────────────────────────────────────────────────────────

function varner_enqueue_react_assets(): void {
    $html_file = plugin_dir_path(__FILE__) . 'dist/index.html';
    if (!file_exists($html_file)) {
        error_log('Varner OS: dist/index.html not found.');
        return;
    }

    // Cache the parsed asset filenames keyed by file modification time.
    // This avoids reading and regex-parsing index.html on every admin page load.
    $cache_key = 'varner_assets_' . filemtime($html_file);
    $assets    = get_transient($cache_key);
    if (!$assets) {
        $html     = file_get_contents($html_file);
        $js_file  = '';
        $css_file = '';
        if (preg_match('/src="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.js)"/', $html, $matches)) {
            $js_file = $matches[1];
        }
        if (preg_match('/href="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.css)"/', $html, $matches)) {
            $css_file = $matches[1];
        }
        $assets = array('js_file' => $js_file, 'css_file' => $css_file);
        set_transient($cache_key, $assets, DAY_IN_SECONDS);
    }

    $js_file  = $assets['js_file'];
    $css_file = $assets['css_file'];

    $dist_url  = plugin_dir_url(__FILE__) . 'dist/assets/';
    $dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';

    if ($js_file && file_exists($dist_path . $js_file)) {
        $ver = filemtime($dist_path . $js_file);
        wp_enqueue_script('varner-react-app', $dist_url . $js_file, array(), $ver, true);
        wp_localize_script('varner-react-app', 'varnerData', array(
            'post_id'  => get_the_ID(),
            'nonce'    => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url()),
            'site_url' => esc_url_raw(home_url('/')),
            'logo_url' => function_exists('varner_get_brand_logo_url') ? varner_get_brand_logo_url('white') : '',
        ));
    } else {
        error_log('Varner OS: JS asset not found. js_file: ' . ($js_file ?: 'none'));
    }

    if ($css_file && file_exists($dist_path . $css_file)) {
        $ver = filemtime($dist_path . $css_file);
        wp_enqueue_style('varner-tailwind', $dist_url . $css_file, array(), $ver);
    } else {
        error_log('Varner OS: CSS asset not found. css_file: ' . ($css_file ?: 'none'));
    }
}

// ─── Meta Box ────────────────────────────────────────────────────────────────

function varner_render_meta_box(): void {
    echo '<div id="varner-inventory-app" class="varner-responsive-container varner-inventory-app-mount" style="min-height:600px;background:#f8fafc;border-radius:4px;overflow:hidden;"></div>';
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'varner_os_editor',
        'Varner OS - Inventory Management',
        'varner_render_meta_box',
        'equipment',
        'normal',
        'high'
    );
});

// ─── Shortcode ───────────────────────────────────────────────────────────────

add_shortcode('varner_showroom', function (): string {
    return '<div id="varner-inventory-app" class="varner-inventory-app-mount varner-public-showroom"></div>';
});

// ─── Front-End Asset Enqueue ─────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'varner_showroom')) {
        varner_enqueue_react_assets();
    }
});

// ─── Admin Asset & CSS Enqueue ───────────────────────────────────────────────

add_action('admin_enqueue_scripts', function (string $hook): void {
    global $post;

    $is_equipment    = isset($post->post_type) && $post->post_type === 'equipment';
    $is_varner_page  = isset($_GET['page']) && in_array($_GET['page'], array('varner-os', 'varner-os-config'), true);
    $is_block_editor = get_current_screen() && get_current_screen()->is_block_editor();

    if (!$is_equipment && !$is_varner_page && !$is_block_editor) return;

    wp_add_inline_style('wp-admin', '
        #wpcontent { padding-left: 0 !important; }
        .wrap { max-width: 100% !important; }
        #wpfooter { display: none !important; }
        #varner-inventory-app { background: #f8fafc; }
        @media (min-width: 783px) {
            .varner-responsive-container { margin: -12px -12px -12px -12px; }
        }
        #varner_os_editor .inside { padding: 0 !important; margin: 0 !important; }
        #varner_os_editor { border: none !important; background: transparent !important; box-shadow: none !important; margin-top: 0 !important; }
        #varner_os_editor .postbox-header { display: none !important; }
        .wp-heading-inline, .page-title-action, #titlediv, .wp-header-end { display: none !important; }
        #poststuff { padding-top: 0 !important; }
    ');

    varner_enqueue_react_assets();
}, 20);

// ─── Vite ESM Support ────────────────────────────────────────────────────────

add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
    if ('varner-react-app' !== $handle) return $tag;
    return '<script type="module" src="' . esc_url($src) . '" id="varner-react-app-js"></script>';
}, 10, 3);

// ─── Facebook Catalog CSV Generator ──────────────────────────────────────────

function varner_os_generate_facebook_catalog(): void {
    $posts = get_posts(array(
        'post_type'      => 'equipment',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            'relation' => 'OR',
            array('key' => 'show_on_website', 'value' => '1', 'compare' => '='),
            array('key' => 'show_on_website', 'compare' => 'NOT EXISTS'),
        ),
    ));

    $out = fopen('php://temp', 'w+');

    $headers = array(
        'id', 'title', 'description', 'link', 'image_link', 'additional_image_link',
        'availability', 'condition', 'price', 'brand',
        'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4',
    );
    fputcsv($out, $headers);

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $fields  = function_exists('get_fields') ? get_fields($post_id) : array();

        $stock_status = isset($fields['stock_status']) ? $fields['stock_status'] : get_post_meta($post_id, 'stock_status', true);
        if ($stock_status === 'Draft') continue;

        $title = $post->post_title;

        $desc_raw = isset($fields['description']) ? $fields['description'] : get_post_meta($post_id, 'description', true);
        $desc = wp_strip_all_tags($desc_raw);
        $desc = str_replace(array("\r", "\n", "\t"), ' ', $desc);
        $desc = preg_replace('/\s+/', ' ', $desc);
        $desc = trim($desc);
        if (empty($desc)) $desc = $title;
        if (strlen($desc) > 4900) $desc = mb_strimwidth($desc, 0, 4900, '...');

        $url = get_permalink($post_id);

        $image_0_url = '';
        $additional_images = array();
        $gallery = isset($fields['gallery']) ? $fields['gallery'] : get_post_meta($post_id, 'gallery', true);
        if (!empty($gallery) && is_array($gallery)) {
            $img_count = 0;
            foreach ($gallery as $img) {
                $img_url = '';
                if (is_array($img)) {
                    $img_url = $img['url'] ?? '';
                } elseif (is_numeric($img)) {
                    $img_url = wp_get_attachment_url($img);
                }
                if ($img_url) {
                    if ($img_count === 0) $image_0_url = $img_url;
                    else $additional_images[] = $img_url;
                    $img_count++;
                }
            }
        }
        if (empty($image_0_url)) {
            $feat_id = get_post_thumbnail_id($post_id);
            if ($feat_id) $image_0_url = wp_get_attachment_url($feat_id);
        }

        $make = isset($fields['make']) ? $fields['make'] : get_post_meta($post_id, 'make', true);
        if (empty($make)) $make = 'Varner Equipment';

        $model = isset($fields['model']) ? $fields['model'] : get_post_meta($post_id, 'model', true);
        if (empty($model)) $model = 'Equipment';

        $year = isset($fields['year']) ? $fields['year'] : get_post_meta($post_id, 'year', true);
        if (empty($year)) $year = get_the_date('Y', $post_id) ?: '2026';

        $cond_raw  = isset($fields['condition']) ? $fields['condition'] : get_post_meta($post_id, 'condition', true);
        $condition = strtolower($cond_raw) === 'used' ? 'used' : 'new';
        $availability = ($stock_status === 'In Stock') ? 'in stock' : 'out of stock';

        $price_val     = isset($fields['price']) ? $fields['price'] : get_post_meta($post_id, 'price', true);
        $call_for_price = isset($fields['call_for_price']) ? (bool) $fields['call_for_price'] : (bool) get_post_meta($post_id, 'call_for_price', true);
        $price = ($call_for_price || empty($price_val) || floatval($price_val) <= 0) ? '0 USD' : floatval($price_val) . ' USD';

        $category     = isset($fields['category']) ? $fields['category'] : get_post_meta($post_id, 'category', true);
        $stock_number = isset($fields['stock_number']) ? $fields['stock_number'] : get_post_meta($post_id, 'stock_number', true);

        fputcsv($out, array(
            $post_id, $title, $desc, $url, $image_0_url,
            implode(',', $additional_images),
            $availability, $condition, $price, $make,
            $category, $stock_number, $year, $make, $model,
        ));
    }

    rewind($out);
    $csv_data = stream_get_contents($out);
    fclose($out);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="facebook-catalog.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    echo $csv_data;
    exit;
}

// Facebook Catalog — served via template_redirect to avoid 'headers already sent'.
add_action('template_redirect', 'varner_os_facebook_catalog_endpoint');
function varner_os_facebook_catalog_endpoint(): void {
    $uri      = $_SERVER['REQUEST_URI'] ?? '';
    $uri_path = trim(parse_url($uri, PHP_URL_PATH), '/');
    $site_path = parse_url(home_url(), PHP_URL_PATH);
    $site_path = $site_path ? trim($site_path, '/') : '';
    $path = ($site_path !== '' && strpos($uri_path, $site_path) === 0)
        ? trim(substr($uri_path, strlen($site_path)), '/')
        : $uri_path;
    if ($path === 'facebook-catalog.csv') {
        varner_os_generate_facebook_catalog();
    }
}

// ─── PWA / Mobile Companion Router ────────────────────────────────────────────────

add_action('init', 'varner_os_mobile_pwa_router');
function varner_os_mobile_pwa_router(): void {
    $uri      = $_SERVER['REQUEST_URI'] ?? '';
    $uri_path = trim(parse_url($uri, PHP_URL_PATH), '/');

    $site_path = parse_url(home_url(), PHP_URL_PATH);
    $site_path = $site_path ? trim($site_path, '/') : '';

    if ($site_path !== '' && strpos($uri_path, $site_path) === 0) {
        $path = trim(substr($uri_path, strlen($site_path)), '/');
    } else {
        $path = $uri_path;
    }

    // Facebook catalog is now handled via template_redirect (above).
    // Skip it here so we don't call header() before WP output buffering starts.

    // Manifest — resolve icon URL only when needed
    if ($path === 'mobile-app/manifest.json' || $path === 'manifest.json') {
        $icon_url = '';
        $icon_cache_key = 'varner_pwa_icon_url';
        $icon_url = get_transient($icon_cache_key);
        if (!$icon_url) {
            if (file_exists(get_stylesheet_directory() . '/assets/VE_Tractor_Icon.png')) {
                $icon_url = get_stylesheet_directory_uri() . '/assets/VE_Tractor_Icon.png';
            } elseif (file_exists(get_template_directory() . '/assets/VE_Tractor_Icon.png')) {
                $icon_url = get_template_directory_uri() . '/assets/VE_Tractor_Icon.png';
            } else {
                $upload_dir = wp_upload_dir();
                if (file_exists($upload_dir['basedir'] . '/2026/04/VE_Tractor_Icon.png')) {
                    $icon_url = $upload_dir['baseurl'] . '/2026/04/VE_Tractor_Icon.png';
                } else {
                    $icon_url = varner_get_brand_logo_url('red') ?: (plugin_dir_url(__FILE__) . 'dist/assets/logo.png');
                }
            }
            set_transient($icon_cache_key, $icon_url, WEEK_IN_SECONDS);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(

            'name'             => 'Varner OS Mobile Companion',
            'short_name'       => 'Varner Mobile',
            'description'      => 'Mobile inventory management for Varner Equipment yard crew.',
            'start_url'        => home_url('/mobile-app/'),
            'display'          => 'standalone',
            'orientation'      => 'any',
            'background_color' => '#0f172a',
            'theme_color'      => '#0f172a',
            'status_bar'       => 'black-translucent',
            'icons'            => array(
                array(
                    'src'     => $icon_url,
                    'sizes'   => '192x192 512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ),
            ),
        ));
        exit;
    }

    // Service worker
    if ($path === 'mobile-app/sw.js' || $path === 'sw.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        $home_url = esc_url_raw(home_url('/mobile-app/'));
        ?>
const CACHE_NAME = 'varner-mobile-cache-v1';
const ASSETS = ['<?php echo $home_url; ?>'];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.map((k) => k !== CACHE_NAME ? caches.delete(k) : null)))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);
    if (url.pathname.includes('/wp-json/') || url.pathname.includes('/wp-admin/')) {
        e.respondWith(fetch(e.request));
        return;
    }
    e.respondWith(
        caches.match(e.request).then((cached) => {
            return cached || fetch(e.request).then((response) => {
                const mime = response.headers.get('content-type') || '';
                if (response.status === 200 && (mime.includes('css') || mime.includes('javascript') || mime.includes('image') || mime.includes('font'))) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(e.request, clone));
                }
                return response;
            });
        }).catch(() => {
            if (e.request.headers.get('accept').includes('text/html')) {
                return caches.match('<?php echo $home_url; ?>');
            }
        })
    );
});
        <?php
        exit;
    }

    // Mobile app page
    if ($path === 'mobile-app') {
        status_header(200);
        ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Varner OS Mobile Companion</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="<?php echo esc_url(home_url('/mobile-app/manifest.json')); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url(get_transient('varner_pwa_icon_url') ?: (plugin_dir_url(__FILE__) . 'dist/assets/logo.png')); ?>">

    <style>
        html, body { margin:0; padding:0; width:100%; height:100%; background-color:#f8fafc; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        #varner-inventory-app { width:100%; height:100%; overflow:hidden; }
    </style>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo esc_url_raw(home_url('/sw.js')); ?>')
                .then(reg => console.log('Service Worker registered!', reg.scope))
                .catch(err => console.log('Service Worker failed:', err));
        });
    }
    </script>
    <?php
    // Use the same transient cache as varner_enqueue_react_assets()
    $html_file = plugin_dir_path(__FILE__) . 'dist/index.html';
    $js_file = $css_file = '';
    if (file_exists($html_file)) {
        $cache_key = 'varner_assets_' . filemtime($html_file);
        $assets    = get_transient($cache_key);
        if (!$assets) {
            $html     = file_get_contents($html_file);
            $js_file  = '';
            $css_file = '';
            if (preg_match('/src="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.js)"/', $html, $m)) $js_file = $m[1];
            if (preg_match('/href="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.css)"/', $html, $m)) $css_file = $m[1];
            $assets = array('js_file' => $js_file, 'css_file' => $css_file);
            set_transient($cache_key, $assets, DAY_IN_SECONDS);
        }
        $js_file  = $assets['js_file'];
        $css_file = $assets['css_file'];
    }

    $dist_url  = plugin_dir_url(__FILE__) . 'dist/assets/';
    $dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';

    if ($js_file && $css_file && file_exists($dist_path . $js_file) && file_exists($dist_path . $css_file)) {
        $ver     = filemtime($dist_path . $js_file);
        $css_ver = filemtime($dist_path . $css_file);
        ?><link rel="stylesheet" href="<?php echo $dist_url . $css_file . '?ver=' . $css_ver; ?>">
<script>
window.varnerData = {
    post_id: 0,
    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
    rest_url: '<?php echo esc_url_raw(rest_url()); ?>',
    site_url: '<?php echo esc_url_raw(home_url('/')); ?>',
    is_mobile_app: true,
    logo_url: '<?php echo function_exists('varner_get_brand_logo_url') ? esc_url(varner_get_brand_logo_url('white')) : ''; ?>'
};
</script>
<script type="module" src="<?php echo $dist_url . $js_file . '?ver=' . $ver; ?>"></script>
<?php
    } else {
        echo '<div style="padding:20px;text-align:center;color:red;">Error: React build assets not found. Please build the application first.</div>';
    }
    ?>
</head>
<body>
    <div id="varner-inventory-app" class="varner-inventory-app-mount"></div>
</body>
</html>
        <?php
        exit;
    }
}
