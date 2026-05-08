<?php
/**
 * Theme Setup and Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
    varner_verify_form_submission( 'varner_service_nonce', 'varner_service_request_submit', 'varner_service_captcha' );

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
add_filter( 'template_include', function( $template ) {
    if ( ! is_page() ) {
        return $template;
    }

    $req = $_SERVER['REQUEST_URI'] ?? '';

    // Match /brand(s)/slug — but not the root /brands/ listing page
    $url_match = preg_match( '#/(brand|brands)/[^/]+/?$#', $req )
              && ! preg_match( '#/(brand|brands)/?$#', $req );

    // Also match when the page's parent has slug "brands" or "brand"
    $parent_match = false;
    $queried = get_queried_object();
    if ( $queried instanceof WP_Post && $queried->post_parent ) {
        $parent = get_post( $queried->post_parent );
        if ( $parent && in_array( $parent->post_name, array( 'brands', 'brand' ), true ) ) {
            $parent_match = true;
        }
    }

    if ( $url_match || $parent_match ) {
        $brand_template = locate_template( 'page-brand.php' );
        if ( $brand_template ) {
            return $brand_template;
        }
    }

    return $template;
}, 20 );

// Clear brand counts cache whenever an equipment post is saved or deleted
add_action( 'save_post_equipment',   function() { delete_transient( 'varner_brand_counts' ); } );
add_action( 'delete_post',           function( $id ) { if ( get_post_type( $id ) === 'equipment' ) delete_transient( 'varner_brand_counts' ); } );
add_action( 'trashed_post',          function( $id ) { if ( get_post_type( $id ) === 'equipment' ) delete_transient( 'varner_brand_counts' ); } );

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

    return compact( 'makes', 'categories', 'conditions', 'year_range' );
}

/**
 * Build the WP_Query args array for the inventory pages based on $_GET filters.
 * Accepts $base_meta (array) to pre-populate the meta_query (e.g. stock_status restriction).
 */
function varner_build_inventory_query( $base_meta = array(), $posts_per_page = -1 ) {
    $meta = array();
    if ( ! empty( $base_meta ) ) {
        $meta = array_merge( array( 'relation' => 'AND' ), $base_meta );
    }

    $filters = array(
        'category'  => array_map( 'sanitize_text_field', (array) ( $_GET['category']  ?? [] ) ),
        'make'      => array_map( 'sanitize_text_field', (array) ( $_GET['make']      ?? [] ) ),
        'condition' => array_map( 'sanitize_text_field', (array) ( $_GET['condition'] ?? [] ) ),
    );

    foreach ( $filters as $key => $vals ) {
        if ( $vals ) {
            if ( empty( $meta ) ) { $meta['relation'] = 'AND'; }
            $meta[] = array( 'key' => $key, 'value' => $vals, 'compare' => 'IN' );
        }
    }

    $ranges = array(
        'year'  => array( 'min' => intval( $_GET['year_min'] ?? 0 ),  'max' => intval( $_GET['year_max'] ?? 0 ),  'type' => 'NUMERIC' ),
        'price' => array( 'min' => intval( $_GET['price_min'] ?? 0 ), 'max' => intval( $_GET['price_max'] ?? 0 ), 'type' => 'DECIMAL' ),
    );

    foreach ( $ranges as $key => $r ) {
        if ( $r['min'] || $r['max'] ) {
            if ( empty( $meta ) ) { $meta['relation'] = 'AND'; }
            if ( $r['min'] && $r['max'] ) {
                $meta[] = array( 'key' => $key, 'value' => array( $r['min'], $r['max'] ), 'compare' => 'BETWEEN', 'type' => $r['type'] );
            } elseif ( $r['min'] ) {
                $meta[] = array( 'key' => $key, 'value' => $r['min'], 'compare' => '>=', 'type' => $r['type'] );
            } elseif ( $r['max'] ) {
                $meta[] = array( 'key' => $key, 'value' => $r['max'], 'compare' => '<=', 'type' => $r['type'] );
            }
        }
    }

    $stock_num = sanitize_text_field( $_GET['stock_number'] ?? '' );
    if ( $stock_num ) {
        if ( empty( $meta ) ) { $meta['relation'] = 'AND'; }
        $meta[] = array( 'key' => 'stock_number', 'value' => $stock_num, 'compare' => 'LIKE' );
    }

    $vin_num = sanitize_text_field( $_GET['vin'] ?? '' );
    if ( $vin_num ) {
        if ( empty( $meta ) ) { $meta['relation'] = 'AND'; }
        $meta[] = array( 'key' => 'vin', 'value' => $vin_num, 'compare' => 'LIKE' );
    }

    $args = array(
        'post_type'      => 'equipment',
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
    );
    
    if ( ! empty( $meta ) ) {
        $args['meta_query'] = $meta;
    }

    $keyword = sanitize_text_field( $_GET['s'] ?? '' );
    if ( $keyword ) { $args['s'] = $keyword; }

    return $args;
}

/**
 * Returns a page URL with a single filter value removed.
 * For array filters (category[], make[], condition[]), removes just that one value.
 * For scalar filters, removes the key entirely.
 */
function varner_remove_filter( $key, $value = null ) {
    $current = $_GET;
    unset( $current['paged'] );
    if ( $value === null ) {
        unset( $current[ $key ] );
    } else {
        $arr     = array_map( 'sanitize_text_field', (array) ( $current[ $key ] ?? [] ) );
        $arr     = array_values( array_filter( $arr, function ( $v ) use ( $value ) { return $v !== $value; } ) );
        if ( empty( $arr ) ) { unset( $current[ $key ] ); } else { $current[ $key ] = $arr; }
    }
    return get_permalink() . ( $current ? '?' . http_build_query( $current ) : '' );
}

/**
 * Returns a page URL with both range keys removed (e.g. year_min + year_max together).
 */
function varner_remove_range_filter( $key1, $key2 ) {
    $current = $_GET;
    unset( $current[ $key1 ], $current[ $key2 ], $current['paged'] );
    return get_permalink() . ( $current ? '?' . http_build_query( $current ) : '' );
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

/**
 * Register Query Vars
 */
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'inventory_segment';
    return $vars;
});

/**
 * FacetWP SEO: Map URL segments and query params to FacetWP filters.
 */
add_filter( 'facetwp_preload_url_vars', function( $url_vars ) {
    $seg = get_query_var('inventory_segment');
    if ( $seg ) {
        $seo = varner_get_segment_seo($seg);
        if ( $seo && ! empty($seo['filter']) ) {
            foreach ( $seo['filter'] as $key => $vals ) {
                $facet_name = 'inventory_' . $key;
                if ( empty($url_vars[$facet_name]) ) {
                    $url_vars[$facet_name] = (array) $vals;
                }
            }
        }
    }
    if ( ! empty($_GET['fwp_inventory_search']) ) {
        $url_vars['inventory_search'] = sanitize_text_field($_GET['fwp_inventory_search']);
    }
    return $url_vars;
});

/**
 * Register Rewrite Rules
 * Handles /inventory/new, /inventory/used, etc.
 */
add_action( 'init', function() {
    add_rewrite_rule(
        '^inventory/(all-units|new|used|tractors|trailers|attachments|hay-equipment|misc)/?$',
        'index.php?inventory_segment=$matches[1]',
        'top'
    );
});

/**
 * SEO & Filter Mapping for Inventory Segments
 */
function varner_get_segment_seo($slug) {
    $segments = array(
        'all-units' => array(
            'title'  => 'All Inventory',
            'h1'     => 'Complete Collection',
            'sub'    => 'Western Colorado\'s most diverse selection of heavy-duty equipment.',
            'blurb'  => 'From tractors to trailers, we keep you moving with the best brands in the business.',
            'filter' => array()
        ),
        'new' => array(
            'title'  => 'New Inventory',
            'h1'     => 'New Equipment',
            'sub'    => 'Explore the latest high-performance machines from Mahindra, Big Tex, and more.',
            'blurb'  => 'Quality you can trust, straight from the factory.',
            'filter' => array('condition' => array('New'))
        ),
        'used' => array(
            'title'  => 'Used Inventory',
            'h1'     => 'Proven Performance',
            'sub'    => 'Inspected and certified pre-owned units ready for work.',
            'blurb'  => 'Rugged equipment with years of life left.',
            'filter' => array('condition' => array('Used'))
        ),
        'tractors' => array(
            'title'  => 'Tractors',
            'h1'     => 'Heavy-Duty Tractors',
            'sub'    => 'The backbone of any operation.',
            'blurb'  => 'From compact utility tractors to commercial-grade powerhouses.',
            'filter' => array('category' => array('Compact Tractors', 'Tractors', 'Utility Tractors'))
        ),
        'trailers' => array(
            'title'  => 'Trailers',
            'h1'     => 'Commercial Trailers',
            'sub'    => 'Haul with confidence.',
            'blurb'  => 'Big Tex, Triton, and Titan – the best names in the business.',
            'filter' => array('category' => array('Commercial Trailers', 'Trailers', 'Dump Trailers', 'Flatbed Trailers', 'Utility Trailers', 'Horse Trailers', 'Livestock Trailers'))
        ),
        'attachments' => array(
            'title'  => 'Attachments',
            'h1'     => 'Attachments & Implements',
            'sub'    => 'Maximize your machine’s versatility.',
            'blurb'  => 'Loaders, mowers, post-hole diggers, and more.',
            'filter' => array('category' => array('Implements', 'Attachments', 'Loaders', 'Mowers', 'Box Blades'))
        ),
        'hay-equipment' => array(
            'title'  => 'Hay Equipment',
            'h1'     => 'Hay & Harvest',
            'sub'    => 'Precision baling and mowing technology.',
            'blurb'  => 'Krone and MacDon equipment designed for maximum yield.',
            'filter' => array('category' => array('Hay Equipment', 'Balers', 'Rakes', 'Tedders'))
        ),
        'misc' => array(
            'title'  => 'Miscellaneous',
            'h1'     => 'Misc. Equipment',
            'sub'    => 'Various tools, accessories, and unique inventory items.',
            'blurb'  => 'Find those specific tools and utility vehicles.',
            'filter' => array('category' => array('Misc', 'Other', 'Utility Vehicles', 'Golf Carts', 'Snow Removal'))
        )
    );
    return $segments[$slug] ?? null;
}

/**
 * Template Redirect: Force segments to use the dynamic template
 */
add_filter( 'template_include', function( $template ) {
    if ( get_query_var('inventory_segment') ) {
        $listing_template = locate_template('page-equipment-listing.php');
        if ( $listing_template ) {
            return $listing_template;
        }
    }
    return $template;
}, 30);

/**
 * Load all ACF fields for a single equipment post and include the card partial.
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

// Carousel JS — outputs once in the footer.
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
    document.addEventListener('facetwp-loaded', initVarnerCarousels);
})();
</script>
    <?php
} );

/**
 * SEO DOMINANCE: JSON-LD Schema Markup
 */
add_action( 'wp_head', function() {
    $local_business = array(
        "@context" => "https://schema.org",
        "@type" => "AutomotiveBusiness",
        "name" => "Varner Equipment",
        "image" => varner_get_brand_logo_url('red'),
        "@id" => home_url('/'),
        "url" => home_url('/'),
        "telephone" => "+19708740612",
        "address" => array(
            "@type" => "PostalAddress",
            "streetAddress" => "1375 US-50",
            "addressLocality" => "Delta",
            "addressRegion" => "CO",
            "postalCode" => "81416",
            "addressCountry" => "US"
        )
    );
    echo '<script type="application/ld+json">' . json_encode($local_business) . '</script>' . "\n";
    
    if ( is_singular('equipment') && function_exists('get_field') ) {
        $post_id = get_the_ID();
        $title = trim(get_field('year', $post_id) . ' ' . get_field('make', $post_id) . ' ' . get_field('model', $post_id)) ?: get_the_title();
        $product_schema = array(
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $title,
            "image" => varner_get_card_images($post_id),
            "description" => wp_strip_all_tags(get_field('description', $post_id)),
            "sku" => get_field('stock_number', $post_id),
            "brand" => array("@type" => "Brand", "name" => get_field('make', $post_id)),
            "offers" => array(
                "@type" => "Offer",
                "url" => get_permalink($post_id),
                "priceCurrency" => "USD",
                "price" => get_field('price', $post_id) ?: "0",
                "availability" => (get_field('stock_status', $post_id) === 'Sold') ? "https://schema.org/OutOfStock" : "https://schema.org/InStock",
                "itemCondition" => (get_field('condition', $post_id) === 'New') ? "https://schema.org/NewCondition" : "https://schema.org/UsedCondition",
                "seller" => array("@type" => "Organization", "name" => "Varner Equipment")
            )
        );
        echo '<script type="application/ld+json">' . json_encode($product_schema) . '</script>' . "\n";
    }
}, 5 );
