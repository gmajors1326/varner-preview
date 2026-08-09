<?php
/**
 * Varner Equipment - Native XML Sitemap Generator
 * Route: /sitemap-inventory.xml
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function varner_generate_xml_sitemap_index() {
	header( 'Content-Type: application/xml; charset=utf-8' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	$sitemaps = array(
		'/wp-sitemap.xml',
		'/sitemap-inventory.xml',
	);

	foreach ( $sitemaps as $sitemap ) {
		echo "  <sitemap>\n";
		echo "    <loc>" . esc_url( home_url( $sitemap ) ) . "</loc>\n";
		echo "    <lastmod>" . date( 'Y-m-d' ) . "</lastmod>\n";
		echo "  </sitemap>\n";
	}

	echo '</sitemapindex>';
	exit;
}

function varner_generate_xml_sitemap() {
	header( 'Content-Type: application/xml; charset=utf-8' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

	$today = date( 'Y-m-d' );

	// 1. Core Static Pages
	$static_pages = array(
		'/'                           => array( 'p' => '1.0', 'c' => 'daily' ),
		'/inventory/all-units/'        => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/new/'              => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/used/'             => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/tractors/'         => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/trailers/'         => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/utility-trailers/' => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/dump-trailers/'    => array( 'p' => '0.9', 'c' => 'daily' ),
		'/inventory/attachments/'      => array( 'p' => '0.9', 'c' => 'weekly' ),
		'/inventory/hay-equipment/'    => array( 'p' => '0.9', 'c' => 'weekly' ),
		'/finance/'                   => array( 'p' => '0.8', 'c' => 'monthly' ),
		'/services/service-request/'   => array( 'p' => '0.8', 'c' => 'monthly' ),
		'/services/parts-request/'     => array( 'p' => '0.8', 'c' => 'monthly' ),
		'/dealer-info/about-us/'       => array( 'p' => '0.8', 'c' => 'monthly' ),
		'/videos/'                    => array( 'p' => '0.7', 'c' => 'weekly' ),
		'/dealer-info/employment/'     => array( 'p' => '0.6', 'c' => 'monthly' ),
		'/contact/'                   => array( 'p' => '0.8', 'c' => 'monthly' ),
	);

	foreach ( $static_pages as $path => $meta ) {
		echo "  <url>\n";
		echo "    <loc>" . esc_url( home_url( $path ) ) . "</loc>\n";
		echo "    <lastmod>{$today}</lastmod>\n";
		echo "    <changefreq>{$meta['c']}</changefreq>\n";
		echo "    <priority>{$meta['p']}</priority>\n";
		echo "  </url>\n";
	}

	// 2. Silent Brands Hub + Individual Brand Landing Pages
	echo "  <url>\n";
	echo "    <loc>" . esc_url( home_url( '/brands/' ) ) . "</loc>\n";
	echo "    <lastmod>{$today}</lastmod>\n";
	echo "    <changefreq>weekly</changefreq>\n";
	echo "    <priority>0.85</priority>\n";
	echo "  </url>\n";

	if ( function_exists( 'varner_get_brands' ) ) {
		foreach ( array_keys( varner_get_brands() ) as $brand_slug ) {
			echo "  <url>\n";
			echo "    <loc>" . esc_url( home_url( '/brands/' . $brand_slug . '/' ) ) . "</loc>\n";
			echo "    <lastmod>{$today}</lastmod>\n";
			echo "    <changefreq>weekly</changefreq>\n";
			echo "    <priority>0.85</priority>\n";
			echo "  </url>\n";
		}
	}

	// 3. Single Equipment Custom Posts
	$equipment_query = new WP_Query( array(
		'post_type'      => array( 'equipment', 'varner_equipment' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	if ( $equipment_query->have_posts() ) {
		foreach ( $equipment_query->posts as $post_id ) {
			$permalink = get_permalink( $post_id );
			$mod_date  = get_the_modified_date( 'Y-m-d', $post_id ) ?: $today;
			echo "  <url>\n";
			echo "    <loc>" . esc_url( $permalink ) . "</loc>\n";
			echo "    <lastmod>{$mod_date}</lastmod>\n";
			echo "    <changefreq>daily</changefreq>\n";
			echo "    <priority>0.80</priority>\n";
			echo "  </url>\n";
		}
	}

	echo '</urlset>';
	exit;
}
