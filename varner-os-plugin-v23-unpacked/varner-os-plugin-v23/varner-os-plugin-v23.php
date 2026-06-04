<?php
/**
 * Plugin Name: Varner OS Plugin v23
 * Description: Version 1.23.10 - React-powered inventory management for Varner Equipment.
 * Version: 1.23.10
 * Author: hwy559.com
 */

if (!defined('ABSPATH'))
    exit;

// Include backend CPT and ACF registrations
require_once plugin_dir_path(__FILE__) . 'varner-backend.php';

// ─── Database Version & Auto-Upgrade ──────────────────────────────────────────
define('VARNER_OS_DB_VERSION', '1.23.7');

add_action('plugins_loaded', 'varner_os_db_check');
function varner_os_db_check() {
    if (get_option('varner_os_db_version') !== VARNER_OS_DB_VERSION) {
        varner_os_activate();
        update_option('varner_os_db_version', VARNER_OS_DB_VERSION);
    }
}

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
    
    // Check if it's a mobile logout or standard web session logout
    $token = '';
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $token = sanitize_text_field($matches[1]);
        }
    } elseif (isset($_GET['mobile_token'])) {
        $token = sanitize_text_field($_GET['mobile_token']);
    }

    if ($token && strlen($token) === 16 && ctype_xdigit($token)) {
        // Clear mobile token transient
        delete_transient('varner_mobile_token_' . $token);
    } else {
        $token = wp_get_session_token();
    }

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
    $html_file = plugin_dir_path(__FILE__) . 'dist/index.html';
    if (!file_exists($html_file)) {
        error_log("Varner OS: dist/index.html not found.");
        return;
    }

    $html = file_get_contents($html_file);
    
    $js_file = '';
    if (preg_match('/src="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.js)"/', $html, $matches)) {
        $js_file = $matches[1];
    }

    $css_file = '';
    if (preg_match('/href="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.css)"/', $html, $matches)) {
        $css_file = $matches[1];
    }

    $dist_url = plugin_dir_url(__FILE__) . 'dist/assets/';
    $dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';

    if ($js_file && file_exists($dist_path . $js_file)) {
        $ver = filemtime($dist_path . $js_file);
        wp_enqueue_script('varner-react-app', $dist_url . $js_file, array(), $ver, true);
        wp_localize_script('varner-react-app', 'varnerData', array(
            'post_id' => get_the_ID(),
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url()),
            'site_url' => esc_url_raw(home_url('/')),
        ));
    } else {
        error_log("Varner OS: JS asset not found or not matched. js_file: " . $js_file);
    }

    if ($css_file && file_exists($dist_path . $css_file)) {
        $ver = filemtime($dist_path . $css_file);
        wp_enqueue_style('varner-tailwind', $dist_url . $css_file, array(), $ver);
    } else {
        error_log("Varner OS: CSS asset not found or not matched. css_file: " . $css_file);
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

// ─── MOBILE COMPANION PWA ROUTER INTERCEPTS ───────────────────────────────────

add_action('init', 'varner_os_mobile_pwa_router');
function varner_os_mobile_pwa_router() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $uri_path = trim(parse_url($uri, PHP_URL_PATH), '/');

    // Support WordPress installations in a subdirectory
    $site_path = parse_url(home_url(), PHP_URL_PATH);
    $site_path = $site_path ? trim($site_path, '/') : '';

    if ($site_path !== '' && strpos($uri_path, $site_path) === 0) {
        $path = trim(substr($uri_path, strlen($site_path)), '/');
    } else {
        $path = $uri_path;
    }

    // Get the launcher icon URL (prefers VE_Tractor_Icon.png from active theme assets or uploads)
    $icon_url = '';
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

    // Handle manifest.json
    if ($path === 'mobile-app/manifest.json' || $path === 'manifest.json') {
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
                    'src'   => $icon_url,
                    'sizes' => '192x192 512x512',
                    'type'  => 'image/png',
                    'purpose' => 'any maskable'
                )
            )
        ));
        exit;
    }

    // Handle sw.js
    if ($path === 'mobile-app/sw.js' || $path === 'sw.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        ?>
        const CACHE_NAME = 'varner-mobile-cache-v1';
        const ASSETS = [
            '<?php echo esc_url_raw(home_url('/mobile-app/')); ?>',
        ];

        self.addEventListener('install', (e) => {
            e.waitUntil(
                caches.open(CACHE_NAME).then((cache) => {
                    return cache.addAll(ASSETS);
                }).then(() => self.skipWaiting())
            );
        });

        self.addEventListener('activate', (e) => {
            e.waitUntil(
                caches.keys().then((keys) => {
                    return Promise.all(
                        keys.map((k) => {
                            if (k !== CACHE_NAME) return caches.delete(k);
                        })
                    );
                }).then(() => self.clients.claim())
            );
        });

        self.addEventListener('fetch', (e) => {
            const url = new URL(e.request.url);
            
            // Bypass cache for REST API and admin calls
            if (url.pathname.includes('/wp-json/') || url.pathname.includes('/wp-admin/')) {
                e.respondWith(fetch(e.request));
                return;
            }

            e.respondWith(
                caches.match(e.request).then((cached) => {
                    return cached || fetch(e.request).then((response) => {
                        // Cache text/css, text/javascript and images dynamically
                        const mime = response.headers.get('content-type') || '';
                        if (response.status === 200 && (mime.includes('css') || mime.includes('javascript') || mime.includes('image') || mime.includes('font'))) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(e.request, clone);
                            });
                        }
                        return response;
                    });
                }).catch(() => {
                    // Offline fallback for html requests
                    if (e.request.headers.get('accept').includes('text/html')) {
                        return caches.match('<?php echo esc_url_raw(home_url('/mobile-app/')); ?>');
                    }
                })
            );
        });
        <?php
        exit;
    }

    // Handle /mobile-app/
    if ($path === 'mobile-app') {
        status_header(200);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
            <title>Varner OS Mobile Companion</title>
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <meta name="theme-color" content="#0f172a">
            <link rel="manifest" href="<?php echo esc_url(home_url('/manifest.json')); ?>">
            <link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">
            <style>
                html, body {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    height: 100%;
                    background-color: #f8fafc;
                    overflow: hidden;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }
                #varner-inventory-app {
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                }
            </style>
            <script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('<?php echo esc_url_raw(home_url('/sw.js')); ?>')
                            .then(reg => console.log('Service Worker registered successfully!', reg.scope))
                            .catch(err => console.log('Service Worker registration failed:', err));
                    });
                }
            </script>
            <?php
            $html_file = plugin_dir_path(__FILE__) . 'dist/index.html';
            $js_file = '';
            $css_file = '';
            if (file_exists($html_file)) {
                $html = file_get_contents($html_file);
                if (preg_match('/src="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.js)"/', $html, $matches)) {
                    $js_file = $matches[1];
                }
                if (preg_match('/href="(?:\.\/)?assets\/(index-[a-zA-Z0-9_\-]+\.css)"/', $html, $matches)) {
                    $css_file = $matches[1];
                }
            }

            $dist_url = plugin_dir_url(__FILE__) . 'dist/assets/';
            $dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';

            if ($js_file && $css_file && file_exists($dist_path . $js_file) && file_exists($dist_path . $css_file)) {
                $ver = filemtime($dist_path . $js_file);
                $css_ver = filemtime($dist_path . $css_file);
                ?>
                <link rel="stylesheet" href="<?php echo $dist_url . $css_file . '?ver=' . $css_ver; ?>">
                <script>
                    window.varnerData = {
                        post_id: 0,
                        nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
                        rest_url: '<?php echo esc_url_raw(rest_url()); ?>',
                        site_url: '<?php echo esc_url_raw(home_url('/')); ?>',
                        is_mobile_app: true
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
