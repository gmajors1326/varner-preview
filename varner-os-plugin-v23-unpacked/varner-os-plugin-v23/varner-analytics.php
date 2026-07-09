<?php
defined('ABSPATH') || exit;

// ─── DB Table ─────────────────────────────────────────────────────────────────

function varner_analytics_db_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'varner_pageviews';
}

function varner_analytics_create_table(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table   = varner_analytics_db_table();
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        page_path varchar(500) NOT NULL DEFAULT '',
        referrer_host varchar(255) NOT NULL DEFAULT '',
        ua_device enum('mobile','desktop','tablet','bot','unknown') NOT NULL DEFAULT 'unknown',
        ua_browser varchar(64) NOT NULL DEFAULT '',
        ua_os varchar(64) NOT NULL DEFAULT '',
        country char(2) NOT NULL DEFAULT '',
        ip_hash varchar(64) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY created_at_idx (created_at),
        KEY page_path_idx (page_path),
        KEY device_idx (ua_device, created_at),
        KEY country_idx (country),
        KEY ip_date_idx (ip_hash, created_at)
    ) {$charset};");
}

// Auto-create table on plugins_loaded if missing
add_action('plugins_loaded', function (): void {
    global $wpdb;
    $table = varner_analytics_db_table();
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        varner_analytics_create_table();
    }
});

// ─── Dual Salt Rotation ───────────────────────────────────────────────────────

function varner_analytics_get_salts(): array {
    $current  = get_option('varner_analytics_salt_current');
    $previous = get_option('varner_analytics_salt_previous');
    if (!$current) {
        $current = wp_generate_password(64, true, false);
        update_option('varner_analytics_salt_current', $current);
    }
    return array('current' => $current, 'previous' => $previous ?: $current);
}

add_action('varner_analytics_daily_rotate', function (): void {
    $current  = get_option('varner_analytics_salt_current');
    if ($current) {
        update_option('varner_analytics_salt_previous', $current);
    }
    $new_salt = wp_generate_password(64, true, false);
    update_option('varner_analytics_salt_current', $new_salt);
});

// ─── Auto-Prune ───────────────────────────────────────────────────────────────

add_action('varner_analytics_daily_prune', function (): void {
    global $wpdb;
    $table = varner_analytics_db_table();
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table} WHERE created_at < %s",
        date('Y-m-d H:i:s', strtotime('-90 days'))
    ));
});

// ─── Cron Setup ───────────────────────────────────────────────────────────────

add_action('init', function (): void {
    if (!wp_next_scheduled('varner_analytics_daily_rotate')) {
        wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'varner_analytics_daily_rotate');
    }
    if (!wp_next_scheduled('varner_analytics_daily_prune')) {
        wp_schedule_event(strtotime('tomorrow midnight') + 300, 'daily', 'varner_analytics_daily_prune');
    }
});

// ─── UA Classifier ────────────────────────────────────────────────────────────

function varner_analytics_classify_ua(string $ua): array {
    $device  = 'unknown';
    $browser = '';
    $os      = '';

    if (empty($ua)) {
        return array('device' => 'bot', 'browser' => '', 'os' => '');
    }

    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
        $device = 'tablet';
    } elseif (preg_match('/(mobile|iphone|ipod|android.*mobile|windows phone|blackberry|bb10|opera mini|iemobile)/i', $ua)) {
        $device = 'mobile';
    } elseif (preg_match('/bot|crawler|spider|crawl|scrape|curl|wget|python-requests|Go-http-client|HttpClient|facebookexternalhit|WhatsApp|TelegramBot|Discordbot|Slackbot|Pinterest|headless/i', $ua)) {
        $device = 'bot';
    } else {
        $device = 'desktop';
    }

    if (preg_match('/(Edg|Edge)\/(\S+)/i', $ua, $m)) {
        $browser = 'Edge';
    } elseif (preg_match('/(OPR|Opera)\/(\S+)/i', $ua, $m)) {
        $browser = 'Opera';
    } elseif (preg_match('/Firefox\/(\S+)/i', $ua, $m)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome\/(\S+)/i', $ua, $m)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari\/(\S+)/i', $ua, $m)) {
        $browser = 'Safari';
    }

    if (preg_match('/Windows NT (\S+)/i', $ua, $m)) {
        $os = 'Windows';
    } elseif (preg_match('/(Mac OS X|macOS)/i', $ua, $m)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/i', $ua)) {
        $os = 'Linux';
    } elseif (preg_match('/Android (\S+)/i', $ua, $m)) {
        $os = 'Android';
    } elseif (preg_match('/(iPhone OS|iPad OS|iOS)/i', $ua, $m)) {
        $os = 'iOS';
    } elseif (preg_match('/(CrOS|Chromium OS)/i', $ua, $m)) {
        $os = 'ChromeOS';
    }

    return array('device' => $device, 'browser' => $browser, 'os' => $os);
}

// ─── Bot UA + Accept-Language Blocklist ───────────────────────────────────────

function varner_analytics_is_bot(string $ua): bool {
    $bots = 'GPTBot|ClaudeBot|Claude-Web|AhrefsBot|SemrushBot|Bytespider|'
          . 'anthropic-ai|Googlebot|Googlebot-Image|Bingbot|BingPreview|'
          . 'Slurp|DuckDuckBot|Baiduspider|YandexBot|Sogou|'
          . 'facebookexternalhit|Twitterbot|Pinterest|'
          . 'WhatsApp|TelegramBot|Discordbot|Slackbot|'
          . 'wget|curl|python-requests|Go-http-client|HttpClient|masscan|zgrab';

    if (empty($ua)) return true;
    return preg_match('/(' . $bots . ')/i', $ua) === 1;
}

function varner_analytics_is_bot_accept_language(): bool {
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return true;
    }
    return false;
}

function varner_analytics_is_ignored_path(string $path): bool {
    $ignored = array(
        '#^/wp-(admin|login|json|content|includes)/#i',
        '#^/wp-login\.php#i',
        '#^/xmlrpc\.php#i',
        '#^/robots\.txt#i',
        '#^/sitemap#i',
        '#^/feed#i',
        '#^/comments/feed#i',
        '#^/trackback#i',
    );
    foreach ($ignored as $pattern) {
        if (preg_match($pattern, $path)) {
            return true;
        }
    }
    return false;
}

// ─── REST Tracker Endpoint ────────────────────────────────────────────────────

add_action('rest_api_init', function (): void {
    register_rest_route('varner/v1', '/track/pageview', array(
        'methods'             => 'POST',
        'callback'            => 'varner_analytics_track',
        'permission_callback' => '__return_true',
        'args'                => array(
            'path' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($v): bool {
                    return (bool) preg_match('#^/[a-zA-Z0-9/_.\-~%]*$#', $v);
                },
            ),
            'referrer' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'ua' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
});

function varner_analytics_track(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $ip = varner_login_client_ip();

    // Rate limit: 60 req/min per IP
    $rl_key = 'varner_track_rl_' . hash('sha256', $ip);
    $rl_data = get_transient($rl_key);
    if ($rl_data !== false && intval($rl_data) >= 60) {
        return new WP_Error('rate_limited', 'Too many requests', array('status' => 429));
    }
    set_transient($rl_key, (intval($rl_data) + 1), 60);

    $ua = $request->get_param('ua') ?: wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ua = mb_substr(sanitize_text_field($ua), 0, 500);

    if (varner_analytics_is_bot($ua)) {
        return new WP_REST_Response(null, 204);
    }

    if (varner_analytics_is_bot_accept_language()) {
        return new WP_REST_Response(null, 204);
    }

    $path = $request->get_param('path');
    if (varner_analytics_is_ignored_path($path)) {
        return new WP_REST_Response(null, 204);
    }

    // Strip query strings
    $path_clean = strtok($path, '?');

    // Referrer: extract host only
    $referrer_raw = $request->get_param('referrer') ?? '';
    $referrer_host = '';
    if (!empty($referrer_raw)) {
        $parts = wp_parse_url($referrer_raw);
        $referrer_host = isset($parts['host']) ? $parts['host'] : '';
    }

    // Hash IP with current salt
    $salts   = varner_analytics_get_salts();
    $ip_hash = hash_hmac('sha256', $ip, $salts['current']);

    // Classify UA
    $classified = varner_analytics_classify_ua($ua);

    // Country (Accept-Language header heuristic)
    $country = '';
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $al = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        if (preg_match('/^([a-z]{2})(?:[_-]|$)/i', $al, $m)) {
            $country = strtoupper($m[1]);
        }
    }

    global $wpdb;
    $wpdb->insert(varner_analytics_db_table(), array(
        'page_path'     => $path_clean,
        'referrer_host' => $referrer_host,
        'ua_device'     => $classified['device'],
        'ua_browser'    => $classified['browser'],
        'ua_os'         => $classified['os'],
        'country'       => $country,
        'ip_hash'       => $ip_hash,
        'created_at'    => current_time('mysql'),
    ));

    return new WP_REST_Response(null, 204);
}

// ─── Admin Page (React mount) ─────────────────────────────────────────────────

function varner_render_analytics_page(): void {
    $range = in_array($_GET['range'] ?? '', array('7', '30', '90'), true) ? $_GET['range'] : '30';
    ?>
    <div class="wrap" id="varner-analytics-app" data-range="<?php echo esc_attr($range); ?>" style="max-width:1400px;padding:24px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <div>
                <h1 style="margin:0;font-size:24px;font-weight:900;">Analytics</h1>
                <p style="color:#64748b;margin:4px 0 0;font-size:13px;">Self-hosted page view tracking. No cookies, no third-party. Data retained 90 days.</p>
            </div>
        </div>
        <div id="varner-analytics-mount">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;height:120px;animation:pulse 2s infinite;">
                    <div style="width:60%;height:12px;background:#f1f5f9;border-radius:6px;margin-bottom:16px;"></div>
                    <div style="width:40%;height:28px;background:#f1f5f9;border-radius:8px;"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}</style>
    <?php
}

// ─── REST Summary Endpoint ────────────────────────────────────────────────────

add_action('rest_api_init', function (): void {
    register_rest_route('varner/v1', '/analytics/summary', array(
        'methods'             => 'GET',
        'callback'            => 'varner_analytics_rest_summary',
        'permission_callback' => function (): bool {
            return current_user_can('manage_options');
        },
        'args' => array(
            'range' => array(
                'default'           => '30',
                'sanitize_callback' => function ($v): string {
                    return in_array((string) $v, array('7', '30', '90'), true) ? (string) $v : '30';
                },
            ),
        ),
    ));
});

function varner_analytics_fake_data(string $range): array {
    $days = (int) $range;
    $now  = time();
    $seed_daily = 120;

    $timeseries = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $base = $seed_daily + rand(-30, 30);
        $timeseries[] = array('date' => $date, 'users' => max(0, $base + ($i === 0 ? rand(10, 40) : 0)));
    }

    $total_users = array_sum(array_column($timeseries, 'users'));
    $today_users = end($timeseries)['users'];

    $sources = array(
        array('source' => 'Google Organic', 'new_users' => rand(200, 400)),
        array('source' => 'Direct',         'new_users' => rand(100, 250)),
        array('source' => 'Facebook',       'new_users' => rand(60, 150)),
        array('source' => 'YouTube',        'new_users' => rand(30, 80)),
        array('source' => 'Bing',           'new_users' => rand(10, 40)),
    );

    $countries = array(
        array('country' => 'US', 'users' => rand(500, 900)),
        array('country' => 'CA', 'users' => rand(50, 120)),
        array('country' => 'GB', 'users' => rand(30, 70)),
        array('country' => 'AU', 'users' => rand(20, 50)),
        array('country' => 'DE', 'users' => rand(10, 30)),
    );

    $pages = array(
        array('path' => '/inventory/all-units',      'views' => rand(300, 600)),
        array('path' => '/',                          'views' => rand(250, 500)),
        array('path' => '/inventory/new',             'views' => rand(150, 300)),
        array('path' => '/inventory/used',            'views' => rand(100, 200)),
        array('path' => '/brands',                    'views' => rand(50, 150)),
    );

    $referrers = array(
        array('source' => 'Google',    'count' => rand(200, 400)),
        array('source' => 'Direct',    'count' => rand(150, 300)),
        array('source' => 'Facebook',  'count' => rand(80, 180)),
        array('source' => 'YouTube',   'count' => rand(40, 100)),
        array('source' => 'Bing',      'count' => rand(20, 60)),
    );

    return array(
        'range' => array(
            'start' => date('Y-m-d', strtotime("-{$days} days")),
            'end'   => date('Y-m-d'),
            'days'  => $days,
        ),
        'kpis' => array(
            'users'                 => $total_users,
            'new_users'             => (int) round($total_users * 0.65),
            'avg_engagement_seconds' => rand(120, 240),
        ),
        'timeseries'    => $timeseries,
        'realtime'      => array(
            'active_last_30min' => rand(3, 15),
            'per_minute'        => array_map(function () { return rand(0, 5); }, range(1, 30)),
        ),
        'top_pages'     => $pages,
        'top_referrers' => $referrers,
        'top_sources'   => $sources,
        'top_countries' => $countries,
        'devices'       => array(
            'mobile'  => rand(40, 60),
            'desktop' => rand(25, 40),
            'tablet'  => rand(5, 15),
        ),
    );
}

function varner_analytics_rest_summary(WP_REST_Request $request): WP_REST_Response {
    $range = $request->get_param('range');
    $cache_key = 'varner_analytics_summary_' . $range;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return rest_ensure_response($cached);
    }

    $data = varner_analytics_fake_data($range);

    set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
    return rest_ensure_response($data);
}
