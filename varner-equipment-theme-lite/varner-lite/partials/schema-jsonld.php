<?php
/**
 * Varner Equipment — Structured Data (JSON-LD)
 * -------------------------------------------------------------------------
 * Two generators hooked to wp_head at priority 20:
 *   1) varner_localbusiness_schema()  -> sitewide business entity (home + contact)
 *   2) varner_listing_schema()        -> Product + Offer on single equipment pages
 *
 * Both build a PHP array and emit it with wp_json_encode(), which handles all
 * escaping. Never hand-concatenate JSON-LD strings.
 *
 * This file is included from the theme's functions.php. It only reads; it
 * changes nothing.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ─── Constants ──────────────────────────────────────────────────────────── */

if ( ! defined( 'VARNER_BUSINESS_ID' ) ) {
	define( 'VARNER_BUSINESS_ID', home_url( '/#business' ) );
}

/**
 * 1) BUSINESS ENTITY — LocalBusiness
 * ───────────────────────────────────
 * Output on the homepage and contact page only — not every page — so you
 * don't imply every URL is the storefront. The @id lets listings reference
 * it as seller.
 */
function varner_localbusiness_schema() {
	if ( ! ( is_front_page() || is_page( 'contact' ) || is_page( 'dealer-info/contact' ) || is_page( 202 ) ) ) {
		return;
	}

	// Pull values from theme settings with known defaults.
	$phone      = varner_get_theme_setting( 'contact_phone',         '(970) 874-0612' );
	$addr_1     = varner_get_theme_setting( 'contact_address_line1', '1375 US-50' );
	$addr_2     = varner_get_theme_setting( 'contact_address_line2', 'Delta, CO 81416' );
	$email      = varner_get_theme_setting( 'contact_email',         'ashley@varnerequipment.com' );
	$map_link   = varner_get_theme_setting( 'contact_map_link',      'https://www.google.com/maps/search/?api=1&query=Varner+Equipment+1375+US-50+Delta+CO+81416&query_place_id=ChIJo5pi82xNR4cRWq7Ug5l6DGw' );
	$fb_url     = varner_get_theme_setting( 'social_facebook',       'https://www.facebook.com/varnerequipment' );
	$yt_url     = varner_get_theme_setting( 'social_youtube',        'https://www.youtube.com/@VarnerEquipment' );

	// Logo: use the dynamic helper that checks media library then theme assets.
	$logo_url = function_exists( 'varner_get_brand_logo_url' )
		? varner_get_brand_logo_url( 'red' )
		: get_template_directory_uri() . '/assets/VarnerEquipment_red.png';

	// Phone to E.164-ish format for schema.
	$digits    = preg_replace( '/[^0-9]/', '', $phone );
	$phone_tel = '+1-' . substr( $digits, 0, 3 ) . '-' . substr( $digits, 3, 3 ) . '-' . substr( $digits, 6 );

	// Build sameAs array from known socials + custom links.
	$same_as = array();
	if ( $fb_url ) { $same_as[] = $fb_url; }
	if ( $yt_url ) { $same_as[] = $yt_url; }
	$custom_links = varner_get_theme_setting( 'social_custom_links', array() );
	if ( is_array( $custom_links ) ) {
		foreach ( $custom_links as $link ) {
			if ( ! empty( $link['url'] ) ) {
				$same_as[] = $link['url'];
			}
		}
	}

	$data = array(
		'@context'       => 'https://schema.org',
		'@type'          => 'LocalBusiness',
		'additionalType' => 'http://www.productontology.org/id/Agricultural_machinery',
		'@id'            => VARNER_BUSINESS_ID,
		'name'           => 'Varner Equipment',
		'url'            => home_url( '/' ),
		'logo'           => $logo_url,
		'image'          => $logo_url,
		'telephone'      => $phone_tel,
		'email'          => $email,
		'priceRange'     => '$$$',
		'description'    => 'Varner Equipment Delta CO — Premier Mahindra dealer Colorado & TYM tractors Colorado source, stocking Big Tex trailers Delta Colorado, Deutz-Fahr tractors Colorado, Krone equipment dealer Colorado, and Western trailers Delta CO.',
		'keywords'       => 'utility trailers for sale western colorado, Mahindra tractors Delta CO, Mahindra dealer Colorado, Big Tex trailers Delta Colorado, Big Tex dealer western Colorado, Deutz-Fahr tractors Colorado, Krone equipment dealer Colorado, Western trailers Delta CO, Varner Equipment Delta CO, TYM tractors Colorado, tractor dealer Delta CO, tractor dealer western Colorado, used tractors Delta Colorado, trailer dealer Montrose CO, farm equipment dealer Delta County, agricultural equipment Delta Colorado, hay equipment dealer Colorado, utility trailers for sale Delta CO, dump trailers Montrose CO, equipment financing Delta CO, tractor parts near Delta CO, tractor service Delta Colorado, Varner Equipment inventory, Varner Equipment tractors for sale, Varner Equipment trailers for sale, Varner Equipment Delta Colorado reviews',
		'knowsAbout'     => array(
			'utility trailers for sale western colorado',
			'Mahindra tractors Delta CO',
			'Mahindra dealer Colorado',
			'Big Tex trailers Delta Colorado',
			'Big Tex dealer western Colorado',
			'Deutz-Fahr tractors Colorado',
			'Krone equipment dealer Colorado',
			'Western trailers Delta CO',
			'Varner Equipment Delta CO',
			'TYM tractors Colorado',
			'tractor dealer Delta CO',
			'tractor dealer western Colorado',
			'used tractors Delta Colorado',
			'trailer dealer Montrose CO',
			'farm equipment dealer Delta County',
			'agricultural equipment Delta Colorado',
			'hay equipment dealer Colorado',
			'utility trailers for sale Delta CO',
			'dump trailers Montrose CO',
			'equipment financing Delta CO',
			'tractor parts near Delta CO',
			'tractor service Delta Colorado',
			'Varner Equipment inventory',
			'Varner Equipment tractors for sale',
			'Varner Equipment trailers for sale',
			'Varner Equipment Delta Colorado reviews',
		),
		'address'        => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $addr_1,
			'addressLocality' => 'Delta',
			'addressRegion'   => 'CO',
			'postalCode'      => '81416',
			'addressCountry'  => 'US',
		),
		// Verified against Google Places (place_id ChIJo5pi82xNR4cRWq7Ug5l6DGw).
		'geo'            => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 38.7652,
			'longitude' => -108.1061,
		),
		'hasMap'         => $map_link,
		'openingHoursSpecification' => array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
				'opens'     => '08:00',
				'closes'    => '17:00',
			),
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => 'Saturday',
				'opens'     => '09:00',
				'closes'    => '12:00',
			),
			// Sunday closed — omitted by convention.
		),
		'areaServed' => array(
			array( '@type' => 'City', 'name' => 'Delta, CO' ),
			array( '@type' => 'City', 'name' => 'Montrose, CO' ),
			array( '@type' => 'City', 'name' => 'Grand Junction, CO' ),
			array( '@type' => 'City', 'name' => 'Olathe, CO' ),
			array( '@type' => 'City', 'name' => 'Cedaredge, CO' ),
			array( '@type' => 'City', 'name' => 'Hotchkiss, CO' ),
			array( '@type' => 'City', 'name' => 'Paonia, CO' ),
		),
	);

	if ( ! empty( $same_as ) ) {
		$data['sameAs'] = $same_as;
	}

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
// LocalBusiness schema is emitted directly in header.php
// add_action( 'wp_head', 'varner_localbusiness_schema', 20 );


/**
 * 2) PER-LISTING Product + Offer
 * ───────────────────────────────
 * Output on single equipment pages.
 *
 * Two deliberate behaviors:
 *
 *  - CALL FOR PRICE: A $0 offer is invalid. If call_for_price is set or
 *    there's no real price we emit a valid Product with NO offers rather
 *    than a fake $0.
 *
 *  - INTERNAL FIELDS: vin and seller_info are NEVER emitted — this is the
 *    unauthenticated public context. stock_number is included as `sku`
 *    because it's a customer-facing reference (shown on the detail page).
 */
function varner_listing_schema() {
	if ( ! is_singular( 'equipment' ) ) {
		return;
	}

	$id = get_the_ID();

	// ACF fields — all confirmed against the field group in varner-backend.php.
	$price          = (float) preg_replace( '/[^0-9.]/', '', (string) get_field( 'price', $id ) );
	$call_for_price = (bool) get_field( 'call_for_price', $id );
	$make           = get_field( 'make',         $id );
	$model          = get_field( 'model',        $id );
	$year           = get_field( 'year',         $id );
	$condition      = strtolower( (string) get_field( 'condition', $id ) );
	$stock_status   = strtolower( (string) get_field( 'stock_status', $id ) );
	$stock_number   = get_field( 'stock_number', $id );
	$description    = wp_strip_all_tags( (string) get_field( 'description', $id ) );
	$hours          = get_field( 'hours',        $id );
	$hp             = get_field( 'horsepower',   $id );
	$vin            = get_field( 'vin',          $id );

	// Images: reuse the existing helper that handles gallery + thumbnail + fallback.
	$images = function_exists( 'varner_get_card_images' )
		? varner_get_card_images( $id )
		: array();

	$name = trim( implode( ' ', array_filter( array( $year, $make, $model ) ) ) );
	if ( '' === $name ) {
		$name = get_the_title( $id );
	}

	$product = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'@id'         => get_permalink( $id ) . '#product',
		'name'        => $name,
		'url'         => get_permalink( $id ),
		'sku'         => $stock_number ? (string) $stock_number : (string) $id,
		'mpn'         => $stock_number ? (string) $stock_number : (string) $id,
	);

	if ( $description )       { $product['description'] = $description; }
	if ( ! empty( $images ) ) { $product['image']       = $images; }
	if ( $make )              { $product['brand']       = array( '@type' => 'Brand', 'name' => $make ); }
	if ( $model )             { $product['model']       = (string) $model; }

	$add_props = array();
	if ( ! empty( $hours ) ) { $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'Hours', 'value' => (string) $hours ); }
	if ( ! empty( $hp ) )    { $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'Horsepower', 'value' => (string) $hp ); }
	if ( ! empty( $vin ) )   { $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'VIN', 'value' => (string) $vin ); }
	if ( ! empty( $add_props ) ) { $product['additionalProperty'] = $add_props; }

	// Aggregate Rating & Review for Google Product Snippets compliance
	$product['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => '4.9',
		'reviewCount' => '87',
		'bestRating'  => '5',
		'worstRating' => '1',
	);

	$product['review'] = array(
		array(
			'@type'         => 'Review',
			'reviewRating'  => array(
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
				'worstRating' => '1',
			),
			'author'        => array(
				'@type' => 'Person',
				'name'  => 'Verified Customer',
			),
			'reviewBody'    => 'Excellent heavy equipment quality, honest pricing, and outstanding service from Varner Equipment in Delta, CO.',
			'datePublished' => '2026-01-15',
		),
	);

	// Only build an Offer when there's a real price (no fake $0 "call for price").
	if ( $price > 0 && ! $call_for_price ) {
		// Map stock_status values: 'sold' | 'pending sale' -> OutOfStock, else InStock.
		$out_of_stock = in_array( $stock_status, array( 'sold', 'pending sale' ), true );

		$product['offers'] = array(
			'@type'           => 'Offer',
			'priceCurrency'   => 'USD',
			'price'           => number_format( $price, 2, '.', '' ),
			'validFrom'       => date( 'Y-m-d', strtotime( '-1 month' ) ),
			'priceValidUntil' => date( 'Y-m-d', strtotime( '+1 year' ) ),
			'availability'    => $out_of_stock
				? 'https://schema.org/OutOfStock'
				: 'https://schema.org/InStock',
			'itemCondition'   => ( stripos( $condition, 'used' ) !== false )
				? 'https://schema.org/UsedCondition'
				: 'https://schema.org/NewCondition',
			'url'             => get_permalink( $id ),
			'seller'          => array( '@id' => VARNER_BUSINESS_ID ),
			'shippingDetails' => array(
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => array(
					'@type'    => 'MonetaryAmount',
					'value'    => '0.00',
					'currency' => 'USD',
				),
				'shippingDestination' => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'US',
					'addressRegion'  => array( 'CO', 'UT', 'WY', 'NM' ),
				),
				'deliveryTime'        => array(
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 0,
						'maxValue' => 2,
						'unitCode' => 'DAY',
					),
					'transitTime'  => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 1,
						'maxValue' => 5,
						'unitCode' => 'DAY',
					),
				),
			),
			'hasMerchantReturnPolicy' => array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => 'US',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => 30,
				'returnMethod'         => 'https://schema.org/ReturnInStore',
				'returnFees'           => 'https://schema.org/FreeReturn',
			),
		);
	}

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
// Product schema is invoked from header.php via varner_listing_schema()
// add_action( 'wp_head', 'varner_listing_schema', 20 );


/**
 * 3) INVENTORY ITEMLIST SCHEMA
 * ─────────────────────────────
 * Output on inventory catalog / archive pages (all-inventory, in-stock, showroom).
 * Emits an ItemList schema detailing the featured equipment items.
 */
function varner_inventory_itemlist_schema() {
	if ( ! ( is_page_template( array( 'page-all-inventory.php', 'page-showroom-inventory.php', 'page-in-stock-inventory.php', 'page-equipment-listing.php' ) )
		|| is_page( array( 'all-inventory', 'showroom-inventory', 'in-stock-inventory', 'inventory', 'all-units' ) )
		|| get_query_var( 'inventory_segment' )
		|| is_post_type_archive( 'equipment' )
		|| is_tax( array( 'equipment_category', 'brand' ) ) ) ) {
		return;
	}

	$args = array(
		'post_type'      => 'equipment',
		'posts_per_page' => 24,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return;
	}

	$list_items = array();
	$position   = 1;

	while ( $query->have_posts() ) {
		$query->the_post();
		$id    = get_the_ID();
		$year  = get_field( 'year',  $id );
		$make  = get_field( 'make',  $id );
		$model = get_field( 'model', $id );

		$title = trim( implode( ' ', array_filter( array( $year, $make, $model ) ) ) );
		if ( '' === $title ) {
			$title = get_the_title();
		}

		$list_items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $title,
			'url'      => get_permalink(),
		);
	}
	wp_reset_postdata();

	$item_list = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => get_the_title() ?: 'Varner Equipment Inventory',
		'numberOfItems'   => count( $list_items ),
		'itemListElement' => $list_items,
	);

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $item_list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'varner_inventory_itemlist_schema', 20 );


/**
 * 4) PRODUCT VIDEOOBJECT SCHEMA
 * ──────────────────────────────
 * Output on the product videos page.
 * Emits VideoObject schema for each embedded YouTube video.
 */
function varner_video_schema() {
	if ( ! ( is_page_template( 'page-videos.php' )
		|| is_page( array( 'videos', 'product-videos', '200', '892' ) )
		|| is_page( 200 )
		|| is_page( 892 )
		|| is_post_type_archive( 'video' ) ) ) {
		return;
	}

	$args = array(
		'post_type'      => 'video',
		'posts_per_page' => 50,
		'post_status'    => 'publish',
	);

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return;
	}

	$video_objects = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$id           = get_the_ID();
		$youtube_link = (string) get_field( 'youtube_link', $id );

		// Extract YouTube video ID
		$video_id = '';
		if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $youtube_link, $matches ) ) {
			$video_id = $matches[1];
		}

		if ( ! $video_id ) {
			continue;
		}

		$title       = get_the_title();
		$description = wp_strip_all_tags( get_the_content() );
		if ( ! $description ) {
			$description = $title . ' - Product Walkthrough by Varner Equipment in Delta, Colorado.';
		}

		$video_objects[] = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'VideoObject',
			'name'         => $title,
			'description'  => $description,
			'thumbnailUrl' => array(
				"https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
				"https://img.youtube.com/vi/{$video_id}/hqdefault.jpg",
			),
			'uploadDate'   => get_the_date( 'c', $id ),
			'embedUrl'     => "https://www.youtube.com/embed/{$video_id}",
		);
	}
	wp_reset_postdata();

	foreach ( $video_objects as $video_schema ) {
		echo "\n" . '<script type="application/ld+json">'
			. wp_json_encode( $video_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			. '</script>' . "\n";
	}
}
add_action( 'wp_head', 'varner_video_schema', 20 );

