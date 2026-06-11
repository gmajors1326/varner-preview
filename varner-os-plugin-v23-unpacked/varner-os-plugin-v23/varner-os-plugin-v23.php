<?php
/**
 * Plugin Name: Varner OS Plugin v23
 * Description: Version 1.23.102 - React-powered inventory management for Varner Equipment.
 * Version: 1.23.102
 * Author: hwy559.com
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'varner-backend.php';
require_once plugin_dir_path(__FILE__) . 'rest-api.php';

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

    if ($token && strlen($token) === 16 && ctype_xdigit($token)) {
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

// ─── Login Redirect ──────────────────────────────────────────────────────────

add_filter('login_redirect', function (string $redirect_to, $request, $user): string {
    if (!($user instanceof WP_User) || !$user->has_cap('edit_posts')) {
        return $redirect_to;
    }
    // If the user was trying to reach the mobile app, send them there.
    // The auto-token logic will authenticate them silently on arrival.
    if (!empty($redirect_to) && str_contains($redirect_to, '/mobile-app/')) {
        return home_url('/mobile-app/');
    }
    // Everyone else (desktop) goes straight to Varner OS — no WP dashboard.
    return admin_url('admin.php?page=varner-os');
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

// ─── Lock Raw WP Admin to Owner Only ─────────────────────────────────────────
// Only user ID 1 (the owner) can browse raw WP pages (editor, plugins, themes, etc.).
// All other staff — even if they have administrator role — are sent to Varner OS.
// AJAX, REST, and Varner OS pages are always allowed through.

add_action('admin_init', function (): void {
    $owner_id = (int) get_option('varner_owner_user_id', 1);

    // Owner: unrestricted
    if (get_current_user_id() === $owner_id) {
        return;
    }

    // Only applies to staff (edit_posts capability)
    if (!current_user_can('edit_posts')) {
        return;
    }

    // Never block AJAX or REST requests
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    // Allow Varner OS admin pages
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    if (in_array($page, ['varner-os', 'varner-os-config'], true)) {
        return;
    }

    // Redirect everyone else straight to Varner OS
    wp_safe_redirect(admin_url('admin.php?page=varner-os'));
    exit;
});

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

    // ── Manifest ─────────────────────────────────────────────────────────────
    if ($path === 'mobile-app/manifest.json' || $path === 'manifest.json') {
        $icon_cache_key = 'varner_pwa_icon_url';
        $icon_url       = get_transient($icon_cache_key);
        if (!$icon_url) {
            if (file_exists(get_stylesheet_directory() . '/assets/VE_Tractor_Icon.png')) {
                $icon_url = get_stylesheet_directory_uri() . '/assets/VE_Tractor_Icon.png';
            } elseif (file_exists(get_template_directory() . '/assets/VE_Tractor_Icon.png')) {
                $icon_url = get_template_directory_uri() . '/assets/VE_Tractor_Icon.png';
            } else {
                $upload_dir = wp_upload_dir();
                $icon_url   = file_exists($upload_dir['basedir'] . '/2026/04/VE_Tractor_Icon.png')
                    ? $upload_dir['baseurl'] . '/2026/04/VE_Tractor_Icon.png'
                    : (varner_get_brand_logo_url('red') ?: plugin_dir_url(__FILE__) . 'dist/assets/logo.png');
            }
            set_transient($icon_cache_key, $icon_url, WEEK_IN_SECONDS);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: max-age=86400, public');
        echo json_encode(array(
            'id'               => '/mobile-app/',
            'name'             => 'Varner OS Mobile',
            'short_name'       => 'Varner OS',
            'description'      => 'Mobile inventory management for Varner Equipment yard crew.',
            'start_url'        => home_url('/mobile-app/'),
            'scope'            => home_url('/mobile-app/'),
            'display'          => 'standalone',
            'orientation'      => 'any',
            'background_color' => '#0f172a',
            'theme_color'      => '#0f172a',
            'categories'       => array('business', 'productivity'),
            'icons'            => array(
                array('src' => $icon_url, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => $icon_url, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
            ),
            'shortcuts' => array(
                array(
                    'name'       => 'New Listing',
                    'short_name' => 'Add Unit',
                    'url'        => home_url('/mobile-app/?action=new'),
                    'icons'      => array(array('src' => $icon_url, 'sizes' => '96x96', 'type' => 'image/png')),
                ),
                array(
                    'name'       => 'View Stock',
                    'short_name' => 'Stock',
                    'url'        => home_url('/mobile-app/?action=list'),
                    'icons'      => array(array('src' => $icon_url, 'sizes' => '96x96', 'type' => 'image/png')),
                ),
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    // ── Service Worker ────────────────────────────────────────────────────────
    if ($path === 'mobile-app/sw.js' || $path === 'sw.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate'); // SW must never be browser-cached

        $mobile_url = esc_url_raw(home_url('/mobile-app/'));

        // Build pre-cache list from the same transient used by the HTML shell.
        // Cache version is tied to JS asset mtime — auto-busts on every deploy.
        $html_file  = plugin_dir_path(__FILE__) . 'dist/index.html';
        $sw_js_url  = '';
        $sw_css_url = '';
        $sw_ver     = '1';

        if (file_exists($html_file)) {
            $sw_cache_key = 'varner_assets_' . filemtime($html_file);
            $sw_assets    = get_transient($sw_cache_key);
            if (!$sw_assets) {
                $sw_html = file_get_contents($html_file);
                $sw_js_f = $sw_css_f = '';
                if (preg_match('/src="(?:\.\/)assets\/(index-[a-zA-Z0-9_\-]+\.js)"/', $sw_html, $m))  $sw_js_f  = $m[1];
                if (preg_match('/href="(?:\.\/)assets\/(index-[a-zA-Z0-9_\-]+\.css)"/', $sw_html, $m)) $sw_css_f = $m[1];
                $sw_assets = array('js_file' => $sw_js_f, 'css_file' => $sw_css_f);
                set_transient($sw_cache_key, $sw_assets, DAY_IN_SECONDS);
            }
            $sw_dist_url  = plugin_dir_url(__FILE__)  . 'dist/assets/';
            $sw_dist_path = plugin_dir_path(__FILE__) . 'dist/assets/';
            if (!empty($sw_assets['js_file']) && file_exists($sw_dist_path . $sw_assets['js_file'])) {
                $sw_ver    = (string) filemtime($sw_dist_path . $sw_assets['js_file']);
                $sw_js_url = esc_url_raw($sw_dist_url . $sw_assets['js_file'] . '?ver=' . $sw_ver);
            }
            if (!empty($sw_assets['css_file']) && file_exists($sw_dist_path . $sw_assets['css_file'])) {
                $sw_css_url = esc_url_raw($sw_dist_url . $sw_assets['css_file'] . '?ver=' . $sw_ver);
            }
        }
        $sw_icon_url = esc_url_raw(get_transient('varner_pwa_icon_url') ?: '');
        ?>
/**
 * Varner OS — Mobile Companion Service Worker
 * Cache version: <?php echo esc_js($sw_ver); ?> (tied to JS asset mtime — auto-busts on every deploy)
 */

const CACHE_VERSION = '<?php echo esc_js($sw_ver); ?>';
const CACHE_NAME    = 'varner-os-' + CACHE_VERSION;

// App shell + compiled Vite dashboard assets — pre-cached at install for offline-first use
const PRE_CACHE = [
    '<?php echo esc_js($mobile_url); ?>'
    <?php if ($sw_css_url): ?>, '<?php echo esc_js($sw_css_url); ?>'<?php endif; ?>
    <?php if ($sw_js_url):  ?>, '<?php echo esc_js($sw_js_url); ?>'<?php endif; ?>
    <?php if ($sw_icon_url): ?>, '<?php echo esc_js($sw_icon_url); ?>'<?php endif; ?>
];

// These paths must always reach the live server — auth tokens & live inventory must never be stale
const NETWORK_ONLY = ['/wp-json/', '/varner/v1', '/wp-admin/', '/wp-login.php', '/sw.js', '/manifest.json'];

// ── Install: pre-cache app shell & all compiled dashboard assets ──────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Varner SW] Pre-caching ' + PRE_CACHE.length + ' assets (v' + CACHE_VERSION + ')');
                return cache.addAll(PRE_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// ── Activate: purge ALL stale caches from previous deploys ───────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => {
                const stale = keys.filter((k) => k !== CACHE_NAME);
                if (stale.length) console.log('[Varner SW] Purging ' + stale.length + ' stale cache(s)');
                return Promise.all(stale.map((k) => caches.delete(k)));
            })
            .then(() => self.clients.claim())
    );
});

// ── Fetch: three-tier caching strategy ───────────────────────────────────────
self.addEventListener('fetch', (event) => {
    // Never intercept mutations — POST/PATCH/DELETE must always reach the server
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Tier 1 — Network-Only: REST API, WP admin, auth endpoints, SW + manifest themselves
    if (NETWORK_ONLY.some((p) => url.pathname.includes(p))) return;

    // Tier 2 — Cache-First: Vite content-addressed static assets
    // Hash baked into filename — safe to serve from cache indefinitely
    if (/\.(js|css|woff2?|ttf|otf|png|webp|jpg|jpeg|gif|svg|ico)(\?.*)?$/i.test(url.pathname)) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Tier 3 — Network-First with app shell fallback: HTML navigation
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() =>
                caches.match(event.request).then(
                    (cached) => cached || caches.match('<?php echo esc_js($mobile_url); ?>')
                )
            )
    );
});
        <?php
        exit;
    }

    // Mobile app page
    if ($path === 'mobile-app') {
        // Not logged in — send to the branded login page, then bounce back here.
        // After login the auto-token logic will authenticate them silently.
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/mobile-app/')));
            exit;
        }
        status_header(200);
        ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Varner OS</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Varner OS">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0f172a">
    <meta name="msapplication-TileColor" content="#0f172a">
    <link rel="manifest" href="<?php echo esc_url(home_url('/mobile-app/manifest.json')); ?>">
    <?php $pwa_icon = esc_url(get_transient('varner_pwa_icon_url') ?: plugin_dir_url(__FILE__) . 'dist/assets/logo.png'); ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $pwa_icon; ?>">
    <link rel="apple-touch-startup-image" href="<?php echo $pwa_icon; ?>">

    <style>
        html, body { margin:0; padding:0; width:100%; height:100%; background-color:#f8fafc; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        body { padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); box-sizing:border-box; }
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
        ?><link rel="stylesheet" href="<?php echo esc_url( $dist_url . $css_file . '?ver=' . $css_ver ); ?>">
<script>
<?php
// Priority 1: Resolve handoff nonce (from "Launch on This Device" or QR scan).
// The nonce is single-use (deleted after first read) with a 2-minute TTL.
$mobile_token_for_page = '';
if (!empty($_GET['handoff'])) {
    $handoff_nonce = sanitize_text_field(wp_unslash($_GET['handoff']));
    if (strlen($handoff_nonce) === 16 && ctype_xdigit($handoff_nonce)) {
        $resolved = get_transient('varner_handoff_' . $handoff_nonce);
        if ($resolved) {
            $mobile_token_for_page = $resolved;
            delete_transient('varner_handoff_' . $handoff_nonce); // one-time use
        }
    }
}

// Priority 2: User is already logged into WordPress — skip the token gate entirely.
// Generate and embed a session token automatically so they land straight in the app.
if (!$mobile_token_for_page && is_user_logged_in() && current_user_can('edit_posts')) {
    $wp_user_id = get_current_user_id();
    $auto_token = strtoupper(bin2hex(random_bytes(8)));
    set_transient('varner_mobile_token_' . $auto_token, $wp_user_id, 1800);
    $mobile_token_for_page = $auto_token;
}
?>
window.varnerData = {
    post_id: 0,
    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
    rest_url: '<?php echo esc_url_raw(rest_url()); ?>',
    site_url: '<?php echo esc_url_raw(home_url('/')); ?>',
    is_mobile_app: true,
    mobile_token: '<?php echo esc_js($mobile_token_for_page); ?>',
    logo_url: '<?php echo function_exists('varner_get_brand_logo_url') ? esc_url(varner_get_brand_logo_url('white')) : ''; ?>'
};
</script>
<script type="module" src="<?php echo esc_url( $dist_url . $js_file . '?ver=' . $ver ); ?>"></script>
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
