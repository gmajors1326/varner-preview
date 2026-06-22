<?php
/**
 * Plugin Name: Varner OS Plugin v23
 * Description: Version 1.23.195 - React-powered inventory management for Varner Equipment.
 * Version: 1.23.195
 * Author: hwy559.com
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'varner-backend.php';
require_once plugin_dir_path(__FILE__) . 'rest-api.php';
require_once plugin_dir_path(__FILE__) . 'varner-facebook-pwa.php';
require_once plugin_dir_path(__FILE__) . 'varner-meta-sync.php';

// ─── Security Helper ─────────────────────────────────────────────────────────

/**
 * One-way hash a raw session token for safe storage in the custom sessions table.
 * Uses HMAC-SHA256 keyed with SECURE_AUTH_KEY so the stored value cannot be
 * reversed into a usable WP session cookie even if the table is breached.
 */
function varner_os_hash_session_token( string $token ): string {
    $key = defined('SECURE_AUTH_KEY') && SECURE_AUTH_KEY ? SECURE_AUTH_KEY : wp_salt('secure_auth');
    return hash_hmac( 'sha256', $token, $key );
}

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

    // Generate/refresh the static facebook-catalog.csv file
    varner_os_write_facebook_catalog_file();
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
            'session_token' => $token ? varner_os_hash_session_token($token) : '',
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
    }
    // Note: $_GET['mobile_token'] intentionally removed — tokens must arrive via
    // HTTP headers only. GET params appear in server logs and Referer headers.

    if ($token && strlen($token) === 32 && ctype_xdigit($token)) {
        delete_transient('varner_mobile_token_' . $token);
    } else {
        $token = wp_get_session_token();
    }

    $hashed    = $token ? varner_os_hash_session_token($token) : '';
    $where_sql = $hashed ? 'session_token = %s AND logout_at IS NULL' : 'user_id = %d AND logout_at IS NULL';
    $param     = $hashed ?: $user->ID;
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
    if (!(defined('REST_REQUEST') && REST_REQUEST)) {
        return $user_id;
    }
    if ($user_id) {
        return $user_id;
    }

    $token = '';
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = sanitize_text_field($matches[1]);
    }
    // Note: $_GET['mobile_token'] intentionally removed — GET params leak into logs.

    if (empty($token)) {
        return $user_id;
    }

    $data = get_transient('varner_mobile_token_' . $token);
    if (is_array($data) && isset($data['user_id'])) {
        $created_at = isset($data['created_at']) ? intval($data['created_at']) : 0;
        if (time() - $created_at > 86400) { // 24-hour absolute expiration
            delete_transient('varner_mobile_token_' . $token);
            return $user_id;
        }
        set_transient('varner_mobile_token_' . $token, $data, 1800);
        return intval($data['user_id']);
    } elseif ($data && !is_array($data)) {
        // Fallback for legacy plain user_id transients
        set_transient('varner_mobile_token_' . $token, $data, 1800);
        return intval($data);
    }

    return $user_id;
}

// ─── Session Activity Tracker (moved from rest-api.php) ────────────────────────────

add_action('init', 'varner_update_session_activity');
function varner_update_session_activity(): void {
    if (!current_user_can('edit_posts')) {
        return;
    }
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
    }
    // Note: $_GET['mobile_token'] intentionally removed — GET params leak into logs.

    if (empty($token)) {
        $token = wp_get_session_token();
    }

    if (empty($token)) {
        return;
    }

    $hashed_token = varner_os_hash_session_token($token);

    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT id, last_activity_at, logout_at FROM {$table} WHERE session_token = %s",
        $hashed_token
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
                if (strlen($old_sess->session_token) === 32 && ctype_xdigit($old_sess->session_token)) {
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
                'session_token'    => varner_os_hash_session_token($token),
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

// ─── Hide Admin Bar for Staff ────────────────────────────────────────────────
add_action('init', function (): void {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});

// ─── Login Redirect ──────────────────────────────────────────────────────────

add_filter('login_redirect', function (string $redirect_to, $request, $user): string {
    if (!($user instanceof WP_User)) {
        return $redirect_to;
    }

    // If the user can manage options, send them to the Varner OS admin page (or mobile app if requested)
    if (user_can($user, 'manage_options')) {
        if (!empty($redirect_to) && str_contains($redirect_to, '/mobile-app/')) {
            return home_url('/mobile-app/');
        }
        return admin_url('admin.php?page=varner-os');
    }

    // Everyone else (staff, editors, etc.) goes straight to the mobile companion app - no WordPress admin screens at all.
    return home_url('/mobile-app/');
}, 999, 3);


// ─── Branded Login Page ───────────────────────────────────────────────

add_action('login_enqueue_scripts', function (): void {
    $icon_url = get_transient('varner_pwa_icon_url') ?: '';
    if (!$icon_url && file_exists(get_stylesheet_directory() . '/assets/VE_Tractor_Icon.png')) {
        $icon_url = get_stylesheet_directory_uri() . '/assets/VE_Tractor_Icon.png';
    } elseif (!$icon_url && file_exists(get_template_directory() . '/assets/VE_Tractor_Icon.png')) {
        $icon_url = get_template_directory_uri() . '/assets/VE_Tractor_Icon.png';
    }
    $icon_esc = esc_url($icon_url);
    ?>
    <style>
    body.login {
        background:#0f172a;
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
        display:flex;align-items:center;justify-content:center;
        min-height:100vh;margin:0;padding:16px;box-sizing:border-box;
    }
    body.login::before {
        content:'';position:fixed;inset:0;
        background:
            radial-gradient(ellipse 80% 60% at 10% 20%,rgba(29,78,216,.2) 0%,transparent 60%),
            radial-gradient(ellipse 60% 50% at 90% 80%,rgba(29,78,216,.10) 0%,transparent 60%);
        pointer-events:none;z-index:0;
    }
    #login {
        background:rgba(15,23,42,.9);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
        border:1px solid rgba(255,255,255,.08);border-radius:24px;
        padding:44px 40px 36px;width:100%;max-width:420px;
        box-shadow:0 32px 80px rgba(0,0,0,.6),0 0 0 1px rgba(29,78,216,.15);
        position:relative;z-index:1;
    }
    <?php if ($icon_esc): ?>
    #login h1 {text-align:center;}
    #login h1 a {
        background-image:url('<?php echo $icon_esc; ?>');
        background-size:contain;background-repeat:no-repeat;background-position:center;
        width:72px;height:72px;display:block;margin:0 auto 8px;
        border-radius:18px;box-shadow:0 8px 24px rgba(29,78,216,.4);
    }
    <?php else: ?>
    #login h1 {text-align:center;}
    #login h1 a {
        background:linear-gradient(135deg,#1d4ed8,#3b82f6);
        width:72px;height:72px;display:block;margin:0 auto 8px;
        border-radius:18px;box-shadow:0 8px 24px rgba(29,78,216,.4);
    }
    <?php endif; ?>
    .varner-login-brand{text-align:center;margin-bottom:28px;}
    .varner-login-brand h2{font-size:22px;font-weight:900;letter-spacing:-.03em;color:#fff;margin:0 0 4px;text-transform:uppercase;}
    .varner-login-brand h2 span{color:#3b82f6;}
    .varner-login-brand p{font-size:11px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#475569;margin:0;}
    .login label{color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;}
    .login input[type=text],.login input[type=password],.login input[type=email] {
        background:#ffffff!important;border:1px solid rgba(255,255,255,.15)!important;
        border-radius:12px!important;color:#1e293b!important;-webkit-text-fill-color:#1e293b!important;font-size:15px!important;
        padding:13px 16px!important;width:100%!important;box-sizing:border-box!important;
        outline:none!important;box-shadow:none!important;height:auto!important;transition:border-color .15s,background .15s!important;
    }
    .login input[type=text]:-webkit-autofill,.login input[type=password]:-webkit-autofill {
        -webkit-box-shadow:0 0 0 1000px #ffffff inset!important;
        -webkit-text-fill-color:#1e293b!important;
    }

    .login input[type=text]:focus,.login input[type=password]:focus {
        background:rgba(255,255,255,.08)!important;border-color:#3b82f6!important;
        box-shadow:0 0 0 3px rgba(59,130,246,.2)!important;
    }
    .login #wp-submit,.login .button-primary {
        background:#dc2626!important;border:none!important;border-radius:12px!important;
        color:#fff!important;font-size:12px!important;font-weight:900!important;
        letter-spacing:.15em!important;text-transform:uppercase!important;
        padding:14px 24px!important;width:100%!important;height:auto!important;
        margin-top:50px!important;
        box-shadow:0 4px 16px rgba(220,38,38,.4)!important;cursor:pointer!important;
        transition:background .15s,transform .1s,box-shadow .15s!important;
    }
    .login #wp-submit:hover,.login .button-primary:hover {
        background:#b91c1c!important;box-shadow:0 6px 24px rgba(220,38,38,.5)!important;transform:translateY(-1px)!important;
    }
    .login #wp-submit:active{transform:translateY(0)!important;}
    .login .forgetmenot{display:flex;align-items:center;gap:8px;}
    .login .forgetmenot input[type=checkbox]{width:16px!important;height:16px!important;accent-color:#3b82f6;}
    .login .forgetmenot label{color:#64748b;font-size:11px;font-weight:600;text-transform:none;letter-spacing:.05em;}
    #login_error,.message,.success {
        background:rgba(239,68,68,.12)!important;border:1px solid rgba(239,68,68,.25)!important;
        border-radius:10px!important;color:#fca5a5!important;font-size:12px!important;
        padding:12px 14px!important;margin-bottom:20px!important;box-shadow:none!important;
    }
    .message{background:rgba(59,130,246,.12)!important;border-color:rgba(59,130,246,.25)!important;color:#93c5fd!important;}
    #nav,#backtoblog{text-align:center;margin-top:20px;}
    #nav a,#backtoblog a{color:#475569!important;font-size:11px!important;font-weight:700!important;letter-spacing:.08em!important;text-decoration:none!important;text-transform:uppercase!important;}
    #nav a:hover,#backtoblog a:hover{color:#94a3b8!important;}
    .varner-login-footer{text-align:center;margin-top:24px;font-size:10px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#1e293b;}
    .login #login_footer,.privacy-policy-page-link{display:none!important;}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var login = document.getElementById('login');
        if (!login) return;
        // Brand header
        var brand = document.createElement('div');
        brand.className = 'varner-login-brand';
        brand.innerHTML = '<h2>Varner Equipment <span>OS</span></h2>';
        login.insertBefore(brand, login.firstChild);
        // Footer
        var footer = document.createElement('div');
        footer.className = 'varner-login-footer';
        footer.textContent = 'Secure Admin Access · Varner Equipment';
        login.appendChild(footer);
        // Fix logo title
        var logo = login.querySelector('h1 a');
        if (logo) logo.setAttribute('title', 'Varner Equipment OS');
    });
    </script>
    <?php
});

add_filter('login_headerurl',  fn() => home_url('/'));
add_filter('login_headertext', fn() => 'Varner Equipment');

// ─── Full-Width UI for Sales Staff ───────────────────────────────────────────
// Admins keep the WP sidebar. Editors (Sales Staff) get a clean full-width UI.

add_action('admin_head', function (): void {
    if (current_user_can('manage_options')) {
        return; // Admins: leave sidebar intact
    }
    ?>
    <style id="varner-full-width-ui">
        /* Hide left sidebar and its toggle */
        #adminmenuwrap,
        #adminmenuback,
        #collapse-button {
            display: none !important;
        }
        /* Expand content area to full width */
        #wpcontent,
        #wpfooter {
            margin-left: 0 !important;
        }
        /* Remove the top gap the sidebar normally creates */
        #wpbody {
            padding-top: 0 !important;
        }
        /* Hide the .wrap h1 page title (redundant — Varner OS has its own) */
        .wrap > h1:first-child {
            display: none !important;
        }
        /* Tighten the admin bar — keep it for logout access */
        #wpadminbar {
            background: #0f172a !important;
        }
        #wpadminbar .ab-top-menu > li > .ab-item,
        #wpadminbar #wp-admin-bar-site-name > .ab-item {
            color: #475569 !important;
        }
        #wpadminbar #wp-admin-bar-my-account .ab-item {
            color: #94a3b8 !important;
        }
    </style>
    <?php
});

// ─── Lock WP Admin and Dashboard to Users who can Manage Options ──────────────
// Only administrators (who can manage options) are allowed to access WP admin screens.
// Everyone else (staff, editors, etc.) is blocked from wp-admin and redirected to the mobile PWA companion.
// AJAX and REST requests are allowed to proceed.

add_action('admin_init', function (): void {
    // Never block AJAX or REST requests
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    // Administrators: unrestricted
    if (current_user_can('manage_options')) {
        return;
    }

    // Redirect everyone else to the mobile app
    wp_safe_redirect(home_url('/mobile-app/'));
    exit;
});

// ─── Admin Page Renderers ────────────────────────────────────────────────────

function varner_render_dashboard_page(): void {
    $logo_url = function_exists('varner_get_brand_logo_url') ? varner_get_brand_logo_url('white') : '';
    if ($logo_url) {
        echo '<div class="wrap" style="margin:0;padding:24px 0 0;text-align:center;background:#0a0a0b;"><img src="' . esc_url($logo_url) . '" alt="Varner Equipment" style="height:40px;width:auto;opacity:0.9;"></div>';
    }
    echo '<div class="wrap" style="margin:0;padding:0;background:#0a0a0b;"><div id="varner-inventory-app" class="varner-inventory-app-mount" style="min-height:90vh;"></div></div>';
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

// Facebook catalog + PWA router extracted to varner-facebook-pwa.php

