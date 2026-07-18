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
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($v): bool {
                    if (empty($v)) return true; // validated in callback after body parsing
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
    // sendBeacon may send as text/plain — WP won't auto-parse the JSON body.
    // Manually decode php://input and set params if WP didn't parse them.
    if (!$request->get_param('path')) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $body = json_decode($raw, true);
            if (is_array($body)) {
                foreach ($body as $k => $v) {
                    if (is_string($v)) {
                        $request->set_param($k, sanitize_text_field($v));
                    }
                }
            }
        }
    }

    // Validate path after fallback parsing
    $path_val = $request->get_param('path');
    if (empty($path_val) || !preg_match('#^/[a-zA-Z0-9/_.\-~%]*$#', $path_val)) {
        return new WP_REST_Response(null, 204);
    }

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
    // Scan ALL language tags for a region subtag, not just the first.
    // Browsers often send "en,en-US;q=0.9" — bare language first, region later.
    $country = '';
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $al = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        if (preg_match_all('/[a-z]{2}[_-]([A-Za-z]{2})\b/', $al, $matches)) {
            // Use the first region subtag found (highest-priority tag with a region)
            $country = strtoupper($matches[1][0]);
        }
        
        // Normalize language-only or custom locales
        if (empty($country)) {
            if (preg_match('/^en\b/i', $al)) {
                $country = 'US';
            } elseif (preg_match('/^zh\b/i', $al)) {
                $country = 'CN';
            }
        }
        
        if ($country === 'EN') $country = 'US';
        if ($country === 'ZH') $country = 'CN';
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

function varner_analytics_rest_summary(WP_REST_Request $request): WP_REST_Response {
    global $wpdb;
    $range = $request->get_param('range');
    $cache_key = 'varner_analytics_summary_' . $range;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return rest_ensure_response($cached);
    }

    $days = (int) $range;
    $table = varner_analytics_db_table();
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $total_users = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT ip_hash) FROM {$table} WHERE created_at >= %s", $since
    ));

    $new_users = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT t1.ip_hash) FROM {$table} t1
         WHERE t1.created_at >= %s
         AND NOT EXISTS (
             SELECT 1 FROM {$table} t2
             WHERE t2.ip_hash = t1.ip_hash AND t2.created_at < %s
         )", $since, $since
    ));

    $timeseries_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as date, COUNT(DISTINCT ip_hash) as users
         FROM {$table} WHERE created_at >= %s
         GROUP BY DATE(created_at) ORDER BY date ASC", $since
    ), ARRAY_A);

    $ts_lookup = array();
    foreach ($timeseries_raw as $row) {
        $ts_lookup[$row['date']] = (int) $row['users'];
    }
    $timeseries = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $timeseries[] = array('date' => $d, 'users' => $ts_lookup[$d] ?? 0);
    }

    $realtime_since = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $active_last_30min = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT ip_hash) FROM {$table} WHERE created_at >= %s", $realtime_since
    ));

    $per_minute_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as minute, COUNT(*) as hits
         FROM {$table} WHERE created_at >= %s
         GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
         ORDER BY minute ASC", $realtime_since
    ), ARRAY_A);

    $pm_lookup = array();
    foreach ( (array) $per_minute_raw as $row ) {
        $pm_lookup[$row['minute']] = (int) $row['hits'];
    }
    $per_minute = array();
    for ($m = 29; $m >= 0; $m--) {
        $per_minute[] = $pm_lookup[date('Y-m-d H:i', strtotime("-{$m} minutes"))] ?? 0;
    }

    $top_pages = $wpdb->get_results($wpdb->prepare(
        "SELECT page_path as path, COUNT(*) as views
         FROM {$table} WHERE created_at >= %s
         GROUP BY page_path ORDER BY views DESC LIMIT 10", $since
    ), ARRAY_A);

    $top_referrers = $wpdb->get_results($wpdb->prepare(
        "SELECT referrer_host as source, COUNT(*) as count
         FROM {$table} WHERE created_at >= %s AND referrer_host != ''
         GROUP BY referrer_host ORDER BY count DESC LIMIT 10", $since
    ), ARRAY_A);

    $top_countries_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT country,
                COUNT(DISTINCT ip_hash) as users,
                COUNT(*) as views
         FROM {$table} WHERE created_at >= %s AND country != ''
         GROUP BY country ORDER BY users DESC", $since
    ), ARRAY_A);

    // Count new users per country (first-time visitors in this period)
    $new_users_by_country = $wpdb->get_results($wpdb->prepare(
        "SELECT t1.country, COUNT(DISTINCT t1.ip_hash) as new_users
         FROM {$table} t1
         WHERE t1.created_at >= %s AND t1.country != ''
         AND NOT EXISTS (
             SELECT 1 FROM {$table} t2
             WHERE t2.ip_hash = t1.ip_hash AND t2.created_at < %s
         )
         GROUP BY t1.country", $since, $since
    ), ARRAY_A);

    $new_users_lookup = array();
    foreach ($new_users_by_country as $row) {
        $code = strtoupper($row['country']);
        if ($code === 'EN') $code = 'US';
        if ($code === 'ZH') $code = 'CN';
        if (!isset($new_users_lookup[$code])) {
            $new_users_lookup[$code] = 0;
        }
        $new_users_lookup[$code] += (int) $row['new_users'];
    }

    // Build enriched country data with full names and regions
    $region_map = varner_analytics_country_to_region();
    $country_names = varner_analytics_country_names();
    $total_views_all = 0;
    
    // Group and aggregate records to merge normalized codes and exclude raw language codes
    $normalized_countries = array();
    foreach ($top_countries_raw as $row) {
        $code = strtoupper($row['country']);
        if ($code === 'EN') $code = 'US';
        if ($code === 'ZH') $code = 'CN';

        // Skip any raw language codes or invalid country codes (such as EN, ZH, etc.)
        if (!isset($country_names[$code])) {
            continue;
        }

        if (!isset($normalized_countries[$code])) {
            $normalized_countries[$code] = array(
                'country'    => $code,
                'name'       => $country_names[$code],
                'region'     => $region_map[$code] ?? 'Other',
                'users'      => 0,
                'views'      => 0,
                'new_users'  => $new_users_lookup[$code] ?? 0,
            );
        }
        $normalized_countries[$code]['users'] += (int) $row['users'];
        $normalized_countries[$code]['views'] += (int) $row['views'];
        $total_views_all += (int) $row['views'];
    }

    $top_countries = array_values($normalized_countries);

    // Add percentage
    foreach ($top_countries as &$c) {
        $c['pct'] = $total_views_all > 0 ? round(($c['views'] / $total_views_all) * 100, 1) : 0;
    }
    unset($c);

    // Sort by users descending
    usort($top_countries, function ($a, $b) { return $b['users'] - $a['users']; });

    // Aggregate by region
    $region_agg = array();
    foreach ($top_countries as $c) {
        $r = $c['region'];
        if (!isset($region_agg[$r])) {
            $region_agg[$r] = array('region' => $r, 'users' => 0, 'views' => 0, 'new_users' => 0);
        }
        $region_agg[$r]['users']     += $c['users'];
        $region_agg[$r]['views']     += $c['views'];
        $region_agg[$r]['new_users'] += $c['new_users'];
    }
    // Add percentages and sort
    $top_regions = array_values($region_agg);
    foreach ($top_regions as &$r) {
        $r['pct'] = $total_views_all > 0 ? round(($r['views'] / $total_views_all) * 100, 1) : 0;
    }
    unset($r);
    usort($top_regions, function ($a, $b) { return $b['users'] - $a['users']; });

    $devices_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT ua_device, COUNT(DISTINCT ip_hash) as cnt
         FROM {$table} WHERE created_at >= %s
         GROUP BY ua_device", $since
    ), ARRAY_A);

    $devices = array('mobile' => 0, 'desktop' => 0, 'tablet' => 0);
    foreach ($devices_raw as $row) {
        if (isset($devices[$row['ua_device']])) {
            $devices[$row['ua_device']] = (int) $row['cnt'];
        }
    }

    $data = array(
        'range' => array(
            'start' => date('Y-m-d', strtotime("-{$days} days")),
            'end'   => date('Y-m-d'),
            'days'  => $days,
        ),
        'kpis' => array(
            'users'                  => $total_users,
            'new_users'              => $new_users,
            'avg_engagement_seconds' => 0,
        ),
        'timeseries'    => $timeseries,
        'realtime'      => array(
            'active_last_30min' => $active_last_30min,
            'per_minute'        => $per_minute,
        ),
        'top_pages'     => $top_pages,
        'top_referrers' => $top_referrers,
        'top_sources'   => array_map(function ($r) {
            return array('source' => $r['source'], 'new_users' => $r['count']);
        }, $top_referrers),
        'top_countries' => $top_countries,
        'top_regions'   => $top_regions,
        'devices'       => $devices,
    );

    set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
    return rest_ensure_response($data);
}

// ─── Country / Region Lookup Tables ───────────────────────────────────────────

function varner_analytics_country_to_region(): array {
    return array(
        // Northern America
        'US' => 'Northern America', 'CA' => 'Northern America', 'MX' => 'Northern America',
        'BM' => 'Northern America', 'GL' => 'Northern America', 'PM' => 'Northern America',
        // Central America
        'GT' => 'Central America', 'BZ' => 'Central America', 'HN' => 'Central America',
        'SV' => 'Central America', 'NI' => 'Central America', 'CR' => 'Central America', 'PA' => 'Central America',
        // Caribbean
        'CU' => 'Caribbean', 'JM' => 'Caribbean', 'HT' => 'Caribbean', 'DO' => 'Caribbean',
        'PR' => 'Caribbean', 'TT' => 'Caribbean', 'BS' => 'Caribbean', 'BB' => 'Caribbean',
        // South America
        'BR' => 'South America', 'AR' => 'South America', 'CO' => 'South America',
        'CL' => 'South America', 'PE' => 'South America', 'VE' => 'South America',
        'EC' => 'South America', 'BO' => 'South America', 'PY' => 'South America',
        'UY' => 'South America', 'GY' => 'South America', 'SR' => 'South America',
        // Northern Europe
        'GB' => 'Northern Europe', 'IE' => 'Northern Europe', 'SE' => 'Northern Europe',
        'NO' => 'Northern Europe', 'DK' => 'Northern Europe', 'FI' => 'Northern Europe',
        'IS' => 'Northern Europe', 'LT' => 'Northern Europe', 'LV' => 'Northern Europe',
        'EE' => 'Northern Europe',
        // Western Europe
        'FR' => 'Western Europe', 'DE' => 'Western Europe', 'NL' => 'Western Europe',
        'BE' => 'Western Europe', 'CH' => 'Western Europe', 'AT' => 'Western Europe',
        'LU' => 'Western Europe', 'LI' => 'Western Europe', 'MC' => 'Western Europe',
        // Southern Europe
        'ES' => 'Southern Europe', 'IT' => 'Southern Europe', 'PT' => 'Southern Europe',
        'GR' => 'Southern Europe', 'HR' => 'Southern Europe', 'RS' => 'Southern Europe',
        'SI' => 'Southern Europe', 'BA' => 'Southern Europe', 'ME' => 'Southern Europe',
        'MK' => 'Southern Europe', 'AL' => 'Southern Europe', 'MT' => 'Southern Europe',
        // Eastern Europe
        'RU' => 'Eastern Europe', 'PL' => 'Eastern Europe', 'UA' => 'Eastern Europe',
        'CZ' => 'Eastern Europe', 'RO' => 'Eastern Europe', 'HU' => 'Eastern Europe',
        'SK' => 'Eastern Europe', 'BG' => 'Eastern Europe', 'BY' => 'Eastern Europe',
        'MD' => 'Eastern Europe',
        // Eastern Asia
        'CN' => 'Eastern Asia', 'JP' => 'Eastern Asia', 'KR' => 'Eastern Asia',
        'TW' => 'Eastern Asia', 'HK' => 'Eastern Asia', 'MO' => 'Eastern Asia', 'MN' => 'Eastern Asia',
        // South-eastern Asia
        'ID' => 'South-eastern Asia', 'PH' => 'South-eastern Asia', 'VN' => 'South-eastern Asia',
        'TH' => 'South-eastern Asia', 'MY' => 'South-eastern Asia', 'SG' => 'South-eastern Asia',
        'MM' => 'South-eastern Asia', 'KH' => 'South-eastern Asia', 'LA' => 'South-eastern Asia',
        'BN' => 'South-eastern Asia', 'TL' => 'South-eastern Asia',
        // Southern Asia
        'IN' => 'Southern Asia', 'PK' => 'Southern Asia', 'BD' => 'Southern Asia',
        'LK' => 'Southern Asia', 'NP' => 'Southern Asia', 'AF' => 'Southern Asia',
        'MV' => 'Southern Asia', 'BT' => 'Southern Asia',
        // Western Asia
        'TR' => 'Western Asia', 'SA' => 'Western Asia', 'AE' => 'Western Asia',
        'IL' => 'Western Asia', 'IQ' => 'Western Asia', 'IR' => 'Western Asia',
        'JO' => 'Western Asia', 'LB' => 'Western Asia', 'KW' => 'Western Asia',
        'QA' => 'Western Asia', 'BH' => 'Western Asia', 'OM' => 'Western Asia',
        'YE' => 'Western Asia', 'SY' => 'Western Asia', 'PS' => 'Western Asia',
        'GE' => 'Western Asia', 'AM' => 'Western Asia', 'AZ' => 'Western Asia', 'CY' => 'Western Asia',
        // Central Asia
        'KZ' => 'Central Asia', 'UZ' => 'Central Asia', 'TM' => 'Central Asia',
        'KG' => 'Central Asia', 'TJ' => 'Central Asia',
        // Northern Africa
        'EG' => 'Northern Africa', 'DZ' => 'Northern Africa', 'MA' => 'Northern Africa',
        'TN' => 'Northern Africa', 'LY' => 'Northern Africa', 'SD' => 'Northern Africa',
        // Sub-Saharan Africa
        'NG' => 'Sub-Saharan Africa', 'ZA' => 'Sub-Saharan Africa', 'KE' => 'Sub-Saharan Africa',
        'ET' => 'Sub-Saharan Africa', 'GH' => 'Sub-Saharan Africa', 'TZ' => 'Sub-Saharan Africa',
        'UG' => 'Sub-Saharan Africa', 'CM' => 'Sub-Saharan Africa', 'CI' => 'Sub-Saharan Africa',
        'SN' => 'Sub-Saharan Africa', 'ZW' => 'Sub-Saharan Africa', 'AO' => 'Sub-Saharan Africa',
        'MZ' => 'Sub-Saharan Africa', 'MG' => 'Sub-Saharan Africa', 'CD' => 'Sub-Saharan Africa',
        'ML' => 'Sub-Saharan Africa', 'BF' => 'Sub-Saharan Africa', 'NE' => 'Sub-Saharan Africa',
        'RW' => 'Sub-Saharan Africa', 'MW' => 'Sub-Saharan Africa', 'ZM' => 'Sub-Saharan Africa',
        'NA' => 'Sub-Saharan Africa', 'BW' => 'Sub-Saharan Africa', 'MU' => 'Sub-Saharan Africa',
        // Oceania
        'AU' => 'Oceania', 'NZ' => 'Oceania', 'FJ' => 'Oceania', 'PG' => 'Oceania',
        'WS' => 'Oceania', 'TO' => 'Oceania', 'GU' => 'Oceania',
    );
}

function varner_analytics_country_names(): array {
    return array(
        'US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico',
        'GB' => 'United Kingdom', 'IE' => 'Ireland', 'FR' => 'France', 'DE' => 'Germany',
        'NL' => 'Netherlands', 'BE' => 'Belgium', 'CH' => 'Switzerland', 'AT' => 'Austria',
        'ES' => 'Spain', 'IT' => 'Italy', 'PT' => 'Portugal', 'GR' => 'Greece',
        'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland',
        'PL' => 'Poland', 'CZ' => 'Czech Republic', 'RO' => 'Romania', 'HU' => 'Hungary',
        'SK' => 'Slovakia', 'BG' => 'Bulgaria', 'HR' => 'Croatia', 'RS' => 'Serbia',
        'SI' => 'Slovenia', 'LT' => 'Lithuania', 'LV' => 'Latvia', 'EE' => 'Estonia',
        'UA' => 'Ukraine', 'BY' => 'Belarus', 'MD' => 'Moldova', 'RU' => 'Russia',
        'TR' => 'Turkey', 'IL' => 'Israel', 'SA' => 'Saudi Arabia', 'AE' => 'UAE',
        'QA' => 'Qatar', 'KW' => 'Kuwait', 'BH' => 'Bahrain', 'OM' => 'Oman',
        'JO' => 'Jordan', 'LB' => 'Lebanon', 'IQ' => 'Iraq', 'IR' => 'Iran',
        'SY' => 'Syria', 'YE' => 'Yemen', 'GE' => 'Georgia', 'AM' => 'Armenia',
        'AZ' => 'Azerbaijan', 'CY' => 'Cyprus', 'PS' => 'Palestine',
        'CN' => 'China', 'JP' => 'Japan', 'KR' => 'South Korea', 'TW' => 'Taiwan',
        'HK' => 'Hong Kong', 'MO' => 'Macao', 'MN' => 'Mongolia',
        'IN' => 'India', 'PK' => 'Pakistan', 'BD' => 'Bangladesh', 'LK' => 'Sri Lanka',
        'NP' => 'Nepal', 'AF' => 'Afghanistan', 'MV' => 'Maldives', 'BT' => 'Bhutan',
        'ID' => 'Indonesia', 'PH' => 'Philippines', 'VN' => 'Vietnam', 'TH' => 'Thailand',
        'MY' => 'Malaysia', 'SG' => 'Singapore', 'MM' => 'Myanmar', 'KH' => 'Cambodia',
        'LA' => 'Laos', 'BN' => 'Brunei', 'TL' => 'Timor-Leste',
        'KZ' => 'Kazakhstan', 'UZ' => 'Uzbekistan', 'TM' => 'Turkmenistan',
        'KG' => 'Kyrgyzstan', 'TJ' => 'Tajikistan',
        'AU' => 'Australia', 'NZ' => 'New Zealand', 'FJ' => 'Fiji', 'PG' => 'Papua New Guinea',
        'BR' => 'Brazil', 'AR' => 'Argentina', 'CO' => 'Colombia', 'CL' => 'Chile',
        'PE' => 'Peru', 'VE' => 'Venezuela', 'EC' => 'Ecuador', 'BO' => 'Bolivia',
        'PY' => 'Paraguay', 'UY' => 'Uruguay', 'GY' => 'Guyana', 'SR' => 'Suriname',
        'GT' => 'Guatemala', 'BZ' => 'Belize', 'HN' => 'Honduras', 'SV' => 'El Salvador',
        'NI' => 'Nicaragua', 'CR' => 'Costa Rica', 'PA' => 'Panama',
        'CU' => 'Cuba', 'JM' => 'Jamaica', 'HT' => 'Haiti', 'DO' => 'Dominican Republic',
        'PR' => 'Puerto Rico', 'TT' => 'Trinidad & Tobago', 'BS' => 'Bahamas', 'BB' => 'Barbados',
        'EG' => 'Egypt', 'DZ' => 'Algeria', 'MA' => 'Morocco', 'TN' => 'Tunisia',
        'LY' => 'Libya', 'SD' => 'Sudan',
        'NG' => 'Nigeria', 'ZA' => 'South Africa', 'KE' => 'Kenya', 'ET' => 'Ethiopia',
        'GH' => 'Ghana', 'TZ' => 'Tanzania', 'UG' => 'Uganda', 'CM' => 'Cameroon',
        'CI' => 'Ivory Coast', 'SN' => 'Senegal', 'ZW' => 'Zimbabwe', 'AO' => 'Angola',
        'MZ' => 'Mozambique', 'MG' => 'Madagascar', 'CD' => 'DR Congo',
        'ML' => 'Mali', 'BF' => 'Burkina Faso', 'NE' => 'Niger', 'RW' => 'Rwanda',
        'MW' => 'Malawi', 'ZM' => 'Zambia', 'NA' => 'Namibia', 'BW' => 'Botswana',
        'MU' => 'Mauritius', 'IS' => 'Iceland', 'LU' => 'Luxembourg', 'MT' => 'Malta',
        'AL' => 'Albania', 'BA' => 'Bosnia & Herzegovina', 'ME' => 'Montenegro',
        'MK' => 'North Macedonia', 'MC' => 'Monaco', 'LI' => 'Liechtenstein',
        'BM' => 'Bermuda', 'GL' => 'Greenland', 'GU' => 'Guam',
        'WS' => 'Samoa', 'TO' => 'Tonga', 'PM' => 'Saint Pierre & Miquelon',
    );
}
