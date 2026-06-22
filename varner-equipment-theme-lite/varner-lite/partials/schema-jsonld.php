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
	if ( ! ( is_front_page() || is_page( 'contact' ) ) ) {
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
	$phone_tel = '+1-' . preg_replace( '/[^0-9]/', '', $phone );
	$phone_tel = substr( $phone_tel, 0, 3 ) . '-' . substr( preg_replace( '/[^0-9]/', '', $phone ), 0, 3 )
	           . '-' . substr( preg_replace( '/[^0-9]/', '', $phone ), 3, 3 )
	           . '-' . substr( preg_replace( '/[^0-9]/', '', $phone ), 6 );

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
		'description'    => 'Family-owned farm, ranch, and agricultural equipment dealership on Colorado\'s Western Slope, carrying Mahindra tractors and Big Tex and CM trailers, with parts and service in Delta.',
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
add_action( 'wp_head', 'varner_localbusiness_schema', 20 );


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
	);

	if ( $description )  { $product['description'] = $description; }
	if ( ! empty( $images ) ) { $product['image'] = $images; }
	if ( $make )          { $product['brand'] = array( '@type' => 'Brand', 'name' => $make ); }
	if ( $model )         { $product['model'] = $model; }
	if ( $stock_number )  { $product['sku']   = (string) $stock_number; }

	// Only build an Offer when there's a real price (no fake $0 "call for price").
	if ( $price > 0 && ! $call_for_price ) {
		// Map stock_status values: 'sold' | 'pending sale' -> OutOfStock, else InStock.
		$out_of_stock = in_array( $stock_status, array( 'sold', 'pending sale' ), true );

		$product['offers'] = array(
			'@type'           => 'Offer',
			'priceCurrency'   => 'USD',
			'price'           => number_format( $price, 2, '.', '' ),
			'priceValidUntil' => date( 'Y-m-d', strtotime( '+1 year' ) ),
			'availability'    => $out_of_stock
				? 'https://schema.org/OutOfStock'
				: 'https://schema.org/InStock',
			'itemCondition'   => ( 'new' === $condition )
				? 'https://schema.org/NewCondition'
				: 'https://schema.org/UsedCondition',
			'url'             => get_permalink( $id ),
			'seller'          => array( '@id' => VARNER_BUSINESS_ID ),
		);
	}

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'varner_listing_schema', 20 );
