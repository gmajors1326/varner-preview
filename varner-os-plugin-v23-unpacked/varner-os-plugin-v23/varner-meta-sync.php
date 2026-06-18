<?php
/**
 * Varner OS — Meta Catalog Sync Logging
 *
 * Extracted from varner-os-plugin-v23.php. Loaded via require_once in that file.
 * All hooks fire at runtime after both files are loaded.
 * Depends on varner_os_schedule_catalog_regeneration() in varner-facebook-pwa.php.
 */

defined('ABSPATH') || exit;

// â”€â”€â”€ Meta Catalog Sync Logging â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function varner_os_log_meta_sync(string $message, string $type = 'success'): void {
    $logs = get_option('varner_meta_sync_logs', array());
    if (!is_array($logs)) {
        $logs = array();
    }
    
    // Prevent spam if the exact same message was logged within the last 5 seconds
    if (!empty($logs) && $logs[0]['message'] === $message && (time() - strtotime($logs[0]['created_at'])) < 5) {
        return;
    }
    
    array_unshift($logs, array(
        'message'    => $message,
        'type'       => $type,
        'created_at' => current_time('mysql'),
    ));
    
    $logs = array_slice($logs, 0, 50);
    update_option('varner_meta_sync_logs', $logs);
}

// Hook into ACF save post to track updates to equipment price, gallery, or general info
add_action('acf/save_post', 'varner_os_acf_save_meta_sync_log', 20);
function varner_os_acf_save_meta_sync_log($post_id): void {
    if (get_post_type($post_id) !== 'equipment') {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }

    $title = get_the_title($post_id);
    $make = get_field('make', $post_id) ?: '';
    $model = get_field('model', $post_id) ?: '';
    $display_name = trim("$make $model");
    if (empty($display_name)) {
        $display_name = $title;
    }

    $price = get_field('price', $post_id);
    $gallery = get_field('gallery', $post_id);
    
    $facebook_sync = get_field('facebook_sync', $post_id);
    $facebook_sync = ($facebook_sync !== null && $facebook_sync !== '') ? (bool)$facebook_sync : true;

    $prev_key = 'varner_prev_post_' . $post_id;
    $prev_data = get_transient($prev_key);
    
    $current_data = array(
        'price' => $price,
        'gallery' => $gallery,
        'facebook_sync' => $facebook_sync,
    );
    
    set_transient($prev_key, $current_data, 300);
    
    if ($prev_data) {
        $prev_sync = isset($prev_data['facebook_sync']) ? (bool)$prev_data['facebook_sync'] : true;
        if ($prev_sync !== $facebook_sync) {
            if ($facebook_sync) {
                varner_os_log_meta_sync("Inventory Synced: {$display_name}");
            } else {
                varner_os_log_meta_sync("Inventory Unsynced: {$display_name}", 'warning');
            }
        } elseif ($prev_data['price'] != $current_data['price']) {
            varner_os_log_meta_sync("Price Sync: {$display_name}");
        } elseif (wp_json_encode($prev_data['gallery']) != wp_json_encode($current_data['gallery'])) {
            varner_os_log_meta_sync("New Media: {$display_name}");
        } else {
            varner_os_log_meta_sync("Inventory Update: {$display_name}");
        }
    } else {
        if (!$facebook_sync) {
            varner_os_log_meta_sync("Inventory Unsynced: {$display_name}", 'warning');
        } else {
            varner_os_log_meta_sync("Inventory Update: {$display_name}");
        }
    }

    // Refresh the static facebook-catalog.csv file
    varner_os_schedule_catalog_regeneration();
}

// Hook into trash post to track removals
add_action('wp_trash_post', 'varner_os_trash_meta_sync_log');
function varner_os_trash_meta_sync_log($post_id): void {
    if (get_post_type($post_id) !== 'equipment') {
        return;
    }
    $title = get_the_title($post_id);
    $make = get_field('make', $post_id) ?: '';
    $model = get_field('model', $post_id) ?: '';
    $display_name = trim("$make $model");
    if (empty($display_name)) {
        $display_name = $title;
    }
    varner_os_log_meta_sync("Inventory Removed: {$display_name}", 'warning');

    // Refresh the static facebook-catalog.csv file
    varner_os_schedule_catalog_regeneration();
}

// Hook into untrash post to refresh catalog when restored
add_action('untrash_post', 'varner_os_untrash_meta_sync_log');
function varner_os_untrash_meta_sync_log($post_id): void {
    if (get_post_type($post_id) !== 'equipment') {
        return;
    }
    $title = get_the_title($post_id);
    $make = get_field('make', $post_id) ?: '';
    $model = get_field('model', $post_id) ?: '';
    $display_name = trim("$make $model");
    if (empty($display_name)) {
        $display_name = $title;
    }
    varner_os_log_meta_sync("Inventory Restored: {$display_name}");

    // Refresh the static facebook-catalog.csv file
    varner_os_schedule_catalog_regeneration();
}

// Hook into before_delete_post to track permanent removals
add_action('before_delete_post', 'varner_os_delete_meta_sync_log');
function varner_os_delete_meta_sync_log($post_id): void {
    if (get_post_type($post_id) !== 'equipment') {
        return;
    }
    $title = get_the_title($post_id);
    $make = get_field('make', $post_id) ?: '';
    $model = get_field('model', $post_id) ?: '';
    $display_name = trim("$make $model");
    if (empty($display_name)) {
        $display_name = $title;
    }
    varner_os_log_meta_sync("Inventory Removed (Permanent): {$display_name}", 'warning');

    // Refresh the static facebook-catalog.csv file
    varner_os_schedule_catalog_regeneration();
}


// Hook into WP All Import after import completion to refresh catalog
add_action('pmxi_after_xml_import', 'varner_os_wpaip_import_complete');
function varner_os_wpaip_import_complete($import_id): void {
    if (function_exists('varner_os_schedule_catalog_regeneration')) {
        varner_os_schedule_catalog_regeneration(true);
    }
}
