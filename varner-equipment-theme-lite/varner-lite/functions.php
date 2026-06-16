<?php
/**
 * Theme Setup and Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ACF Fallback: Prevents site crashes if ACF plugin is deactivated.
 */
if ( ! function_exists( 'get_field' ) ) {
    function get_field( $selector, $post_id = false, $format_value = true ) { return null; }
}
if ( ! function_exists( 'the_field' ) ) {
    function the_field( $selector, $post_id = false, $format_value = true ) { echo ''; }
}
if ( ! function_exists( 'get_sub_field' ) ) {
    function get_sub_field( $selector, $format_value = true ) { return null; }
}
if ( ! function_exists( 'have_rows' ) ) {
    function have_rows( $selector, $post_id = false ) { return false; }
}

/**
 * Register ACF Options Page for Global Settings
 */
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title'    => 'Varner Site Settings',
        'menu_title'    => 'Site Settings',
        'menu_slug'     => 'varner-site-settings',
        'capability'    => 'manage_options',
        'redirect'      => false,
        'icon_url'      => 'dashicons-admin-generic',
    ));
}

/**
 * Enqueue scripts and styles.
 */
function varner_theme_scripts() {
	// Google Fonts - Inter
	wp_enqueue_style( 'varner-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap', array(), null );

	// Compiled Tailwind CSS (replaces CDN)
	wp_enqueue_style( 'varner-tailwind', get_template_directory_uri() . '/assets/css/tailwind.css', array(), filemtime( get_template_directory() . '/assets/css/tailwind.css' ) );

	// Main stylesheet
	wp_enqueue_style( 'varner-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version') );
}
add_action( 'wp_enqueue_scripts', 'varner_theme_scripts' );

/**
 * Theme Setup
 */
function varner_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	
	// Register navigation menus if needed later
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'varner' ),
		'footer'  => __( 'Footer Menu', 'varner' ),
	) );
}
add_action( 'after_setup_theme', 'varner_theme_setup' );

/**
 * Brand logo resolver: prefers media library then falls back to theme asset.
 */
function varner_get_brand_logo_url( $variant = 'red' ) {
	$variant = strtolower( $variant ) === 'white' ? 'white' : 'red';
	$preferred_titles = array(
		"VarnerEquipment_{$variant}",
		"VarnerEquipment {$variant}",
		"Varner Equipment {$variant}",
		"VarnerEquipment-{$variant}",
	);

	foreach ( $preferred_titles as $title ) {
		$attachment = get_page_by_title( $title, OBJECT, 'attachment' );
		if ( $attachment && ! empty( $attachment->ID ) ) {
			$url = wp_get_attachment_url( $attachment->ID );
			if ( $url ) {
				return $url;
			}
		}
	}

	$asset_path = get_template_directory() . "/assets/VarnerEquipment_{$variant}.png";
	if ( file_exists( $asset_path ) ) {
		return get_template_directory_uri() . "/assets/VarnerEquipment_{$variant}.png";
	}

	return '';
}

/**
 * Clean up WordPress Head for speed and security
 */
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
add_filter('the_generator', '__return_false');
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Resolve the client IP for rate limiting.
 *
 * REMOTE_ADDR is the only value the client cannot forge, so it's the default.
 * X-Forwarded-For is honored ONLY when the connection actually arrives from a
 * trusted proxy (e.g. Cloudflare). Otherwise the header is attacker-controlled
 * and would let a single client evade the limiter by rotating it each request.
 *
 * Populate $trusted_proxies with your real proxy/Cloudflare egress ranges, or
 * leave it empty if the site is served directly (REMOTE_ADDR only).
 */
function varner_get_client_ip() {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
        return '';
    }

    // CIDR ranges for proxies you control. Empty = trust nothing, use REMOTE_ADDR.
    // Cloudflare publishes its ranges at https://www.cloudflare.com/ips/
    $trusted_proxies = array(
        // '173.245.48.0/20',
        // '103.21.244.0/22',
        // ... add your proxy/CDN ranges here ...
    );

    $is_trusted = false;
    foreach ( $trusted_proxies as $cidr ) {
        if ( varner_ip_in_cidr( $remote, $cidr ) ) {
            $is_trusted = true;
            break;
        }
    }

    if ( ! $is_trusted ) {
        // Direct connection (or unknown proxy): only REMOTE_ADDR is trustworthy.
        return $remote;
    }

    // Behind a trusted proxy: take the right-most XFF entry, which the trusted
    // proxy appended. Walk right-to-left past any further trusted hops.
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ( $xff ) {
        $parts = array_map( 'trim', explode( ',', $xff ) );
        for ( $i = count( $parts ) - 1; $i >= 0; $i-- ) {
            $candidate = $parts[ $i ];
            if ( ! filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
                continue;
            }
            $candidate_is_proxy = false;
            foreach ( $trusted_proxies as $cidr ) {
                if ( varner_ip_in_cidr( $candidate, $cidr ) ) {
                    $candidate_is_proxy = true;
                    break;
                }
            }
            if ( ! $candidate_is_proxy ) {
                return $candidate; // first non-proxy hop = real client
            }
        }
    }

    return $remote;
}

/**
 * Check whether an IPv4/IPv6 address falls within a CIDR range.
 */
function varner_ip_in_cidr( $ip, $cidr ) {
    if ( strpos( $cidr, '/' ) === false ) {
        return $ip === $cidr;
    }
    list( $subnet, $bits ) = explode( '/', $cidr, 2 );
    $bits = (int) $bits;

    $ip_bin     = @inet_pton( $ip );
    $subnet_bin = @inet_pton( $subnet );
    if ( $ip_bin === false || $subnet_bin === false || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
        return false; // mismatched family or invalid input
    }

    $bytes = intdiv( $bits, 8 );
    $rem   = $bits % 8;

    if ( $bytes > 0 && strncmp( $ip_bin, $subnet_bin, $bytes ) !== 0 ) {
        return false;
    }
    if ( $rem > 0 ) {
        $mask = chr( 0xFF << ( 8 - $rem ) & 0xFF );
        if ( ( ord( $ip_bin[ $bytes ] ) & ord( $mask ) ) !== ( ord( $subnet_bin[ $bytes ] ) & ord( $mask ) ) ) {
            return false;
        }
    }
    return true;
}

/**
 * Form Submission Helper: Verifies nonce and captcha, and applies IP-based rate limiting
 */
function varner_generate_stateless_captcha() {
    $num1 = rand(10, 99);
    $num2 = rand(10, 99);
    $ans  = $num1 + $num2;
    $time = time();
    $key  = wp_salt('nonce');
    $hash = hash_hmac('sha256', "$ans|$time", $key);
    
    return array(
        'num1' => $num1,
        'num2' => $num2,
        'time' => $time,
        'hash' => $hash,
    );
}

/**
 * Form Submission Helper: Verifies nonce and captcha, and applies IP-based rate limiting
 */
function varner_verify_form_submission( $nonce_name, $action_name, $require_captcha = false ) {
    // Validate nonce FIRST — don't spend rate-limit budget on forged/expired requests.
    if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $action_name ) ) {
        wp_safe_redirect( wp_get_referer() ?: home_url() );
        exit;
    }

    $now = time();

    // Rate Limiting Check using client IP and WordPress transients
    $ip = varner_get_client_ip();
    if ( $ip !== '' ) {
        $transient_key = 'vne_rl_' . substr( md5( $ip ), 0, 20 );
        $rate_data = get_transient( $transient_key );

        if ( ! is_array( $rate_data ) ) {
            $rate_data = array(
                'last_time'  => 0,
                'count'      => 0,
                'first_time' => $now,
            );
        }

        // 1. Cooldown check (5 seconds between submissions)
        if ( $now - $rate_data['last_time'] < 5 ) {
            wp_die( '<h1>Too Many Requests</h1><p>Please wait a few seconds before submitting another form.</p><a href="javascript:history.back()">Go Back</a>', 'Too Many Requests', array( 'response' => 429 ) );
        }

        // 2. Hourly limit check (max 10 submissions per hour per IP)
        if ( $now - $rate_data['first_time'] > 3600 ) {
            $rate_data['first_time'] = $now;
            $rate_data['count']      = 0;
        }

        if ( $rate_data['count'] >= 10 ) {
            wp_die( '<h1>Rate Limit Exceeded</h1><p>You have reached the maximum number of submissions allowed per hour. Please try again later.</p><a href="javascript:history.back()">Go Back</a>', 'Rate Limit Exceeded', array( 'response' => 429 ) );
        }

        // Update rate limit data and set transient TTL to the remaining time in the hourly window
        $rate_data['last_time'] = $now;
        $rate_data['count']++;
        $ttl = max( 1, 3600 - ( $now - $rate_data['first_time'] ) );
        set_transient( $transient_key, $rate_data, $ttl );
    }

    if ( $require_captcha ) {
        $user_ans = isset( $_POST['captcha_answer'] ) ? intval( $_POST['captcha_answer'] ) : 0;
        $time     = isset( $_POST['captcha_time'] ) ? intval( $_POST['captcha_time'] ) : 0;
        $hash     = isset( $_POST['captcha_hash'] ) ? sanitize_text_field( $_POST['captcha_hash'] ) : '';

        // Check expiration: 1 hour (3600 seconds)
        if ( time() - $time > 3600 || time() - $time < -10 ) {
            wp_die( '<h1>Security Verification Failed</h1><p>Captcha expired. Please go back, reload the page, and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }

        $key = wp_salt('nonce');
        $expected_hash = hash_hmac('sha256', "$user_ans|$time", $key);

        if ( empty($hash) || ! hash_equals( $expected_hash, $hash ) ) {
            wp_die( '<h1>Security Verification Failed</h1><p>Incorrect sum. Please go back and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }
    }
}

/**
 * Chatbox form handler
 */
function varner_handle_chatbox_submit() {
    varner_verify_form_submission( 'varner_chatbox_nonce', 'varner_chatbox_submit', false );

    $dept    = sanitize_text_field( $_POST['department'] ?? '' );
    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $mobile  = sanitize_text_field( $_POST['mobile'] ?? '' );
    $msg     = sanitize_textarea_field( $_POST['message'] ?? '' );

    $recipient = varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
    $subject   = sanitize_text_field( "Chatbox Inquiry [{$dept}]: {$name}" );
    $body      = "Department: {$dept}\nName: {$name}\nMobile: {$mobile}\n\nMessage:\n{$msg}";
    wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( wp_get_referer() ?: home_url() );
    exit;
}

add_action( 'admin_post_nopriv_varner_chatbox_submit', 'varner_handle_chatbox_submit' );
add_action( 'admin_post_varner_chatbox_submit', 'varner_handle_chatbox_submit' );

/**
 * General Contact Form handler
 */
function varner_handle_contact_form_submit() {
    varner_verify_form_submission( 'varner_contact_nonce', 'varner_contact_form_submit', true );

    $name = sanitize_text_field( $_POST['full_name'] );
    $body = "CONTACT FORM SUBMISSION:\n\n"
          . "Name: $name\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n\n"
          . "Message:\n" . sanitize_textarea_field( $_POST['message'] ) . "\n";
    $recipient = varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
    wp_mail( $recipient, 'General Website Inquiry: ' . $name, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( esc_url_raw( add_query_arg( 'request', 'sent', wp_get_referer() ?: home_url() ) ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_contact_form_submit', 'varner_handle_contact_form_submit' );
add_action( 'admin_post_varner_contact_form_submit', 'varner_handle_contact_form_submit' );

/**
 * Parts Request form handler
 */
function varner_handle_parts_request_submit() {
    varner_verify_form_submission( 'varner_parts_nonce', 'varner_parts_request_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name']  ?? '' );
    $make  = sanitize_text_field( $_POST['make']        ?? '' );
    $model = sanitize_text_field( $_POST['model']       ?? '' );

    $body = "CUSTOMER INFORMATION:\n"
          . "Name: $fname $lname\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n"
          . "Address: " . sanitize_text_field( $_POST['address'] ) . ", " . sanitize_text_field( $_POST['city'] ) . ", " . sanitize_text_field( $_POST['state'] ) . " " . sanitize_text_field( $_POST['zip'] ) . "\n\n"
          . "EQUIPMENT DETAILS:\n"
          . "Make: $make\nModel: $model\nYear: " . sanitize_text_field( $_POST['year'] ) . "\nSerial: " . sanitize_text_field( $_POST['serial'] ) . "\nHours: " . sanitize_text_field( $_POST['hours'] ) . "\n\n"
          . "PARTS REQUESTED:\n"
          . "Preferred Date: " . sanitize_text_field( $_POST['appointment_date'] ) . "\nDescription: " . sanitize_textarea_field( $_POST['parts_needed'] ) . "\n\n"
          . "HISTORY:\n"
          . "Prior Customer: " . sanitize_text_field( $_POST['prior_service'] ) . "\nLast Date: " . sanitize_text_field( $_POST['last_service_date'] ) . "\nLast Work: " . sanitize_text_field( $_POST['last_service_work'] ) . "\n";
    $recipient = varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
    wp_mail( $recipient, "Parts Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_parts_request_submit', 'varner_handle_parts_request_submit' );
add_action( 'admin_post_varner_parts_request_submit', 'varner_handle_parts_request_submit' );

/**
 * Service Request form handler
 */
function varner_handle_service_request_submit() {
    varner_verify_form_submission( 'varner_service_nonce', 'varner_service_request_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name']  ?? '' );
    $make  = sanitize_text_field( $_POST['make']        ?? '' );
    $model = sanitize_text_field( $_POST['model']       ?? '' );

    $body = "CUSTOMER INFORMATION:\n"
          . "Name: $fname $lname\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n"
          . "Address: " . sanitize_text_field( $_POST['address'] ) . ", " . sanitize_text_field( $_POST['city'] ) . ", " . sanitize_text_field( $_POST['state'] ) . " " . sanitize_text_field( $_POST['zip'] ) . "\n\n"
          . "EQUIPMENT DETAILS:\n"
          . "Make: $make\nModel: $model\nYear: " . sanitize_text_field( $_POST['year'] ) . "\nSerial: " . sanitize_text_field( $_POST['serial'] ) . "\nHours: " . sanitize_text_field( $_POST['hours'] ) . "\n\n"
          . "SERVICE NEEDS:\n"
          . "Appointment Date: " . sanitize_text_field( $_POST['appointment_date'] ) . "\nDescription: " . sanitize_textarea_field( $_POST['services_needed'] ) . "\n\n"
          . "HISTORY:\n"
          . "Prior Customer: " . sanitize_text_field( $_POST['prior_service'] ) . "\nLast Date: " . sanitize_text_field( $_POST['last_service_date'] ) . "\nLast Work: " . sanitize_text_field( $_POST['last_service_work'] ) . "\n";
    $recipient = varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
    wp_mail( $recipient, "Service Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_service_request_submit', 'varner_handle_service_request_submit' );
add_action( 'admin_post_varner_service_request_submit', 'varner_handle_service_request_submit' );

/**
 * Employment Application form handler
 */
function varner_handle_employment_submit() {
    varner_verify_form_submission( 'varner_employment_nonce', 'varner_employment_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name'] ?? '' );
    $pos   = sanitize_text_field( $_POST['position'] ?? '' );

    $body = "JOB APPLICATION\n\n"
          . "Name:     $fname $lname\n"
          . "Email:    " . sanitize_email( $_POST['email'] ?? '' ) . "\n"
          . "Phone:    " . sanitize_text_field( $_POST['phone'] ?? '' ) . "\n"
          . "Position: $pos\n\n"
          . "Cover Letter / Experience:\n" . sanitize_textarea_field( $_POST['cover_letter'] ?? '' ) . "\n";

    $attachments = array();
    if ( ! empty( $_FILES['resume']['name'] ) ) {
        // Enforce upload errors check and size limit of 5 MB (5 * 1024 * 1024 bytes)
        if ( $_FILES['resume']['error'] !== UPLOAD_ERR_OK ) {
            wp_die( '<h1>Upload Error</h1><p>There was an error uploading your resume. Please go back and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }
        if ( $_FILES['resume']['size'] > 5 * 1024 * 1024 ) {
            wp_die( '<h1>File Too Large</h1><p>Your resume file is too large. Maximum size allowed is 5 MB.</p><a href="javascript:history.back()">Go Back</a>' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload( $_FILES['resume'], array(
            'test_form' => false,
            'mimes'     => array(
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ) );
        if ( isset( $uploaded['file'] ) ) {
            $attachments[] = $uploaded['file'];
        }
    }

    $recipient = varner_get_theme_setting( 'employment_email' ) ?: varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
    wp_mail( $recipient, "Job Application: $fname $lname — $pos", $body, array( 'Content-Type: text/plain; charset=UTF-8' ), $attachments );

    if ( ! empty( $attachments[0] ) && file_exists( $attachments[0] ) ) {
        wp_delete_file( $attachments[0] );
    }

    wp_safe_redirect( esc_url_raw( add_query_arg( 'application', 'sent', wp_get_referer() ?: home_url() ) ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_employment_submit', 'varner_handle_employment_submit' );
add_action( 'admin_post_varner_employment_submit', 'varner_handle_employment_submit' );

// ─── Force brand landing template for /brands/* pages ───────────────────────
// (Deprecated in favor of rewrite rule based include below)
/*
add_filter( 'template_include', function( $template ) {
...
}, 20 );
*/

/**
 * ── VARNER DIRECT URL ROUTER ──
 * Intercepts requests at parse_request (before DB queries) for instant loading.
 * Maps known URL paths directly to theme template files.
 */
add_action( 'parse_request', function( $wp ) {
    $path = trim( $wp->request, '/' );

    $route_map = array(
        'services'                 => 'page-service-request.php',
        'services/service-request' => 'page-service-request.php',
        'services/parts-request'   => 'page-parts-request.php',
        'dealer-info'              => 'page-about-us.php',
        'dealer-info/about-us'     => 'page-about-us.php',
        'dealer-info/our-team'     => 'page-our-team.php',
        'dealer-info/employment'   => 'page-employment.php',
        'videos'                   => 'page-videos.php',
        'product-videos'           => 'page-videos.php',
        'finance'                  => 'page-finance.php',
        'financing'                => 'page-finance.php',
        'contact'                  => 'page-contact.php',
        'brands'                   => 'page-brands.php',
    );

    if ( isset( $route_map[ $path ] ) ) {
        $tpl = get_template_directory() . '/' . $route_map[ $path ];
        if ( file_exists( $tpl ) ) {
            status_header( 200 );
            // Load header/footer context
            define( 'VARNER_VIRTUAL_PAGE', $path );
            include $tpl;
            exit;
        }
    }
}, 1 );

// ─── INVENTORY FILTER HELPERS ────────────────────────────────────────────────

/**
 * Returns distinct makes, categories, conditions (with counts), and
 * min/max year + price for all published equipment.
 */
function varner_get_filter_data( $segment_categories = array(), $active_categories = array() ) {
    global $wpdb;

    // Clause for segment categories (scopes categories, makes, conditions, years, prices)
    $segment_clause = '';
    if ( ! empty( $segment_categories ) ) {
        $escaped_cats = array_map( function($c) use ($wpdb) {
            return $wpdb->prepare('%s', $c);
        }, $segment_categories );
        $segment_clause = " AND p.ID IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = 'category' AND meta_value IN (" . implode( ',', $escaped_cats ) . ")
        )";
    }

    $base = "FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.post_type = 'equipment'
               AND p.post_status = 'publish'
               AND pm.meta_value != ''
               $segment_clause
               AND p.ID NOT IN (
                   SELECT post_id FROM {$wpdb->postmeta}
                   WHERE meta_key = 'show_on_website' AND meta_value = '0'
               )";

    $makes = $wpdb->get_results(
        "SELECT pm.meta_value AS val, COUNT(*) AS cnt $base AND pm.meta_key = 'make'
         GROUP BY pm.meta_value ORDER BY cnt DESC",
        OBJECT_K
    );

    $categories = $wpdb->get_results(
        "SELECT pm.meta_value AS val, COUNT(*) AS cnt $base AND pm.meta_key = 'category'
         GROUP BY pm.meta_value ORDER BY cnt DESC",
        OBJECT_K
    );

    $conditions = $wpdb->get_results(
        "SELECT pm.meta_value AS val, COUNT(*) AS cnt $base AND pm.meta_key = 'condition'
         GROUP BY pm.meta_value ORDER BY cnt DESC",
        OBJECT_K
    );

    $year_range = $wpdb->get_row(
        "SELECT MIN(CAST(pm.meta_value AS UNSIGNED)) AS min_year,
                MAX(CAST(pm.meta_value AS UNSIGNED)) AS max_year
         $base AND pm.meta_key = 'year'"
    );

    $price_range = $wpdb->get_row(
        "SELECT MIN(CAST(pm.meta_value AS DECIMAL(15,2))) AS min_price,
                MAX(CAST(pm.meta_value AS DECIMAL(15,2))) AS max_price
         $base AND pm.meta_key = 'price'"
    );

    // Subcategories should be filtered by the active checked categories if any,
    // or by the segment categories if none are checked.
    $sub_cat_filter = ! empty( $active_categories ) ? $active_categories : $segment_categories;
    $sub_cat_clause = '';
    if ( ! empty( $sub_cat_filter ) ) {
        $escaped_sub_cats = array_map( function($c) use ($wpdb) {
            return $wpdb->prepare('%s', $c);
        }, $sub_cat_filter );
        $sub_cat_clause = " AND p.ID IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = 'category' AND meta_value IN (" . implode( ',', $escaped_sub_cats ) . ")
        )";
    }

    $sub_base = "FROM {$wpdb->postmeta} pm
                 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE p.post_type = 'equipment'
                   AND p.post_status = 'publish'
                   AND pm.meta_value != ''
                   $segment_clause
                   $sub_cat_clause
                   AND p.ID NOT IN (
                       SELECT post_id FROM {$wpdb->postmeta}
                       WHERE meta_key = 'show_on_website' AND meta_value = '0'
                   )";

    $subcategories = array();
    if ( ! empty( $sub_cat_filter ) ) {
        $subcategories = $wpdb->get_results(
            "SELECT pm.meta_value AS val, COUNT(*) AS cnt $sub_base AND pm.meta_key = 'subcategory'
             GROUP BY pm.meta_value ORDER BY cnt DESC",
            OBJECT_K
        );
    }

    return compact( 'makes', 'categories', 'subcategories', 'conditions', 'year_range', 'price_range' );
}

/**
 * Dynamic Inventory Segments & SEO
 */
function varner_get_segment_seo($slug) {
    $segments = array(
        'all-units' => array('title' => 'All Inventory', 'h1' => 'Complete Collection', 'sub' => 'Western Colorado\'s selection.', 'filter' => array()),
        'new'       => array('title' => 'New Inventory', 'h1' => 'New Equipment', 'sub' => 'Latest machines.', 'filter' => array('condition' => array('New'))),
        'used'      => array('title' => 'Used Inventory', 'h1' => 'Proven Performance', 'sub' => 'Certified units.', 'filter' => array('condition' => array('Used'))),
        'tractors'  => array('title' => 'Tractors', 'h1' => 'Heavy-Duty Tractors', 'sub' => 'Backbone of any operation.', 'filter' => array('category' => array('Compact Tractors', 'Tractors', 'Utility Tractors'))),
        'trailers'  => array('title' => 'Trailers', 'h1' => 'Commercial Trailers', 'sub' => 'Haul with confidence.', 'filter' => array('category' => array('Commercial Trailers', 'Trailers', 'Dump Trailers', 'Flatbed Trailers', 'Utility Trailers'))),
        'attachments' => array('title' => 'Attachments', 'h1' => 'Attachments & Implements', 'sub' => 'Maximize versatility.', 'filter' => array('category' => array('Implements', 'Attachments', 'Loaders', 'Mowers'))),
        'hay-equipment' => array('title' => 'Hay Equipment', 'h1' => 'Hay & Harvest', 'sub' => 'Precision baling.', 'filter' => array('category' => array('Hay Equipment', 'Balers', 'Rakes'))),
        'misc'      => array('title' => 'Miscellaneous', 'h1' => 'Misc. Equipment', 'sub' => 'Tools & accessories.', 'filter' => array('category' => array('Misc', 'Other')))
    );
    return $segments[$slug] ?? null;
}

add_filter( 'query_vars', function( $vars ) { 
    $vars[] = 'inventory_segment'; 
    $vars[] = 'brand_name';
    return $vars; 
});

add_action( 'init', function() {
    add_rewrite_rule('^inventory/(all-units|new|used|tractors|trailers|attachments|hay-equipment|misc)/?$', 'index.php?inventory_segment=$matches[1]', 'top');
    add_rewrite_rule('^brands/([^/]+)/?$', 'index.php?brand_name=$matches[1]', 'top');
});

add_filter( 'template_include', function( $template ) {
    if ( get_query_var('inventory_segment') ) {
        $listing_template = locate_template('page-equipment-listing.php');
        if ( $listing_template ) return $listing_template;
    }
    if ( get_query_var('brand_name') ) {
        $brand_template = locate_template('page-brand.php');
        if ( $brand_template ) return $brand_template;
    }
    if ( is_page() ) {
        $about_template = locate_template( 'page-about-us.php' );
        if ( $about_template ) {
            $post_obj = get_post();
            $slug     = $post_obj ? $post_obj->post_name : '';
            $path     = $post_obj ? get_page_uri( $post_obj ) : '';
            if ( $slug === 'about-us' || $slug === 'about' || $path === 'dealer-info/about-us' || $path === 'dealer-info/about' ) {
                return $about_template;
            }
        }
    }
    return $template;
}, 30);

// Ensure About page uses the About template if present
function varner_ensure_about_page_template() {
    $candidates = array( 'dealer-info/about-us', 'about-us', 'dealer-info/about', 'about' );
    foreach ( $candidates as $candidate ) {
        $page = get_page_by_path( $candidate );
        if ( $page && ! is_wp_error( $page ) ) {
            update_post_meta( $page->ID, '_wp_page_template', 'page-about-us.php' );
            break;
        }
    }
}
add_action( 'init', 'varner_ensure_about_page_template' );

// Ensure Finance page exists and uses the Finance template
function varner_ensure_finance_page() {
    $page = get_page_by_path( 'finance' );
    if ( ! $page ) {
        $page_id = wp_insert_post( array(
            'post_title'   => 'Finance',
            'post_name'    => 'finance',
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => '',
        ) );
        if ( is_wp_error( $page_id ) ) { return; }
        $page = get_post( $page_id );
    }
    if ( $page && ! is_wp_error( $page ) ) {
        update_post_meta( $page->ID, '_wp_page_template', 'page-finance.php' );
    }

    // Auto-migrate stale /contact default value in options DB to /finance
    $settings = get_option( 'varner_theme_settings', array() );
    if ( is_array( $settings ) && isset( $settings['support_hub_finance_link'] ) && $settings['support_hub_finance_link'] === '/contact' ) {
        $settings['support_hub_finance_link'] = '/finance';
        update_option( 'varner_theme_settings', $settings );
    }
}
add_action( 'after_switch_theme', 'varner_ensure_finance_page' );
add_action( 'init', 'varner_ensure_finance_page' );

/**
 * Card Partial Loader
 */
function varner_include_equipment_card( $post_id = null ) {
    if ( ! function_exists('get_field') ) return;
    if ( ! $post_id ) $post_id = get_the_ID();
    $year            = get_field( 'year',           $post_id );
    $make            = get_field( 'make',           $post_id );
    $model           = get_field( 'model',          $post_id );
    $price           = get_field( 'price',          $post_id );
    $call_for_price  = get_field( 'call_for_price', $post_id );
    $category        = get_field( 'category',       $post_id );
    $condition       = get_field( 'condition',      $post_id );
    $stock_number    = get_field( 'stock_number',   $post_id );
    $length          = get_field( 'length',         $post_id );
    $formatted_price = $call_for_price ? 'Call For Price' : ( is_numeric( $price ) ? number_format( $price ) : (string) $price );
    $images          = varner_get_card_images( $post_id );
    include get_template_directory() . '/partials/equipment-card.php';
}

/**
 * Build the WP_Query args array for the inventory pages based on $_GET filters.
 * Accepts $base_meta (array) to pre-populate the meta_query (e.g. stock_status restriction).
 */
function varner_build_inventory_query( $base_meta = array(), $posts_per_page = -1 ) {
    $meta = array_merge( array( 'relation' => 'AND' ), $base_meta );

    $paged = max( 1, intval( get_query_var( 'paged' ) ?: ( get_query_var( 'page' ) ?: ( $_GET['paged'] ?? ( $_GET['page'] ?? 1 ) ) ) ) );

    $filters = array(
        'category'    => array_map( 'sanitize_text_field', (array) ( $_GET['category']    ?? [] ) ),
        'subcategory' => array_map( 'sanitize_text_field', (array) ( $_GET['subcategory'] ?? [] ) ),
        'make'        => array_map( 'sanitize_text_field', (array) ( $_GET['make']        ?? [] ) ),
        'condition'   => array_map( 'sanitize_text_field', (array) ( $_GET['condition']   ?? [] ) ),
    );

    foreach ( $filters as $key => $vals ) {
        if ( $vals ) {
            $meta[] = array( 'key' => $key, 'value' => $vals, 'compare' => 'IN' );
        }
    }

    $stock_number = sanitize_text_field( $_GET['stock_number'] ?? '' );
    if ( $stock_number !== '' ) {
        $meta[] = array( 'key' => 'stock_number', 'value' => $stock_number, 'compare' => 'LIKE' );
    }

    $vin = sanitize_text_field( $_GET['vin'] ?? '' );
    if ( $vin !== '' ) {
        $meta[] = array( 'key' => 'vin', 'value' => $vin, 'compare' => 'LIKE' );
    }

    $ranges = array(
        'year'  => array( 'min' => intval( $_GET['year_min'] ?? 0 ),  'max' => intval( $_GET['year_max'] ?? 0 ),  'type' => 'NUMERIC' ),
        'price' => array( 'min' => intval( $_GET['price_min'] ?? 0 ), 'max' => intval( $_GET['price_max'] ?? 0 ), 'type' => 'DECIMAL' ),
    );

    foreach ( $ranges as $key => $r ) {
        if ( $r['min'] && $r['max'] ) {
            $meta[] = array( 'key' => $key, 'value' => array( $r['min'], $r['max'] ), 'compare' => 'BETWEEN', 'type' => $r['type'] );
        } elseif ( $r['min'] ) {
            $meta[] = array( 'key' => $key, 'value' => $r['min'], 'compare' => '>=', 'type' => $r['type'] );
        } elseif ( $r['max'] ) {
            $meta[] = array( 'key' => $key, 'value' => $r['max'], 'compare' => '<=', 'type' => $r['type'] );
        }
    }

    $args = array(
        'post_type'      => 'equipment',
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'meta_query'     => $meta,
        'paged'          => $paged,
    );

    $keyword = sanitize_text_field( $_GET['s'] ?? '' );
    if ( $keyword ) { 
        $args['s'] = $keyword; 
        // Allow searching in meta fields via a hook
        add_filter( 'posts_search', 'varner_search_meta_fields', 10, 2 );
    }

    return $args;
}

/**
 * Extend WordPress search to include specific equipment meta fields.
 */
function varner_search_meta_fields( $search, $wp_query ) {
    global $wpdb;

    if ( empty( $search ) || ! $wp_query->is_main_query() && $wp_query->get( 'post_type' ) !== 'equipment' ) {
        return $search;
    }

    $q = $wp_query->query_vars;
    $n = ! empty( $q['exact'] ) ? '' : '%';
    $search = $search_and = '';

    foreach ( (array) $q['search_terms'] as $term ) {
        $term = esc_sql( $wpdb->esc_like( $term ) );
        $search .= "{$search_and}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR (EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key IN ('make','model','category','stock_number','vin') AND meta_value LIKE '{$n}{$term}{$n}')))";

        $search_and = ' AND ';
    }

    if ( ! empty( $search ) ) {
        $search = " AND ({$search}) ";
    }

    // Remove the filter so it doesn't affect other queries
    remove_filter( 'posts_search', 'varner_search_meta_fields', 10 );

    return $search;
}

/**
 * Returns a page URL with a single filter value removed.
 * For array filters (category[], make[], condition[]), removes just that one value.
 * For scalar filters, removes the key entirely.
 */
function varner_remove_filter( $key, $value = null ) {
    global $wp;
    // Sanitize all keys and values from $_GET before building the URL.
    $current = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
    unset( $current['paged'] );
    if ( $value === null ) {
        unset( $current[ $key ] );
    } else {
        $arr     = array_map( 'sanitize_text_field', (array) ( $current[ $key ] ?? [] ) );
        $arr     = array_values( array_filter( $arr, function ( $v ) use ( $value ) { return $v !== $value; } ) );
        if ( empty( $arr ) ) { unset( $current[ $key ] ); } else { $current[ $key ] = $arr; }
    }
    $base_url = is_singular() ? get_permalink() : home_url( add_query_arg( array(), $wp->request ) );
    return esc_url( $base_url . ( $current ? '?' . http_build_query( $current ) : '' ) );
}

/**
 * Returns a page URL with both range keys removed (e.g. year_min + year_max together).
 */
function varner_remove_range_filter( $key1, $key2 ) {
    global $wp;
    $current = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
    unset( $current[ $key1 ], $current[ $key2 ], $current['paged'] );
    return esc_url( ( is_singular() ? get_permalink() : home_url( add_query_arg( array(), $wp->request ) ) ) . ( $current ? '?' . http_build_query( $current ) : '' ) );
}

// ─── EQUIPMENT CARD HELPERS ──────────────────────────────────────────────────

/**
 * Build the images array for the equipment card partial.
 * Returns an array of full-size image URLs, falling back to post thumbnail,
 * then a placeholder.
 */
function varner_get_card_images( $post_id ) {
    $images  = array();
    $gallery = get_field( 'gallery', $post_id );
    if ( ! empty( $gallery ) ) {
        foreach ( $gallery as $img ) {
            if ( is_array( $img ) && ! empty( $img['url'] ) ) {
                $images[] = $img['url'];
            } elseif ( is_numeric( $img ) ) {
                $url = wp_get_attachment_url( $img );
                if ( $url ) { $images[] = $url; }
            }
        }
    }
    if ( empty( $images ) ) {
        $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( $thumb ) { $images[] = $thumb; }
    }
    if ( empty( $images ) ) {
        $images[] = 'https://images.unsplash.com/photo-1594913785162-e6785b423cb1?auto=format&fit=crop&q=80&w=800';
    }
    return $images;
}

// Carousel JS — outputs once in the footer on every front-end page.
add_action( 'wp_footer', function () {
    ?>
<script>
(function () {
    function initVarnerCarousels() {
        document.querySelectorAll('.vne-carousel-wrap').forEach(function (wrap) {
            var slides = wrap.querySelectorAll('.vne-slide');
            var dots   = wrap.querySelectorAll('.vne-dot');
            var prev   = wrap.querySelector('.vne-prev');
            var next   = wrap.querySelector('.vne-next');
            if (slides.length <= 1) return;
            var cur = 0;
            function go(i) {
                slides[cur].style.opacity = '0';
                slides[cur].style.zIndex  = '1';
                if (dots[cur]) dots[cur].style.opacity = '0.4';
                cur = ((i % slides.length) + slides.length) % slides.length;
                slides[cur].style.opacity = '1';
                slides[cur].style.zIndex  = '5';
                if (dots[cur]) dots[cur].style.opacity = '1';
            }
            dots.forEach(function (dot, i) {
                dot.addEventListener('click', function () { go(i); });
            });
            if (prev) prev.addEventListener('click', function (e) { e.preventDefault(); go(cur - 1); });
            if (next) next.addEventListener('click', function (e) { e.preventDefault(); go(cur + 1); });
            // Swipe support for mobile
            var startX = 0;
            wrap.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
            wrap.addEventListener('touchend',   function (e) {
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 40) go(dx < 0 ? cur + 1 : cur - 1);
            }, { passive: true });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVarnerCarousels);
    } else {
        initVarnerCarousels();
    }
})();
</script>
    <?php
} );



/**
 * Theme Setup
 */

/**
 * Fallback for theme settings defaults if the plugin is not active.
 */
if ( ! function_exists( 'varner_get_theme_settings_defaults' ) ) {
    function varner_get_theme_settings_defaults() {
        // Delegate to the plugin's full defaults if the plugin is active.
        if ( function_exists( 'varner_backend_get_settings_defaults' ) ) {
            return varner_backend_get_settings_defaults();
        }
        return array();
    }
}



/**
 * Retrieve a theme setting with visual-safe fallback.
 */
function varner_get_theme_setting($key, $default = null) {
    if (isset($_GET['varner_preview']) && current_user_can('edit_posts')) {
        $settings = get_option('varner_theme_settings_preview', array());
        if (empty($settings)) {
            $settings = get_option('varner_theme_settings', array());
        }
    } else {
        $settings = get_option('varner_theme_settings', array());
    }
    
    if (isset($settings[$key])) {
        return $settings[$key];
    }
    if ($default !== null) {
        return $default;
    }
    $defaults = varner_get_theme_settings_defaults();
    if (isset($defaults[$key])) {
        return $defaults[$key];
    }
    return '';
}

/**
 * Clear brand counts transient on equipment save, trash, untrash, or deletion.
 */
function varner_clear_brand_transient( $post_id ) {
    if ( get_post_type( $post_id ) === 'equipment' ) {
        delete_transient( 'varner_brand_counts' );
    }
}
add_action( 'save_post_equipment', 'varner_clear_brand_transient' );
add_action( 'deleted_post', 'varner_clear_brand_transient' );
add_action( 'trashed_post', 'varner_clear_brand_transient' );
add_action( 'untrashed_post', 'varner_clear_brand_transient' );

/**
 * Exclude hidden equipment from frontend loops.
 */
function varner_filter_equipment_visibility( $query ) {
    if ( is_admin() ) {
        return;
    }
    // Check if REST API and user has capability to edit posts (so the editor app can see everything)
    if ( defined('REST_REQUEST') && REST_REQUEST && current_user_can('edit_posts') ) {
        return;
    }

    $post_types = $query->get('post_type');
    if ( $post_types === 'equipment' || ( is_array( $post_types ) && in_array( 'equipment', $post_types ) ) ) {
        $meta_query = $query->get('meta_query');
        if ( ! is_array( $meta_query ) ) {
            $meta_query = array();
        }

        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => 'show_on_website',
                'value'   => '1',
                'compare' => '=',
            ),
            array(
                'key'     => 'show_on_website',
                'compare' => 'NOT EXISTS',
            ),
        );

        $query->set('meta_query', $meta_query);
    }
}
add_action( 'pre_get_posts', 'varner_filter_equipment_visibility' );

/**
 * Security Hardening
 */
// Disable XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// Disable Theme and Plugin Editor in WordPress Dashboard
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

