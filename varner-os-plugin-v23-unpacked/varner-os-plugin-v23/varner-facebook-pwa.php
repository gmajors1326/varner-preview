<?php
/**
 * Varner OS — Facebook Catalog CSV + PWA Mobile Companion Router
 *
 * Extracted from varner-os-plugin-v23.php. Loaded via require_once in that file.
 * No dependencies on varner-backend.php — uses get_post_meta/get_field directly.
 * Catalog functions are called by meta-sync hooks and direct invocation.
 */

defined('ABSPATH') || exit;

// ─── Facebook Catalog CSV Generator ──────────────────────────────────────────

function varner_os_get_facebook_catalog_csv(): string {
    $posts = get_posts(array(
        'post_type'              => 'equipment',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'cache_results'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'suppress_filters'       => false,
        'meta_query'             => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array('key' => 'show_on_website', 'value' => '1', 'compare' => '='),
                array('key' => 'show_on_website', 'compare' => 'NOT EXISTS'),
            ),
            array(
                'key' => 'facebook_sync', 'value' => '1', 'compare' => '=',
            ),
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

        // Use direct get_post_meta lookups to avoid ACF's heavy get_fields N+1 query pattern.
        $stock_status = get_post_meta($post_id, 'stock_status', true);
        if ($stock_status === 'Draft') continue;

        $title = $post->post_title;

        $desc_raw = get_post_meta($post_id, 'description', true);
        $desc = wp_strip_all_tags($desc_raw);
        $desc = str_replace(array("\r", "\n", "\t"), ' ', $desc);
        $desc = preg_replace('/\s+/', ' ', $desc);
        $desc = trim($desc);
        if (empty($desc)) $desc = $title;
        if (strlen($desc) > 4900) $desc = mb_strimwidth($desc, 0, 4900, '...');

        $url = get_permalink($post_id);

        $image_0_url = '';
        $additional_images = array();
        
        $gallery = get_post_meta($post_id, 'gallery', true);
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

        $make = get_post_meta($post_id, 'make', true);
        if (empty($make)) $make = 'Varner Equipment';

        $model = get_post_meta($post_id, 'model', true);
        if (empty($model)) $model = 'Equipment';

        $year = get_post_meta($post_id, 'year', true);
        if (empty($year)) $year = get_the_date('Y', $post_id) ?: '2026';

        $cond_raw  = get_post_meta($post_id, 'condition', true);
        $condition = strtolower($cond_raw) === 'used' ? 'used' : 'new';
        $availability = ($stock_status === 'In Stock') ? 'in stock' : 'out of stock';

        $price_val     = get_post_meta($post_id, 'price', true);
        $call_for_price = (bool) get_post_meta($post_id, 'call_for_price', true);
        $price = ($call_for_price || empty($price_val) || floatval($price_val) <= 0) ? '0 USD' : floatval($price_val) . ' USD';

        $category     = get_post_meta($post_id, 'category', true);
        $stock_number = get_post_meta($post_id, 'stock_number', true);

        $row = array(
            $post_id, $title, $desc, $url, $image_0_url,
            implode(',', $additional_images),
            $availability, $condition, $price, $make,
            $category, $stock_number, $year, $make, $model,
        );

        // Neutralize CSV formula injection (= + - @)
        $row = array_map(function ($val) {
            $val = (string) $val;
            if (strlen($val) > 0 && in_array($val[0], array('=', '+', '-', '@'), true)) {
                return "'" . $val;
            }
            return $val;
        }, $row);

        fputcsv($out, $row);
    }

    rewind($out);
    $csv_data = stream_get_contents($out);
    fclose($out);

    return $csv_data;
}

function varner_os_schedule_catalog_regeneration(bool $force = false): void {
    if ($force) {
        delete_option('varner_catalog_regen_lock_expiry');
        delete_option('varner_catalog_dirty');
    }

    $lock_expiry = (int)get_option('varner_catalog_regen_lock_expiry', 0);
    $is_locked   = (time() < $lock_expiry);

    // Regenerate immediately synchronously to ensure live sync updates instantly (unless importing in bulk and not forced)
    if ($force || !defined('WP_IMPORTING') || !WP_IMPORTING) {
        if (!$force && $is_locked) {
            // Set dirty flag so we sweep updates at the end of the burst (non-autoloaded)
            update_option('varner_catalog_dirty', 1, false);

            // Rate-limited synchronously; schedule a background job as a fallback
            if (!wp_next_scheduled('varner_cron_regenerate_catalog')) {
                wp_schedule_single_event(time() + 60, 'varner_cron_regenerate_catalog');
            }
            return;
        }

        // Set a 10-second cooldown lock to debounce rapid subsequent edits (bulk loops) (non-autoloaded)
        if (!$force) {
            update_option('varner_catalog_regen_lock_expiry', time() + 10, false);
        }
        delete_option('varner_catalog_dirty');
        varner_os_write_facebook_catalog_file();
    } else {
        // We are importing in bulk and not forced; flag catalog as dirty for sweeping (non-autoloaded)
        update_option('varner_catalog_dirty', 1, false);
    }

    // Schedule single event in the background as a fallback/cooldown mechanism
    if (!wp_next_scheduled('varner_cron_regenerate_catalog')) {
        wp_schedule_single_event(time() + 60, 'varner_cron_regenerate_catalog');
    }
}

// Sweep dirty catalog on next request once lock expires
add_action('init', 'varner_os_maybe_cleanup_dirty_catalog');
function varner_os_maybe_cleanup_dirty_catalog(): void {
    // Skip catalog sweep on REST requests — it adds 2 DB reads per call and
    // the catalog only needs to be fresh for Meta crawlers (handled via template_redirect).
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $is_dirty    = (int)get_option('varner_catalog_dirty', 0) === 1;
    $lock_expiry = (int)get_option('varner_catalog_regen_lock_expiry', 0);
    $is_locked   = (time() < $lock_expiry);

    // Gate 1: Check dirty flag and lock state
    if (!$is_dirty || $is_locked) {
        return;
    }

    // Gate 2: Skip if requesting the catalog file directly to avoid serving/rebuild race
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, 'facebook-catalog.csv') !== false) {
        return;
    }

    // Thundering Herd Guard: Set lock immediately before we start compiling (non-autoloaded)
    update_option('varner_catalog_regen_lock_expiry', time() + 10, false);

    // Ordering Caution: Clear dirty flag BEFORE write to capture any writes happening mid-execution
    delete_option('varner_catalog_dirty');

    varner_os_write_facebook_catalog_file();
}

add_action('varner_cron_regenerate_catalog', 'varner_os_cron_regenerate_catalog');
function varner_os_cron_regenerate_catalog(): void {
    delete_option('varner_catalog_dirty');
    varner_os_write_facebook_catalog_file();
}

function varner_os_write_facebook_catalog_file(): bool {
    $csv_data = varner_os_get_facebook_catalog_csv();
    $upload_dir = wp_upload_dir();
    $file_path = trailingslashit($upload_dir['basedir']) . 'facebook-catalog.csv';
    $written = file_put_contents($file_path, $csv_data);
    if ($written === false) {
        varner_os_log_meta_sync("ERROR: Failed to write catalog CSV to {$file_path}", 'warning');
    }
    
    // Clear the meta sync health cache transient so it recalculates next time
    delete_transient('varner_meta_sync_health');
    
    return $written !== false;
}

function varner_os_generate_facebook_catalog(): void {
    status_header(200);
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    $csv_data = varner_os_get_facebook_catalog_csv();
    
    // Attempt to write/refresh the static file for Nginx direct serving
    $upload_dir = wp_upload_dir();
    $file_path = trailingslashit($upload_dir['basedir']) . 'facebook-catalog.csv';
    $written = file_put_contents($file_path, $csv_data);
    if ($written === false) {
        varner_os_log_meta_sync("ERROR: Failed to write catalog CSV to {$file_path}", 'warning');
    }

    // Log Meta sync crawl event
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $is_facebook = (strpos(strtolower($ua), 'facebook') !== false || strpos(strtolower($ua), 'facebot') !== false);
    $trigger = $is_facebook ? 'Meta Crawler' : 'Manual Request';
    
    // Count the number of lines (approximate by newlines, minus header)
    $lines = substr_count($csv_data, "\n");
    $count = $lines > 0 ? $lines - 1 : 0;
    
    if ($is_facebook) {
        varner_os_log_meta_sync("API Handshake: Success (Meta crawler synced {$count} items)");
    } else {
        varner_os_log_meta_sync("Inventory Update checked (Manual pull: {$count} items synced)");
    }

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
                array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-192.png',         'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-512.png',         'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-192-maskable.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'),
                array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-512-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
            ),
            'shortcuts' => array(
                array(
                    'name'       => 'New Listing',
                    'short_name' => 'Add Unit',
                    'url'        => home_url('/mobile-app/?action=new'),
                    'icons'      => array(array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png')),
                ),
                array(
                    'name'       => 'View Stock',
                    'short_name' => 'Stock',
                    'url'        => home_url('/mobile-app/?action=list'),
                    'icons'      => array(array('src' => plugin_dir_url(__FILE__) . 'assets/icons/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png')),
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
                return Promise.allSettled(PRE_CACHE.map(u => cache.add(u)));
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
                    const urlObj = new URL(event.request.url);
                    if (!urlObj.pathname.includes('/mobile-app')) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
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
        // If they have a handoff nonce or token, let them access the PWA directly so
        // the client-side React app can verify the token and authenticate silently.
        // Otherwise, if they are not logged into WordPress, redirect to the login screen.
        $has_auth_param = false;
        if (!empty($_GET['handoff'])) {
            $handoff_nonce = sanitize_text_field(wp_unslash($_GET['handoff']));
            if (strlen($handoff_nonce) === 32 && ctype_xdigit($handoff_nonce)) {
                $has_auth_param = (bool) get_transient('varner_handoff_' . $handoff_nonce);
            }
        }

        if (!$has_auth_param) {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/mobile-app/')));
                exit;
            }
            if (!current_user_can('edit_posts')) {
                status_header(403);
                nocache_headers();
                echo '<!DOCTYPE html><meta charset="utf-8"><title>Not authorized</title>'
                   . '<div style="font-family:-apple-system,sans-serif;max-width:30rem;margin:20vh auto;padding:0 1.5rem;text-align:center;color:#0f172a">'
                   . '<h1 style="font-size:1.25rem">Not authorized</h1>'
                   . '<p>This account can\'t use the Varner OS app. Ask an admin to set your role to <strong>Editor</strong>.</p>'
                   . '<p><a href="' . esc_url(wp_logout_url(home_url('/mobile-app/'))) . '">Sign out</a></p></div>';
                exit;
            }
        }

        nocache_headers();
        header('Cache-Control: private, no-store');
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
    <?php
    $pwa_icons_base = plugin_dir_url(__FILE__) . 'assets/icons/';
    $apple_icon     = esc_url($pwa_icons_base . 'apple-touch-icon-180.png');
    ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $apple_icon; ?>">
    <link rel="apple-touch-startup-image" href="<?php echo $apple_icon; ?>">

    <style>
        html, body { margin:0; padding:0; width:100%; height:100%; background-color:#0a0a0b; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        body { padding: 0; box-sizing:border-box; }
        #varner-inventory-app { width:100%; height:100%; overflow:hidden; }
    </style>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo esc_url_raw(home_url('/sw.js')); ?>', { scope: '/mobile-app/' })
                .then(reg => {
                    console.log('SW registered', reg.scope);
                    return navigator.serviceWorker.getRegistrations();
                })
                .then(regs => regs && regs.forEach(r => { if (!r.scope.endsWith('/mobile-app/')) r.unregister(); }))
                .catch(err => console.log('SW failed', err));
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
    if (strlen($handoff_nonce) === 32 && ctype_xdigit($handoff_nonce)) {
        $resolved = get_transient('varner_handoff_' . $handoff_nonce);
        if ($resolved) {
            $mobile_token_for_page = $resolved;
            delete_transient('varner_handoff_' . $handoff_nonce); // one-time use
        }
    }
}

// Priority 2: User is already logged into WordPress — skip the token gate entirely.
// Reuse an existing active valid token if possible, otherwise generate and embed one.
if (!$mobile_token_for_page && is_user_logged_in() && current_user_can('edit_posts')) {
    $wp_user_id = get_current_user_id();
    $active_key = 'varner_active_tokens_' . $wp_user_id;
    $active_tokens = get_transient($active_key) ?: array();
    
    $valid_token = '';
    if (is_array($active_tokens)) {
        // Search newest first for reuse
        foreach (array_reverse($active_tokens) as $t) {
            $data = get_transient('varner_mobile_token_' . $t);
            if ($data) {
                $valid_token = $t;
                break;
            }
        }
    }

    if ($valid_token) {
        $mobile_token_for_page = $valid_token;
    } else {
        $auto_token = strtoupper(bin2hex(random_bytes(16)));
        $token_data = array('user_id' => $wp_user_id, 'created_at' => time());
        set_transient('varner_mobile_token_' . $auto_token, $token_data, 1800);
        
        $active_tokens = is_array($active_tokens) ? $active_tokens : array();
        $active_tokens[] = $auto_token;
        if (count($active_tokens) > 3) {
            $oldest = array_shift($active_tokens);
            delete_transient('varner_mobile_token_' . $oldest);
        }
        set_transient($active_key, $active_tokens, 1800);
        
        $mobile_token_for_page = $auto_token;
    }
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
