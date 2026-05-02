<?php
/**
 * Varner Equipment - Backend Registration
 * CPT, ACF fields, and REST API for Varner OS
 */

// ─── 1. CUSTOM POST TYPE ────────────────────────────────────────────────────

add_action('init', 'varner_register_equipment_cpt');
function varner_register_equipment_cpt() {
    register_post_type('equipment', array(
        'labels' => array(
            'name'          => 'Equipment',
            'singular_name' => 'Equipment',
            'menu_name'     => 'Equipment',
            'add_new'       => 'Add New Unit',
            'add_new_item'  => 'Add New Equipment Unit',
            'edit_item'     => 'Edit Unit',
            'new_item'      => 'New Unit',
            'view_item'     => 'View Unit',
            'search_items'  => 'Search Inventory',
            'not_found'     => 'No equipment found',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-cart',
        'show_in_menu' => false,
        'supports'     => array('thumbnail'),
        'show_in_rest' => true,
        'rewrite'      => array('slug' => 'inventory'),
    ));
}

// ─── 2. ACF JSON SYNC ───────────────────────────────────────────────────────

add_filter('acf/settings/save_json', function() {
    return dirname(__FILE__) . '/acf-json';
});

add_filter('acf/settings/load_json', function($paths) {
    $paths[] = dirname(__FILE__) . '/acf-json';
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
});

// ─── 3. ACF FIELD GROUP ─────────────────────────────────────────────────────

if (function_exists('acf_add_local_field_group')):

acf_add_local_field_group(array(
    'key'   => 'group_varner_equipment_core',
    'title' => 'Varner OS - Core Data Bins',
    'fields' => array(

        // Identity
        array('key' => 'field_varner_year',        'label' => 'Year',          'name' => 'year',         'type' => 'text'),
        array('key' => 'field_varner_make', 'label' => 'Brand / Manufacturer', 'name' => 'make', 'type' => 'select',
            'ui' => 1, 'allow_null' => 1,
            'choices' => array(
                'Bale King'=>'Bale King','Baumalight'=>'Baumalight','Beaver Valley'=>'Beaver Valley',
                'Big Tex'=>'Big Tex','Bison'=>'Bison','Brush Chief'=>'Brush Chief',
                'CM Truck Beds'=>'CM Truck Beds','Custom Made'=>'Custom Made',
                'Danuser'=>'Danuser','Degelman'=>'Degelman','Deutz Fahr'=>'Deutz Fahr','Donahue'=>'Donahue',
                'Enorossi'=>'Enorossi','Hackett'=>'Hackett','Interstate'=>'Interstate','Krone'=>'Krone',
                'Legend'=>'Legend','Macdon'=>'Macdon','Mahindra'=>'Mahindra','Maschio'=>'Maschio',
                'Massey Ferguson'=>'Massey Ferguson','Maxon'=>'Maxon','McHale'=>'McHale','MK Martin'=>'MK Martin',
                'RC Trailers'=>'RC Trailers','Speeco'=>'Speeco','Tar River'=>'Tar River','Tidenberg'=>'Tidenberg',
                'Titan MFG'=>'Titan MFG','Triton'=>'Triton','TYM'=>'TYM','Worksaver'=>'Worksaver',
                'Other'=>'Other',
            ),
        ),
        array('key' => 'field_varner_model',       'label' => 'Model',         'name' => 'model',        'type' => 'text'),
        array('key' => 'field_varner_stock',       'label' => 'Stock Number',  'name' => 'stock_number', 'type' => 'text'),
        array('key' => 'field_varner_vin',         'label' => 'VIN / Serial',  'name' => 'vin',          'type' => 'text'),
        array('key' => 'field_varner_color',       'label' => 'Color',         'name' => 'color',        'type' => 'text'),
        array('key' => 'field_varner_length',      'label' => 'Length',         'name' => 'length',       'type' => 'text'),

        // Pricing & status
        array('key' => 'field_varner_price',       'label' => 'Retail Price',  'name' => 'price',        'type' => 'number'),
        array('key' => 'field_varner_call_for_price', 'label' => 'Call For Price', 'name' => 'call_for_price', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0),
        array('key' => 'field_varner_condition',   'label' => 'Condition',     'name' => 'condition',    'type' => 'select',
            'choices' => array('New' => 'New', 'Used' => 'Used'),
        ),
        array('key' => 'field_varner_status',      'label' => 'Stock Status',  'name' => 'stock_status', 'type' => 'select',
            'choices' => array('In Stock' => 'In Stock', 'Pending Sale' => 'Pending Sale', 'Sold' => 'Sold', 'Draft' => 'Draft'),
        ),
        array('key' => 'field_varner_category',    'label' => 'Category',      'name' => 'category',     'type' => 'select',
            'choices' => array('Compact Tractors' => 'Compact Tractors', 'Commercial Trailers' => 'Commercial Trailers', 'Utility Vehicles' => 'Utility Vehicles', 'Implements' => 'Implements'),
        ),

        // Meter & intake
        array('key' => 'field_varner_meter',       'label' => 'Meter Reading', 'name' => 'meter',        'type' => 'text'),
        array('key' => 'field_varner_meter_type',  'label' => 'Meter Type',    'name' => 'meter_type',   'type' => 'select',
            'choices' => array('Hours' => 'Hours', 'Miles' => 'Miles', 'Acres' => 'Acres'),
        ),
        array('key' => 'field_varner_intake_date', 'label' => 'Intake Date',   'name' => 'intake_date',  'type' => 'date_picker',
            'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d',
        ),

        // Featured flag
        array('key' => 'field_varner_featured', 'label' => 'Featured on Home Page', 'name' => 'featured', 'type' => 'true_false',
            'ui' => 1, 'ui_on_text' => 'Featured', 'ui_off_text' => 'Not Featured', 'default_value' => 0,
        ),

        // Visibility Toggle
        array('key' => 'field_varner_show_on_website', 'label' => 'Display on Website', 'name' => 'show_on_website', 'type' => 'true_false',
            'ui' => 1, 'ui_on_text' => 'Yes', 'ui_off_text' => 'No', 'default_value' => 1,
            'instructions' => 'If set to No, this unit will be hidden from all public-facing pages.',
        ),

        // Descriptions
        array('key' => 'field_varner_desc',        'label' => 'Public Description', 'name' => 'description',  'type' => 'wysiwyg'),
        array('key' => 'field_varner_seller_info', 'label' => 'Seller Info',        'name' => 'seller_info',  'type' => 'wysiwyg'),

        // Media gallery
        array(
            'key'           => 'field_varner_gallery',
            'label'         => 'Equipment Gallery',
            'name'          => 'gallery',
            'type'          => 'gallery',
            'return_format' => 'array',
            'preview_size'  => 'medium',
        ),

        // Implements repeater
        array(
            'key'          => 'field_varner_implements',
            'label'        => 'Implements / Attachments',
            'name'         => 'implements',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Implement',
            'sub_fields'   => array(
                array('key' => 'field_impl_title', 'label' => 'Title',       'name' => 'implement_title',       'type' => 'text'),
                array('key' => 'field_impl_price', 'label' => 'Price',       'name' => 'implement_price',       'type' => 'text'),
                array('key' => 'field_impl_desc',  'label' => 'Description', 'name' => 'implement_description', 'type' => 'textarea'),
                array(
                    'key'           => 'field_impl_image',
                    'label'         => 'Image',
                    'name'          => 'implement_image',
                    'type'          => 'image',
                    'return_format' => 'id',
                    'preview_size'  => 'medium',
                ),
            ),
        ),
    ),
    'location' => array(array(array(
        'param'    => 'post_type',
        'operator' => '==',
        'value'    => 'equipment',
    ))),
    'show_in_rest'   => 1,
    'style'          => 'seamless',
    'hide_on_screen' => array('the_content', 'excerpt'),
));

endif;

// ─── 4. ACF BLOCK ───────────────────────────────────────────────────────────

add_action('acf/init', 'varner_register_blocks');
function varner_register_blocks() {
    if (function_exists('acf_register_block_type')) {
        acf_register_block_type(array(
            'name'            => 'varner-editor',
            'title'           => 'Varner Inventory Editor',
            'description'     => 'The React-powered inventory editor.',
            'render_template' => plugin_dir_path(__FILE__) . 'blocks/varner-editor.php',
            'category'        => 'formatting',
            'icon'            => 'admin-tools',
            'keywords'        => array('varner', 'inventory', 'editor'),
            'mode'            => 'edit',
            'enqueue_assets'  => function() {
                if (function_exists('varner_enqueue_react_assets')) {
                    varner_enqueue_react_assets();
                }
            },
        ));
    }
}

// ─── 5. REST API ROUTES ─────────────────────────────────────────────────────

add_action('rest_api_init', 'varner_register_rest_routes');
function varner_register_rest_routes() {
    $ns   = 'varner/v1';
    $auth = function() { return current_user_can('edit_posts'); };

    register_rest_route($ns, '/inventory', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_inventory',  'permission_callback' => '__return_true'),
        array('methods' => 'POST', 'callback' => 'varner_api_create_unit',    'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/inventory/deleted', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_get_deleted',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/inventory/(?P<id>\d+)', array(
        array('methods' => 'PATCH',  'callback' => 'varner_api_update_unit',   'permission_callback' => $auth),
        array('methods' => 'DELETE', 'callback' => 'varner_api_soft_delete',   'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/inventory/(?P<id>\d+)/restore', array(
        'methods'             => 'POST',
        'callback'            => 'varner_api_restore_unit',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/inventory/(?P<id>\d+)/permanent', array(
        'methods'             => 'DELETE',
        'callback'            => 'varner_api_permanent_delete',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/inventory/(?P<id>\d+)/ledger', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_get_ledger',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/media', array(
        'methods'             => 'POST',
        'callback'            => 'varner_api_upload_media',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/brands', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_brands',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_save_brands', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/categories', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_categories',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_save_categories', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/sessions', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_get_sessions',
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ));

    register_rest_route($ns, '/me', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_me',
        'permission_callback' => 'is_user_logged_in',
    ));

    register_rest_route($ns, '/logout', array(
        'methods'             => 'POST',
        'callback'            => 'varner_api_logout',
        'permission_callback' => 'is_user_logged_in',
    ));
}

function varner_api_get_brands() {
    $default = array(
        'Bale King','Baumalight','Beaver Valley','Big Tex','Bison','Brush Chief',
        'CM Truck Beds','Custom Made','Danuser','Degelman','Deutz Fahr','Donahue',
        'Enorossi','Hackett','Interstate','Krone','Legend','Macdon','Mahindra',
        'Maschio','Massey Ferguson','Maxon','McHale','MK Martin','RC Trailers',
        'Speeco','Tar River','Tidenberg','Titan MFG','Triton','TYM','Worksaver',
    );
    return rest_ensure_response(get_option('varner_brands', $default));
}

function varner_api_save_brands(WP_REST_Request $request) {
    $brands = array_map('sanitize_text_field', (array) $request->get_param('brands'));
    $brands = array_values(array_unique(array_filter($brands)));
    sort($brands);
    update_option('varner_brands', $brands);
    return rest_ensure_response($brands);
}

function varner_api_get_categories() {
    $default = array('Compact Tractors', 'Commercial Trailers', 'Utility Vehicles', 'Implements');
    return rest_ensure_response(get_option('varner_categories', $default));
}

function varner_api_save_categories(WP_REST_Request $request) {
    $categories = array_map('sanitize_text_field', (array) $request->get_param('categories'));
    $categories = array_values(array_unique(array_filter($categories)));
    sort($categories);
    update_option('varner_categories', $categories);
    return rest_ensure_response($categories);
}

function varner_os_user_initials(WP_User $user) {
    $first = $user->first_name ?: '';
    $last  = $user->last_name ?: '';

    if ($first || $last) {
        return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    }

    $parts = preg_split('/\s+/', trim($user->display_name));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= substr($part, 0, 1);
        if (strlen($initials) >= 2) break;
    }
    return strtoupper($initials);
}

function varner_api_me() {
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) {
        return new WP_Error('unauthorized', 'Not logged in', array('status' => 401));
    }

    return rest_ensure_response(array(
        'id'           => $user->ID,
        'display_name' => $user->display_name,
        'first_name'   => $user->first_name,
        'last_name'    => $user->last_name,
        'initials'     => varner_os_user_initials($user),
        'roles'        => $user->roles,
    ));
}

function varner_api_logout() {
    if (!is_user_logged_in()) {
        return new WP_Error('unauthorized', 'Not logged in', array('status' => 401));
    }

    // Record logout before the session is cleared
    if (function_exists('varner_os_record_logout')) {
        varner_os_record_logout();
    }

    wp_logout();
    return rest_ensure_response(array('success' => true));
}

// ─── Audit helpers ──────────────────────────────────────────────────────────

function varner_os_current_actor() {
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) return null;

    return array(
        'id'           => $user->ID,
        'display_name' => $user->display_name,
        'initials'     => varner_os_user_initials($user),
    );
}

function varner_os_request_id(WP_REST_Request $request) {
    $rid = $request->get_header('x-request-id');
    if (!$rid) {
        $rid = $request->get_param('request_id');
    }
    $rid = sanitize_text_field((string) $rid);
    return $rid ? substr($rid, 0, 64) : '';
}

function varner_os_update_last_meta($post_id, $action, $actor) {
    if (!$actor) return;
    update_post_meta($post_id, '_varner_last_action', $action);
    update_post_meta($post_id, '_varner_last_actor_name', $actor['display_name']);
    update_post_meta($post_id, '_varner_last_actor_initials', $actor['initials']);
    update_post_meta($post_id, '_varner_last_action_at', current_time('mysql'));
}

function varner_os_log_ledger($post_id, $action, $summary, $details = array(), $request_id = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_inventory_ledger';
    $actor = varner_os_current_actor();

    if ($request_id) {
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE request_id = %s LIMIT 1", $request_id));
        if ($existing) {
            return intval($existing);
        }
    }

    $wpdb->insert(
        $table,
        array(
            'post_id'      => intval($post_id),
            'action'       => sanitize_text_field($action),
            'user_id'      => $actor ? intval($actor['id']) : null,
            'display_name' => $actor ? $actor['display_name'] : null,
            'initials'     => $actor ? $actor['initials'] : null,
            'summary'      => sanitize_text_field(substr((string) $summary, 0, 255)),
            'details'      => wp_json_encode($details),
            'request_id'   => $request_id ?: null,
            'created_at'   => current_time('mysql'),
        ),
        array('%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    varner_os_update_last_meta($post_id, $action, $actor);

    return $wpdb->insert_id;
}

function varner_os_diff_unit($before, $after) {
    $diff = array();
    $fields = array_keys(varner_get_equipment_fields_config());
    $fields[] = 'title';
    $fields[] = 'show_on_website';
    $fields[] = 'stock_status';

    foreach ($fields as $key) {
        if (!array_key_exists($key, $before) || !array_key_exists($key, $after)) continue;
        if (is_array($before[$key]) || is_array($after[$key])) continue;
        if ($before[$key] == $after[$key]) continue;
        $diff[$key] = array('from' => $before[$key], 'to' => $after[$key]);
    }

    return $diff;
}

function varner_os_diff_summary($diff) {
    if (empty($diff)) return 'updated unit';
    $parts = array();
    foreach ($diff as $field => $change) {
        $parts[] = sprintf('%s: %s -> %s', $field, $change['from'], $change['to']);
        if (count($parts) >= 3) break;
    }
    return implode('; ', $parts);
}

// ─── 6. HELPERS ─────────────────────────────────────────────────────────────

/**
 * Centralized list of equipment fields for formatting and saving.
 */
function varner_get_equipment_fields_config() {
    return array(
        'year'           => array('type' => 'text'),
        'make'           => array('type' => 'text'),
        'model'          => array('type' => 'text'),
        'stock_number'   => array('type' => 'text'),
        'vin'            => array('type' => 'text'),
        'price'          => array('type' => 'number'),
        'call_for_price' => array('type' => 'bool'),
        'condition'      => array('type' => 'text', 'default' => 'New'),
        'stock_status'   => array('type' => 'text', 'default' => 'Draft'),
        'category'       => array('type' => 'text'),
        'color'          => array('type' => 'text'),
        'length'         => array('type' => 'text'),
        'meter'          => array('type' => 'text'),
        'meter_type'     => array('type' => 'text', 'default' => 'Hours'),
        'intake_date'    => array('type' => 'text'),
        'description'    => array('type' => 'wysiwyg'),
        'seller_info'    => array('type' => 'wysiwyg'),
        'featured'       => array('type' => 'bool'),
        'show_on_website'=> array('type' => 'bool', 'default' => true),
    );
}

function varner_format_unit($post_id) {
    $post = get_post($post_id);
    if (!$post) return null;

    $config = varner_get_equipment_fields_config();
    $data   = array(
        'id'    => $post_id,
        'title' => $post->post_title,
    );

    foreach ($config as $key => $meta) {
        $val = get_field($key, $post_id);
        
        if ($meta['type'] === 'bool') {
            $data[$key] = (bool) ( $val !== false ? $val : ($meta['default'] ?? false) );
        } elseif ($meta['type'] === 'number') {
            $data[$key] = (string) ($val ?? '');
        } else {
            $data[$key] = $val ?: ($meta['default'] ?? '');
        }
    }

    // Gallery
    $gallery = get_field('gallery', $post_id);
    $data['images']    = array();
    $data['image_ids'] = array();
    if (!empty($gallery)) {
        foreach ($gallery as $img) {
            if (is_array($img)) {
                $data['images'][]    = $img['url'];
                $data['image_ids'][]  = $img['ID'];
            } elseif (is_numeric($img)) {
                $data['images'][]    = wp_get_attachment_url($img);
                $data['image_ids'][]  = intval($img);
            }
        }
    }

    // Implements
    $raw = get_field('implements', $post_id);
    $data['implements'] = array();
    if (!empty($raw)) {
        foreach ($raw as $imp) {
            $img_url = '';
            $img_id  = 0;
            if (!empty($imp['implement_image'])) {
                $img_url = is_array($imp['implement_image']) ? $imp['implement_image']['url'] : wp_get_attachment_url($imp['implement_image']);
                $img_id  = is_array($imp['implement_image']) ? $imp['implement_image']['ID'] : intval($imp['implement_image']);
            }
            $data['implements'][] = array(
                'title'       => $imp['implement_title']       ?? '',
                'price'       => $imp['implement_price']       ?? '',
                'description' => $imp['implement_description'] ?? '',
                'image'       => $img_url,
                'image_id'    => $img_id,
            );
        }
    }

    $data['deleted_at'] = get_post_meta($post_id, '_varner_deleted_at', true);
    $data['last_action'] = get_post_meta($post_id, '_varner_last_action', true);
    $data['last_actor_name'] = get_post_meta($post_id, '_varner_last_actor_name', true);
    $data['last_actor_initials'] = get_post_meta($post_id, '_varner_last_actor_initials', true);
    $data['last_action_at'] = get_post_meta($post_id, '_varner_last_action_at', true);
    return $data;
}

function varner_save_unit_fields($post_id, $data) {
    $config = varner_get_equipment_fields_config();

    foreach ($config as $key => $meta) {
        if (!array_key_exists($key, $data)) continue;

        $val = $data[$key];
        if ($meta['type'] === 'bool') {
            update_field($key, (bool) $val, $post_id);
        } elseif ($meta['type'] === 'number') {
            update_field($key, floatval($val), $post_id);
        } elseif ($meta['type'] === 'wysiwyg') {
            update_field($key, wp_kses_post($val), $post_id);
        } else {
            update_field($key, sanitize_text_field($val), $post_id);
        }
    }

    // Gallery
    if (array_key_exists('image_ids', $data)) {
        $ids = array_map('intval', (array) $data['image_ids']);
        update_field('gallery', $ids, $post_id);
        if (!empty($ids)) {
            set_post_thumbnail($post_id, $ids[0]);
        } else {
            delete_post_thumbnail($post_id);
        }
    }

    // Implements
    if (array_key_exists('implements', $data)) {
        $rows = array();
        foreach ((array) $data['implements'] as $imp) {
            $rows[] = array(
                'implement_title'       => sanitize_text_field($imp['title']       ?? ''),
                'implement_price'       => sanitize_text_field($imp['price']       ?? ''),
                'implement_description' => sanitize_textarea_field($imp['description'] ?? ''),
                'implement_image'       => intval($imp['image_id'] ?? 0),
            );
        }
        update_field('implements', $rows, $post_id);
    }
}

// ─── 7. ROUTE HANDLERS ──────────────────────────────────────────────────────

function varner_api_get_inventory() {
    $args = array(
        'post_type'      => 'equipment',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // If user is NOT an admin/editor, only show visible units
    if (!current_user_can('edit_posts')) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => 'show_on_website',
                'value'   => '1',
                'compare' => '==',
            ),
            // Also include if the field hasn't been set yet (defaults to true)
            array(
                'key'     => 'show_on_website',
                'compare' => 'NOT EXISTS',
            ),
        );
    }

    $posts = get_posts($args);
    return rest_ensure_response(array_map(function($p) { return varner_format_unit($p->ID); }, $posts));
}

function varner_api_get_deleted() {
    $posts = get_posts(array(
        'post_type'      => 'equipment',
        'post_status'    => 'trash',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    return rest_ensure_response(array_map(function($p) { return varner_format_unit($p->ID); }, $posts));
}

function varner_api_create_unit(WP_REST_Request $request) {
    $data    = $request->get_json_params();
    $post_id = wp_insert_post(array(
        'post_title'  => sanitize_text_field($data['title'] ?? 'Untitled Unit'),
        'post_type'   => 'equipment',
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', $post_id->get_error_message(), array('status' => 500));
    }
    varner_save_unit_fields($post_id, $data);
    $unit = varner_format_unit($post_id);

    $rid = varner_os_request_id($request);
    varner_os_log_ledger($post_id, 'create', 'created unit', array('after' => $unit), $rid);

    return rest_ensure_response($unit);
}

function varner_api_update_unit(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    $data    = $request->get_json_params();
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Unit not found.', array('status' => 404));
    }
    $before = varner_format_unit($post_id);
    if (isset($data['title'])) {
        wp_update_post(array('ID' => $post_id, 'post_title' => sanitize_text_field($data['title'])));
    }
    varner_save_unit_fields($post_id, $data);
    $after = varner_format_unit($post_id);

    $diff = varner_os_diff_unit($before ?: array(), $after ?: array());
    $summary = $before ? varner_os_diff_summary($diff) : 'updated unit';
    $rid = varner_os_request_id($request);
    varner_os_log_ledger($post_id, 'update', $summary, array('diff' => $diff, 'before' => $before, 'after' => $after), $rid);

    return rest_ensure_response($after);
}

function varner_api_soft_delete(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Unit not found.', array('status' => 404));
    }
    update_post_meta($post_id, '_varner_deleted_at', current_time('c'));
    wp_trash_post($post_id);
    $rid = varner_os_request_id($request);
    varner_os_log_ledger($post_id, 'delete', 'soft delete', array('deleted_at' => current_time('mysql')), $rid);
    return rest_ensure_response(array('success' => true, 'id' => $post_id));
}

function varner_api_restore_unit(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    wp_untrash_post($post_id);
    delete_post_meta($post_id, '_varner_deleted_at');
    $rid = varner_os_request_id($request);
    varner_os_log_ledger($post_id, 'restore', 'restore unit', array(), $rid);
    return rest_ensure_response(varner_format_unit($post_id));
}

function varner_api_permanent_delete(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    wp_delete_post($post_id, true);
    $rid = varner_os_request_id($request);
    varner_os_log_ledger($post_id, 'permanent_delete', 'permanent delete', array(), $rid);
    return rest_ensure_response(array('success' => true, 'id' => $post_id));
}

function varner_api_upload_media(WP_REST_Request $request) {
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded.', array('status' => 400));
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('file', 0);
    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_failed', $attachment_id->get_error_message(), array('status' => 500));
    }
    return rest_ensure_response(array(
        'id'  => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
    ));
}

function varner_api_get_ledger(WP_REST_Request $request) {
    global $wpdb;
    $post_id = intval($request->get_param('id'));
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'equipment') {
        return new WP_Error('not_found', 'Unit not found.', array('status' => 404));
    }

    $page = max(1, intval($request->get_param('page') ?: 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?: 20)));
    $offset = ($page - 1) * $per_page;

    $table = $wpdb->prefix . 'varner_inventory_ledger';

    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT id, action, user_id, display_name, initials, summary, details, request_id, created_at FROM {$table} WHERE post_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $post_id,
        $per_page,
        $offset
    ), ARRAY_A);

    $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE post_id = %d", $post_id));

    return rest_ensure_response(array(
        'items'    => $items,
        'total'    => intval($total),
        'page'     => $page,
        'per_page' => $per_page,
    ));
}

function varner_api_get_sessions(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';

    $active_only = filter_var($request->get_param('active_only'), FILTER_VALIDATE_BOOLEAN);
    $user_id     = intval($request->get_param('user_id'));
    $page        = max(1, intval($request->get_param('page') ?: 1));
    $per_page    = min(100, max(1, intval($request->get_param('per_page') ?: 20)));
    $offset      = ($page - 1) * $per_page;

    $wheres = array();
    $params = array();
    if ($active_only) {
        $wheres[] = 'logout_at IS NULL';
    }
    if ($user_id > 0) {
        $wheres[] = 'user_id = %d';
        $params[] = $user_id;
    }
    $where_sql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

    $sql = "SELECT id, user_id, session_token, login_at, logout_at, ip, user_agent, ended_reason FROM {$table} {$where_sql} ORDER BY login_at DESC LIMIT %d OFFSET %d";
    $items = $wpdb->get_results($wpdb->prepare($sql, array_merge($params, array($per_page, $offset))), ARRAY_A);

    $count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
    $total = $wpdb->get_var($params ? $wpdb->prepare($count_sql, $params) : $count_sql);

    // Attach user display_name/initials for convenience
    foreach ($items as &$row) {
        $u = $row['user_id'] ? get_user_by('id', $row['user_id']) : null;
        $row['display_name'] = $u ? $u->display_name : '';
        $row['initials']     = $u ? varner_os_user_initials($u) : '';
    }

    return rest_ensure_response(array(
        'items'    => $items,
        'total'    => intval($total),
        'page'     => $page,
        'per_page' => $per_page,
    ));
}
