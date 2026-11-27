<?php
/**
 * Varner Equipment - Asynchronous IndexNow Protocol Integration
 * Location: wp-content/themes/varner-equipment-theme-v23-lite-4/inc/indexing-api.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fixed 32-character hex key for IndexNow verification
if ( ! defined( 'VARNER_INDEXNOW_KEY' ) ) {
	define( 'VARNER_INDEXNOW_KEY', 'd4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9' );
}

/**
 * Trigger an asynchronous non-blocking IndexNow ping when an equipment post is published or updated.
 *
 * @param int $post_id
 */
function varner_trigger_indexnow_ping( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( get_post_type( $post_id ) !== 'equipment' ) {
		return;
	}

	if ( get_post_status( $post_id ) !== 'publish' ) {
		return;
	}

	$url = get_permalink( $post_id );
	if ( ! $url ) {
		return;
	}

	$host        = wp_parse_url( home_url(), PHP_URL_HOST );
	$key_location = home_url( '/' . VARNER_INDEXNOW_KEY . '.txt' );

	$payload = array(
		'host'        => $host,
		'key'         => VARNER_INDEXNOW_KEY,
		'keyLocation' => $key_location,
		'urlList'     => array( $url ),
	);

	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'     => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
			'blocking' => false,
			'timeout'  => 3,
		)
	);
}

add_action( 'publish_equipment', 'varner_trigger_indexnow_ping', 10, 1 );
add_action( 'save_post_equipment', 'varner_trigger_indexnow_ping', 10, 1 );

/**
 * Serve the IndexNow key file at /<key>.txt
 */
function varner_serve_indexnow_key_file() {
	if ( get_query_var( 'indexnow_key' ) ) {
		$requested_key = sanitize_text_field( get_query_var( 'indexnow_key' ) );
		if ( $requested_key === VARNER_INDEXNOW_KEY ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo VARNER_INDEXNOW_KEY;
			exit;
		}
	}
}
add_action( 'template_redirect', 'varner_serve_indexnow_key_file' );
