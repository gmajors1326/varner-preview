<?php
defined('ABSPATH') || exit;

class Varner_Cookie_Registry {
    const CONSENT_VERSION = 2;

    public static function get_declared(): array {
        $cookies = array(
            'necessary' => array(
                'label'   => 'Necessary',
                'cookies' => array(
                    array(
                        'name'     => 'wordpress_*',
                        'service'  => 'WordPress',
                        'purpose'  => 'Session authentication for logged-in users and commenters.',
                        'duration' => 'Session / 14 days',
                    ),
                    array(
                        'name'     => 'wordpress_logged_in_*',
                        'service'  => 'WordPress',
                        'purpose'  => 'Keeps you authenticated after login.',
                        'duration' => 'Session / 14 days',
                    ),
                    array(
                        'name'     => 'wp-settings-{user}',
                        'service'  => 'WordPress',
                        'purpose'  => 'Stores admin UI preferences per user.',
                        'duration' => '1 year',
                    ),
                    array(
                        'name'     => 'wp-settings-time-{user}',
                        'service'  => 'WordPress',
                        'purpose'  => 'Timestamp for admin UI settings.',
                        'duration' => '1 year',
                    ),
                    array(
                        'name'     => 'varner_cookie_consent',
                        'service'  => 'Varner Equipment',
                        'purpose'  => 'Stores your cookie consent preferences (localStorage, not a cookie).',
                        'duration' => 'Persistent',
                    ),
                ),
            ),
            'analytics' => array(
                'label'   => 'Analytics',
                'cookies' => array(
                    array(
                        'name'     => 'varner_analytics_beacon',
                        'service'  => 'Varner Analytics (first-party)',
                        'purpose'  => 'Anonymous pageview counting for site improvement. No cookies set. IP is hashed with a daily-rotating salt.',
                        'duration' => 'N/A (no cookie set)',
                    ),
                ),
            ),
            'marketing' => array(
                'label'   => 'Marketing',
                'cookies' => array(
                    array(
                        'name'     => 'NID',
                        'service'  => 'Google Maps',
                        'purpose'  => 'Used by Google Maps embed for preferences and functionality.',
                        'duration' => '6 months',
                    ),
                    array(
                        'name'     => 'VISITOR_INFO1_LIVE',
                        'service'  => 'YouTube',
                        'purpose'  => 'Used by YouTube to track embed preferences and view history.',
                        'duration' => '6 months',
                    ),
                    array(
                        'name'     => 'CONSENT',
                        'service'  => 'YouTube / Google',
                        'purpose'  => 'Stores consent preferences for YouTube and Google services.',
                        'duration' => '2 years',
                    ),
                ),
            ),
        );

        return apply_filters( 'varner_cookie_registry', $cookies );
    }

    public static function get_version(): int {
        return self::CONSENT_VERSION;
    }

    public static function get_flat(): array {
        $flat = array();
        foreach ( self::get_declared() as $category => $group ) {
            foreach ( $group['cookies'] as $c ) {
                $c['category'] = $category;
                $flat[] = $c;
            }
        }
        return $flat;
    }
}

// ─── REST Endpoints ───────────────────────────────────────────────────────────

add_action( 'rest_api_init', function (): void {
    $ns = 'varner/v1';

    register_rest_route( $ns, '/cookies/declaration', array(
        'methods'             => 'GET',
        'callback'            => function (): WP_REST_Response {
            return rest_ensure_response( Varner_Cookie_Registry::get_declared() );
        },
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( $ns, '/cookies/consent-version', array(
        'methods'             => 'GET',
        'callback'            => function (): WP_REST_Response {
            return rest_ensure_response( array(
                'version' => Varner_Cookie_Registry::get_version(),
            ) );
        },
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( $ns, '/cookies/settings', array(
        array(
            'methods'             => 'GET',
            'callback'            => function (): WP_REST_Response {
                return rest_ensure_response( array(
                    'banner_enabled' => (bool) get_option( 'varner_cookie_banner_enabled', true ),
                    'consent_version' => Varner_Cookie_Registry::get_version(),
                ) );
            },
            'permission_callback' => function (): bool { return current_user_can( 'manage_options' ); },
        ),
        array(
            'methods'             => 'POST',
            'callback'            => function ( WP_REST_Request $request ): WP_REST_Response|WP_Error {
                $banner = $request->get_param( 'banner_enabled' );
                if ( is_bool( $banner ) ) {
                    update_option( 'varner_cookie_banner_enabled', $banner );
                }
                $bump = $request->get_param( 'bump_version' );
                if ( $bump === true ) {
                    $current = Varner_Cookie_Registry::get_version();
                    update_option( 'varner_cookie_consent_version_override', $current + 1 );
                }
                return rest_ensure_response( array( 'success' => true ) );
            },
            'permission_callback' => function (): bool { return current_user_can( 'manage_options' ); },
        ),
    ) );
} );

// ─── Admin Page ───────────────────────────────────────────────────────────────

function varner_render_cookie_manager_page(): void {
    $registry = Varner_Cookie_Registry::get_declared();
    $version  = (int) get_option( 'varner_cookie_consent_version_override', Varner_Cookie_Registry::CONSENT_VERSION );
    $enabled  = (bool) get_option( 'varner_cookie_banner_enabled', true );
    ?>
    <div class="wrap" style="max-width:960px;padding:24px 0;">
        <h1 style="margin-bottom:16px;">Cookie Manager</h1>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:32px;">
            <?php wp_nonce_field( 'varner_cookie_settings', 'varner_cookie_nonce' ); ?>
            <input type="hidden" name="action" value="varner_cookie_save_settings">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Banner Enabled</th>
                    <td>
                        <label>
                            <input type="checkbox" name="banner_enabled" value="1" <?php checked( $enabled ); ?>>
                            Show cookie consent banner on front-end
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Consent Version</th>
                    <td>
                        <code><?php echo intval( $version ); ?></code>
                        <p class="description" style="margin-top:4px;">
                            Bump this version to force all users to re-consent after changing the cookie registry.
                        </p>
                        <button type="submit" name="bump_version" value="1" class="button button-secondary" style="margin-top:8px;" onclick="return confirm('This will invalidate all existing cookie consents. Continue?');">
                            Bump Version &amp; Re-Consent All Users
                        </button>
                    </td>
                </tr>
            </table>
        </form>

        <h2 style="margin-bottom:12px;">Declared Cookies</h2>
        <?php foreach ( $registry as $category => $group ): ?>
            <h3 style="margin:20px 0 8px;"><?php echo esc_html( $group['label'] ); ?></h3>
            <table class="widefat fixed striped" style="max-width:100%;">
                <thead>
                    <tr>
                        <th style="width:25%;">Cookie</th>
                        <th style="width:20%;">Service</th>
                        <th style="width:40%;">Purpose</th>
                        <th style="width:15%;">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $group['cookies'] as $c ): ?>
                        <tr>
                            <td><code><?php echo esc_html( $c['name'] ); ?></code></td>
                            <td><?php echo esc_html( $c['service'] ); ?></td>
                            <td><?php echo esc_html( $c['purpose'] ); ?></td>
                            <td><?php echo esc_html( $c['duration'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <p style="margin-top:24px;color:#64748b;font-size:12px;">
            Cookie registry is hardcoded. Use the <code>varner_cookie_registry</code> filter to extend from a plugin or theme.
            REST: <code>/wp-json/varner/v1/cookies/declaration</code> (public) &middot;
            <code>/wp-json/varner/v1/cookies/consent-version</code> (public)
        </p>
    </div>
    <?php
}

// ─── Admin POST Handler ───────────────────────────────────────────────────────

add_action( 'admin_post_varner_cookie_save_settings', function (): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized.' );
    }
    check_admin_referer( 'varner_cookie_settings', 'varner_cookie_nonce' );

    $banner = ! empty( $_POST['banner_enabled'] );
    update_option( 'varner_cookie_banner_enabled', $banner );

    if ( ! empty( $_POST['bump_version'] ) ) {
        $current = (int) get_option( 'varner_cookie_consent_version_override', Varner_Cookie_Registry::CONSENT_VERSION );
        update_option( 'varner_cookie_consent_version_override', $current + 1 );
    }

    wp_safe_redirect( add_query_arg( 'updated', '1', wp_get_referer() ?: admin_url( 'admin.php?page=varner-os-cookies' ) ) );
    exit;
} );

// ─── Consent Version Helper ───────────────────────────────────────────────────

function varner_cookie_get_effective_version(): int {
    return (int) get_option( 'varner_cookie_consent_version_override', Varner_Cookie_Registry::CONSENT_VERSION );
}

// ─── Gating JS (output in footer) ─────────────────────────────────────────────

add_action( 'wp_footer', function (): void {
    if ( ! (bool) get_option( 'varner_cookie_banner_enabled', true ) ) {
        return;
    }

    $version = varner_cookie_get_effective_version();
    ?>
<script>
(function() {
    var KEY = 'varner_cookie_consent';
    var VERSION = <?php echo json_encode( $version ); ?>;

    var raw;
    try { raw = JSON.parse(localStorage.getItem(KEY)); } catch(e) {}
    var consent = raw && raw.version === VERSION && raw.consent ? raw : null;

    function readConsent() {
        var r;
        try { r = JSON.parse(localStorage.getItem(KEY)); } catch(e) {}
        return r && r.version === VERSION && r.consent ? r : null;
    }

    consent = readConsent();

    window.VarnerCookies = {
        getConsent: function() { var c = readConsent(); return c ? { necessary: true, analytics: !!c.analytics, marketing: !!c.marketing } : null; },
        hasConsented: function() { return readConsent() !== null; },
        waitForConsent: function(timeout) {
            timeout = timeout || 30000;
            var c = readConsent();
            if (c) return Promise.resolve(c);
            return new Promise(function(resolve) {
                var timer = setTimeout(function() {
                    document.removeEventListener('varner-consent-updated', handler);
                    resolve({ necessary: true, analytics: false, marketing: false });
                }, timeout);
                function handler(e) { clearTimeout(timer); document.removeEventListener('varner-consent-updated', handler); resolve(e.detail); }
                document.addEventListener('varner-consent-updated', handler);
            });
        }
    };

    // Gate embeds: replace data-cookie-src with src once marketing consent is given
    function gateEmbeds() {
        var els = document.querySelectorAll('[data-cookie-src]');
        if (!els.length) return;
        var c = readConsent();
        if (c && c.marketing) {
            els.forEach(function(el) {
                el.src = el.getAttribute('data-cookie-src');
                el.removeAttribute('data-cookie-src');
            });
        } else if (!c) {
            window.VarnerCookies.waitForConsent().then(function(c) {
                if (c.marketing) {
                    document.querySelectorAll('[data-cookie-src]').forEach(function(el) {
                        el.src = el.getAttribute('data-cookie-src');
                        el.removeAttribute('data-cookie-src');
                    });
                }
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', gateEmbeds);
    } else {
        gateEmbeds();
    }

    // Re-gate on consent change (banner interaction)
    document.addEventListener('varner-consent-updated', function(e) {
        consent = e.detail;
        gateEmbeds();
    });
})();
</script>
    <?php
} );

// ─── Cookie Declaration Shortcode ─────────────────────────────────────────────

add_shortcode( 'varner_cookie_declaration', function (): string {
    $registry = Varner_Cookie_Registry::get_declared();
    $version  = varner_cookie_get_effective_version();
    $updated = get_option( 'varner_cookie_consent_version_override' )
        ? sprintf( 'Consent version %d', $version )
        : 'Initial';

    ob_start();
    ?>
    <div class="varner-cookie-declaration">
        <p style="margin-bottom:16px;font-size:14px;color:#475569;">
            This page lists the cookies and similar technologies used on this website.
            Last updated: <?php echo esc_html( $updated ); ?>
        </p>
        <?php foreach ( $registry as $category => $group ): ?>
            <h3 style="margin:24px 0 8px;font-size:16px;font-weight:700;"><?php echo esc_html( $group['label'] ); ?></h3>
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:13px;">
                <thead>
                    <tr style="background:#f1f5f9;text-align:left;">
                        <th style="padding:8px 12px;border:1px solid #e2e8f0;">Cookie</th>
                        <th style="padding:8px 12px;border:1px solid #e2e8f0;">Service</th>
                        <th style="padding:8px 12px;border:1px solid #e2e8f0;">Purpose</th>
                        <th style="padding:8px 12px;border:1px solid #e2e8f0;">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $group['cookies'] as $c ): ?>
                        <tr>
                            <td style="padding:6px 12px;border:1px solid #e2e8f0;"><code><?php echo esc_html( $c['name'] ); ?></code></td>
                            <td style="padding:6px 12px;border:1px solid #e2e8f0;"><?php echo esc_html( $c['service'] ); ?></td>
                            <td style="padding:6px 12px;border:1px solid #e2e8f0;"><?php echo esc_html( $c['purpose'] ); ?></td>
                            <td style="padding:6px 12px;border:1px solid #e2e8f0;"><?php echo esc_html( $c['duration'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
} );
