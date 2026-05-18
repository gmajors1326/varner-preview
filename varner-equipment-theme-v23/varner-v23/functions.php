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
        'capability'    => 'edit_posts',
        'redirect'      => false,
        'icon_url'      => 'dashicons-admin-generic',
    ));
}

/**
 * Enqueue scripts and styles.
 */
function varner_theme_scripts() {
	// Tailwind CSS via CDN (For Phase 1 rapid prototyping. Will compile locally in Phase 3)
	wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', array(), null, false );
	
	// Google Fonts - Inter
	wp_enqueue_style( 'varner-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap', array(), null );

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
 * Form Submission Helper: Verifies nonce and captcha
 */
function varner_verify_form_submission( $nonce_name, $action_name, $captcha_key = null ) {
    if ( ! session_id() ) {
        session_start();
    }

    if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $action_name ) ) {
        wp_safe_redirect( wp_get_referer() ?: home_url() );
        exit;
    }

    if ( $captcha_key ) {
        $user_ans = isset( $_POST['captcha_answer'] ) ? intval( $_POST['captcha_answer'] ) : 0;
        $real_ans = isset( $_SESSION[$captcha_key] ) ? intval( $_SESSION[$captcha_key] ) : -1;
        if ( $user_ans !== $real_ans ) {
            wp_die( '<h1>Security Verification Failed</h1><p>Incorrect sum. Please go back and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }
        unset( $_SESSION[$captcha_key] );
    }
}

/**
 * Chatbox form handler
 */
function varner_handle_chatbox_submit() {
    varner_verify_form_submission( 'varner_chatbox_nonce', 'varner_chatbox_submit' );

    $dept    = sanitize_text_field( $_POST['department'] ?? '' );
    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $mobile  = sanitize_text_field( $_POST['mobile'] ?? '' );
    $msg     = sanitize_textarea_field( $_POST['message'] ?? '' );

    $subject = 'Website Chatbox: ' . ( $dept ?: 'General' );
    $body    = "Department: {$dept}\nName: {$name}\nMobile: {$mobile}\n\nMessage:\n{$msg}";

    wp_mail( 'ashley@varnerequiment.com', $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( wp_get_referer() ?: home_url() );
    exit;
}

add_action( 'admin_post_nopriv_varner_chatbox_submit', 'varner_handle_chatbox_submit' );
add_action( 'admin_post_varner_chatbox_submit', 'varner_handle_chatbox_submit' );

/**
 * General Contact Form handler
 */
function varner_handle_contact_form_submit() {
    varner_verify_form_submission( 'varner_contact_nonce', 'varner_contact_form_submit', 'varner_contact_captcha' );

    $name = sanitize_text_field( $_POST['full_name'] );
    $body = "CONTACT FORM SUBMISSION:\n\n"
          . "Name: $name\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n\n"
          . "Message:\n" . sanitize_textarea_field( $_POST['message'] ) . "\n";

    wp_mail( 'ashley@varnerequiment.com', 'General Website Inquiry: ' . $name, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( add_query_arg( 'request', 'sent', wp_get_referer() ?: home_url() ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_contact_form_submit', 'varner_handle_contact_form_submit' );
add_action( 'admin_post_varner_contact_form_submit', 'varner_handle_contact_form_submit' );

/**
 * Parts Request form handler
 */
function varner_handle_parts_request_submit() {
    varner_verify_form_submission( 'varner_parts_nonce', 'varner_parts_request_submit', 'varner_parts_captcha' );

    $fname = sanitize_text_field( $_POST['first_name'] );
    $lname = sanitize_text_field( $_POST['last_name'] );
    $make  = sanitize_text_field( $_POST['make'] );
    $model = sanitize_text_field( $_POST['model'] );

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

    wp_mail( 'ashley@varnerequiment.com', "Parts Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_parts_request_submit', 'varner_handle_parts_request_submit' );
add_action( 'admin_post_varner_parts_request_submit', 'varner_handle_parts_request_submit' );

/**
 * Service Request form handler
 */
function varner_handle_service_request_submit() {
    varner_verify_form_submission( 'varner_service_nonce', 'varner_service_request_submit', 'varner_captcha' );

    $fname = sanitize_text_field( $_POST['first_name'] );
    $lname = sanitize_text_field( $_POST['last_name'] );
    $make  = sanitize_text_field( $_POST['make'] );
    $model = sanitize_text_field( $_POST['model'] );

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

    wp_mail( 'ashley@varnerequiment.com', "Service Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_service_request_submit', 'varner_handle_service_request_submit' );
add_action( 'admin_post_varner_service_request_submit', 'varner_handle_service_request_submit' );

/**
 * Employment Application form handler
 */
function varner_handle_employment_submit() {
    varner_verify_form_submission( 'varner_employment_nonce', 'varner_employment_submit', 'varner_employment_captcha' );

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
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload( $_FILES['resume'], array( 'test_form' => false ) );
        if ( isset( $uploaded['file'] ) ) {
            $attachments[] = $uploaded['file'];
        }
    }

    wp_mail( 'gmajors1326@gmail.com', "Job Application: $fname $lname — $pos", $body, array( 'Content-Type: text/plain; charset=UTF-8' ), $attachments );

    if ( ! empty( $attachments[0] ) && file_exists( $attachments[0] ) ) {
        wp_delete_file( $attachments[0] );
    }

    wp_safe_redirect( add_query_arg( 'application', 'sent', wp_get_referer() ?: home_url() ) );
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

// ─── INVENTORY FILTER HELPERS ────────────────────────────────────────────────

/**
 * Returns distinct makes, categories, conditions (with counts), and
 * min/max year + price for all published equipment.
 */
function varner_get_filter_data() {
    global $wpdb;

    $base = "FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.post_type = 'equipment'
               AND p.post_status = 'publish'
               AND pm.meta_value != ''";

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

    return compact( 'makes', 'categories', 'conditions', 'year_range', 'price_range' );
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

    $paged = max( 1, intval( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

    $filters = array(
        'category'  => array_map( 'sanitize_text_field', (array) ( $_GET['category']  ?? [] ) ),
        'make'      => array_map( 'sanitize_text_field', (array) ( $_GET['make']      ?? [] ) ),
        'condition' => array_map( 'sanitize_text_field', (array) ( $_GET['condition'] ?? [] ) ),
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
        $search .= "{$search_and}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR (EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key IN ('make','model','category','sub-category','sub-sub-category','stock_number','vin') AND meta_value LIKE '{$n}{$term}{$n}')))";
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
    $current = $_GET;
    unset( $current['paged'] );
    if ( $value === null ) {
        unset( $current[ $key ] );
    } else {
        $arr     = array_map( 'sanitize_text_field', (array) ( $current[ $key ] ?? [] ) );
        $arr     = array_values( array_filter( $arr, function ( $v ) use ( $value ) { return $v !== $value; } ) );
        if ( empty( $arr ) ) { unset( $current[ $key ] ); } else { $current[ $key ] = $arr; }
    }
    
    $base_url = is_singular() ? get_permalink() : home_url( add_query_arg( array(), $wp->request ) );
    return $base_url . ( $current ? '?' . http_build_query( $current ) : '' );
}

/**
 * Returns a page URL with both range keys removed (e.g. year_min + year_max together).
 */
function varner_remove_range_filter( $key1, $key2 ) {
    global $wp;
    $current = $_GET;
    unset( $current[ $key1 ], $current[ $key2 ], $current['paged'] );
    return ( is_singular() ? get_permalink() : home_url( add_query_arg( array(), $wp->request ) ) ) . ( $current ? '?' . http_build_query( $current ) : '' );
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
 * Render Breadcrumbs for the current page.
 */
function varner_render_breadcrumbs() {
    if ( is_front_page() ) return; // No breadcrumbs on home

    echo '<div class="bg-white border-b border-slate-100">';
    echo '<div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-4">';
    
    echo '<nav class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2 flex-wrap">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="hover:text-red-600 transition-colors">Home</a>';

    $request_uri = $_SERVER['REQUEST_URI'];

    if ( is_singular('equipment') ) {
        echo '<span>›</span>';
        echo '<a href="' . esc_url( home_url( '/inventory/all-units' ) ) . '" class="hover:text-red-600 transition-colors">Inventory</a>';
        
        $category = get_field('category');
        if ( $category ) {
            echo '<span>›</span>';
            $cat_slug = sanitize_title($category);
            echo '<a href="' . esc_url( home_url( '/inventory/' . $cat_slug ) ) . '" class="hover:text-red-600 transition-colors">' . esc_html( $category ) . '</a>';
        }

        echo '<span>›</span>';
        echo '<span class="text-slate-900">' . get_the_title() . '</span>';

    } elseif ( strpos($request_uri, '/brands/') !== false ) {
        echo '<span>›</span>';
        echo '<a href="' . esc_url( home_url( '/brands' ) ) . '" class="hover:text-red-600 transition-colors">Brands</a>';
        echo '<span>›</span>';
        echo '<span class="text-slate-900">' . get_the_title() . '</span>';

    } elseif ( is_page() ) {
        // Special case for equipment listing pages
        $slug = get_query_var('inventory_segment');
        if ( $slug && $slug !== 'all-units' ) {
            echo '<span>›</span>';
            echo '<a href="' . esc_url( home_url( '/inventory/all-units' ) ) . '" class="hover:text-red-600 transition-colors">Inventory</a>';
        }

        $post = get_post();
        if ( $post && $post->post_parent ) {
            $parent_id   = $post->post_parent;
            $breadcrumbs = array();
            while ( $parent_id ) {
                $page = get_page( $parent_id );
                $breadcrumbs[] = '<a href="' . get_permalink( $page->ID ) . '" class="hover:text-red-600 transition-colors">' . get_the_title( $page->ID ) . '</a>';
                $parent_id  = $page->post_parent;
            }
            $breadcrumbs = array_reverse( $breadcrumbs );
            foreach ( $breadcrumbs as $crumb ) {
                echo '<span>›</span>';
                echo $crumb;
            }
        }
        echo '<span>›</span>';
        echo '<span class="text-slate-900">' . get_the_title() . '</span>';

    } elseif ( is_search() ) {
        echo '<span>›</span>';
        echo '<span class="text-slate-900">Search Results</span>';
    } elseif ( is_404() ) {
        echo '<span>›</span>';
        echo '<span class="text-slate-900">404 - Not Found</span>';
    }

    echo '</nav>';

    echo '</div>';
    echo '</div>';
}

/**
 * Register Video Custom Post Type and Taxonomy
 */
function varner_register_video_cpt() {
    $labels = array(
        "name" => __( "Videos", "varner-v23" ),
        "singular_name" => __( "Video", "varner-v23" ),
        "menu_name" => __( "Videos", "varner-v23" ),
        "all_items" => __( "All Videos", "varner-v23" ),
        "add_new" => __( "Add New Video", "varner-v23" ),
        "add_new_item" => __( "Add New Video", "varner-v23" ),
        "edit_item" => __( "Edit Video", "varner-v23" ),
        "new_item" => __( "New Video", "varner-v23" ),
        "view_item" => __( "View Video", "varner-v23" ),
        "search_items" => __( "Search Videos", "varner-v23" ),
        "not_found" => __( "No Videos Found", "varner-v23" ),
    );

    $args = array(
        "label" => __( "Videos", "varner-v23" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => array( "slug" => "video", "with_front" => true ),
        "query_var" => true,
        "menu_icon" => "dashicons-video-alt3",
        "supports" => array( "title" ),
    );

    register_post_type( "video", $args );

    $tax_labels = array(
        "name" => __( "Video Categories", "varner-v23" ),
        "singular_name" => __( "Video Category", "varner-v23" ),
    );

    $tax_args = array(
        "label" => __( "Video Categories", "varner-v23" ),
        "labels" => $tax_labels,
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => true,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "query_var" => true,
        "rewrite" => array( "slug" => "video_category", "with_front" => true ),
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rest_base" => "video_category",
        "rest_controller_class" => "WP_REST_Terms_Controller",
        "show_in_quick_edit" => false,
    );
    register_taxonomy( "video_category", array( "video" ), $tax_args );
}
add_action( "init", "varner_register_video_cpt" );

