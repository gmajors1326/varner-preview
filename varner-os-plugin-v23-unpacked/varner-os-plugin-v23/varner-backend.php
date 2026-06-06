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

/**
 * Register Video Custom Post Type and Taxonomy
 */
if ( ! function_exists( 'varner_register_video_cpt' ) ) {
    function varner_register_video_cpt() {
        $labels = array(
            "name" => __( "Videos", "varner-os" ),
            "singular_name" => __( "Video", "varner-os" ),
            "menu_name" => __( "Videos", "varner-os" ),
            "all_items" => __( "All Videos", "varner-os" ),
            "add_new" => __( "Add New Video", "varner-os" ),
            "add_new_item" => __( "Add New Video", "varner-os" ),
            "edit_item" => __( "Edit Video", "varner-os" ),
            "new_item" => __( "New Video", "varner-os" ),
            "view_item" => __( "View Video", "varner-os" ),
            "search_items" => __( "Search Videos", "varner-os" ),
            "not_found" => __( "No Videos Found", "varner-os" ),
        );

        $args = array(
            "label" => __( "Videos", "varner-os" ),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive" => false,
            "show_in_menu" => false,
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
            "name" => __( "Video Categories", "varner-os" ),
            "singular_name" => __( "Video Category", "varner-os" ),
        );

        $tax_args = array(
            "label" => __( "Video Categories", "varner-os" ),
            "labels" => $tax_labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => false,
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
}
add_action( "init", "varner_register_video_cpt" );

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
                'Big Tex'=>'Big Tex','Bison'=>'Bison','Branson'=>'Branson','Brush Chief'=>'Brush Chief',
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
            'choices' => array(
                'Compact Tractors' => 'Compact Tractors', 
                'Utility Tractors' => 'Utility Tractors',
                'Tractors' => 'Tractors',
                'Commercial Trailers' => 'Commercial Trailers', 
                'Dump Trailers' => 'Dump Trailers',
                'Flatbed Trailers' => 'Flatbed Trailers',
                'Utility Trailers' => 'Utility Trailers',
                'Horse Trailers' => 'Horse Trailers',
                'Livestock Trailers' => 'Livestock Trailers',
                'Trailers' => 'Trailers',
                'Utility Vehicles' => 'Utility Vehicles', 
                'Golf Carts' => 'Golf Carts',
                'Implements' => 'Implements',
                'Attachments' => 'Attachments',
                'Loaders' => 'Loaders',
                'Hay Equipment' => 'Hay Equipment',
                'Balers' => 'Balers',
                'Rakes' => 'Rakes',
                'Tedders' => 'Tedders',
                'Snow Removal' => 'Snow Removal',
                'Misc' => 'Misc',
                'Other' => 'Other'
            ),
        ),
        array('key' => 'field_varner_subcategory',    'label' => 'Subcategory',  'name' => 'subcategory',   'type' => 'text'),
        array('key' => 'field_varner_sub_subcategory','label' => 'Sub-Subcategory', 'name' => 'sub_subcategory', 'type' => 'text'),

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

    register_rest_route($ns, '/subcategories', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_subcategories',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_save_subcategories', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/sub-subcategories', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_sub_subcategories',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_save_sub_subcategories', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/videos', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_videos',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_create_video', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/videos/(?P<id>\d+)', array(
        array('methods' => 'PATCH',  'callback' => 'varner_api_update_video', 'permission_callback' => $auth),
        array('methods' => 'DELETE', 'callback' => 'varner_api_delete_video', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/video-categories', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_video_categories',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_create_video_category', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/video-categories/(?P<id>\d+)', array(
        array('methods' => 'DELETE', 'callback' => 'varner_api_delete_video_category', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/sessions', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_get_sessions',
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ));

    register_rest_route($ns, '/ledger', array(
        'methods'             => 'GET',
        'callback'            => 'varner_api_get_global_ledger',
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

    register_rest_route($ns, '/settings', array(
        array('methods' => 'GET',  'callback' => 'varner_api_get_settings',  'permission_callback' => $auth),
        array('methods' => 'POST', 'callback' => 'varner_api_save_settings', 'permission_callback' => $auth),
    ));

    register_rest_route($ns, '/settings/preview', array(
        'methods'             => 'POST',
        'callback'            => 'varner_api_save_preview_settings',
        'permission_callback' => $auth,
    ));

    register_rest_route($ns, '/mobile/token', array(
        'methods'             => 'POST',
        'callback'            => 'varner_api_generate_mobile_token',
        'permission_callback' => $auth,
    ));
}

function varner_api_get_brands() {
    $default = array(
        'Bale King','Baumalight','Beaver Valley','Big Tex','Bison','Branson','Brush Chief',
        'CM Truck Beds','Custom Made','Danuser','Degelman','Deutz Fahr','Donahue',
        'Enorossi','Hackett','Interstate','Krone','Legend','Macdon','Mahindra',
        'Maschio','Massey Ferguson','Maxon','McHale','MK Martin','RC Trailers',
        'Speeco','Tar River','Tidenberg','Titan MFG','Triton','TYM','Worksaver',
    );
    return rest_ensure_response(get_option('varner_brands', $default));
}

function varner_api_save_list(string $param, string $option, WP_REST_Request $request) {
    $payload = $request->get_json_params();
    if (!is_array($payload)) {
        $payload = array();
    }

    // Full list save (preferred)
    $raw_items = $request->get_param($param);

    // Flexible fallbacks for legacy/single-item "Add" buttons
    if (null === $raw_items) {
        $singular = rtrim($param, 's');
        foreach (array($singular, 'item', 'name', 'value') as $alt_key) {
            if ($request->get_param($alt_key) !== null) {
                $raw_items = $request->get_param($alt_key);
                break;
            }
            if (array_key_exists($alt_key, $payload)) {
                $raw_items = $payload[$alt_key];
                break;
            }
        }
    }

    // If a single string is provided, append it to existing list instead of replacing everything
    if (is_string($raw_items)) {
        $existing = get_option($option, array());
        if (!is_array($existing)) {
            $existing = array();
        }
        $raw_items = array_merge($existing, array($raw_items));
    }

    $items = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) $raw_items))));
    sort($items);
    update_option($option, $items);
    return rest_ensure_response($items);
}

function varner_api_save_brands(WP_REST_Request $r)     { return varner_api_save_list('brands',     'varner_brands',     $r); }

function varner_api_get_categories() {
    $default = array('Compact Tractors', 'Commercial Trailers', 'Utility Vehicles', 'Implements');
    return rest_ensure_response(get_option('varner_categories', $default));
}

function varner_api_save_categories(WP_REST_Request $r) { return varner_api_save_list('categories', 'varner_categories', $r); }

function varner_api_get_subcategories() {
    return rest_ensure_response(get_option('varner_subcategories', array()));
}

function varner_api_save_subcategories(WP_REST_Request $r) { return varner_api_save_list('subcategories', 'varner_subcategories', $r); }

function varner_api_get_sub_subcategories() {
    return rest_ensure_response(get_option('varner_sub_subcategories', array()));
}

function varner_api_save_sub_subcategories(WP_REST_Request $r) { return varner_api_save_list('sub-subcategories', 'varner_sub_subcategories', $r); }

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
        'subcategory'     => array('type' => 'text'),
        'sub_subcategory' => array('type' => 'text'),
        'color'          => array('type' => 'text'),
        'length'         => array('type' => 'text'),
        'meter'          => array('type' => 'text'),
        'meter_type'     => array('type' => 'text', 'default' => 'Hours'),
        'intake_date'    => array('type' => 'text'),
        'description'    => array('type' => 'wysiwyg'),
        'seller_info'    => array('type' => 'wysiwyg'),
        'featured'       => array('type' => 'bool'),
        'show_on_website'=> array('type' => 'bool', 'default' => true),
        'has_attachments'=> array('type' => 'bool', 'default' => false),
        'attachment_details' => array('type' => 'text'),
        'drive'          => array('type' => 'text'),
    );
}

function varner_format_unit($post_id) {
    $post = get_post($post_id);
    if (!$post) return null;

    $config = varner_get_equipment_fields_config();
    $fields = function_exists('get_fields') ? get_fields($post_id) : array();
    
    $data   = array(
        'id'    => $post_id,
        'title' => $post->post_title,
    );

    foreach ($config as $key => $meta) {
        $val = isset($fields[$key]) ? $fields[$key] : null;
        
        if ($meta['type'] === 'bool') {
            $data[$key] = (bool) ( $val !== false && $val !== null ? $val : ($meta['default'] ?? false) );
        } elseif ($meta['type'] === 'number') {
            $data[$key] = (string) ($val ?? '');
        } else {
            $raw = $val ?: ($meta['default'] ?? '');
            if ($meta['type'] === 'wysiwyg' && $raw && strip_tags($raw) === $raw) {
                $raw = nl2br(esc_html($raw));
            }
            $data[$key] = $raw;
        }
    }

    // Gallery (already in $fields)
    $gallery = isset($fields['gallery']) ? $fields['gallery'] : array();
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

    // Implements (already in $fields)
    $raw_implements = isset($fields['implements']) ? $fields['implements'] : array();
    $data['implements'] = array();
    if (!empty($raw_implements)) {
        foreach ($raw_implements as $imp) {
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

    $data['created_at'] = $post->post_date;
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
            $clean = wp_kses_post($val);
            if (strip_tags($clean) === $clean) {
                $clean = nl2br($clean);
            }
            update_field($key, $clean, $post_id);
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
        'post_status'    => current_user_can('edit_posts') ? array('publish', 'draft') : 'publish',
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
                'compare' => '=',
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
    $status  = (isset($data['stock_status']) && $data['stock_status'] === 'Draft') ? 'draft' : 'publish';
    $post_id = wp_insert_post(array(
        'post_title'  => sanitize_text_field($data['title'] ?? 'Untitled Unit'),
        'post_type'   => 'equipment',
        'post_status' => $status,
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
    
    $post_updates = array('ID' => $post_id);
    if (isset($data['title'])) {
        $post_updates['post_title'] = sanitize_text_field($data['title']);
    }
    if (isset($data['stock_status'])) {
        $post_updates['post_status'] = ($data['stock_status'] === 'Draft') ? 'draft' : 'publish';
    }
    if (count($post_updates) > 1) {
        wp_update_post($post_updates);
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
    
    // Explicitly update post status to 'publish' so it is visible in the API queries
    wp_update_post(array(
        'ID'          => $post_id,
        'post_status' => 'publish',
    ));
    
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

    // ─── On-the-fly verification of all active sessions ────────────────────
    $active_sessions = $wpdb->get_results("SELECT id, user_id, session_token, login_at, last_activity_at FROM {$table} WHERE logout_at IS NULL");

    if ($active_sessions) {
        foreach ($active_sessions as $sess) {
            $is_valid = true;
            $reason = 'expired';
            $is_mobile_token = (strlen($sess->session_token) === 16 && ctype_xdigit($sess->session_token));

            if (empty($sess->session_token)) {
                $is_valid = false;
                $reason = 'expired';
            } elseif ($is_mobile_token) {
                $stored_user_id = get_transient('varner_mobile_token_' . $sess->session_token);
                if (!$stored_user_id) {
                    $is_valid = false;
                    $reason = 'expired';
                } else {
                    $now = current_time('timestamp');
                    $last_act = $sess->last_activity_at ? strtotime($sess->last_activity_at) : strtotime($sess->login_at);
                    if ($now - $last_act > 1800) { // 30 mins timeout
                        $is_valid = false;
                        $reason = 'timeout';
                        delete_transient('varner_mobile_token_' . $sess->session_token);
                    }
                }
            } else {
                $manager = WP_Session_Tokens::get_instance($sess->user_id);
                if (!$manager->verify($sess->session_token)) {
                    $is_valid = false;
                    $reason = 'expired';
                } else {
                    $now = current_time('timestamp');
                    $last_act = $sess->last_activity_at ? strtotime($sess->last_activity_at) : strtotime($sess->login_at);
                    if ($now - $last_act > 7200) { // 2 hours timeout
                        $is_valid = false;
                        $reason = 'timeout';
                        $manager->destroy($sess->session_token);
                    }
                }
            }

            if (!$is_valid) {
                $wpdb->update(
                    $table,
                    array(
                        'logout_at'    => current_time('mysql'),
                        'ended_reason' => $reason
                    ),
                    array('id' => $sess->id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }
    }

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

    $sql = "SELECT id, user_id, session_token, login_at, logout_at, last_activity_at, ip, user_agent, ended_reason FROM {$table} {$where_sql} ORDER BY login_at DESC LIMIT %d OFFSET %d";
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

function varner_api_get_global_ledger(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'varner_inventory_ledger';

    $page        = max(1, intval($request->get_param('page') ?: 1));
    $per_page    = min(100, max(1, intval($request->get_param('per_page') ?: 20)));
    $offset      = ($page - 1) * $per_page;

    $wheres = array();
    $params = array();

    $action = sanitize_text_field($request->get_param('action'));
    if ($action) {
        $wheres[] = 'l.action = %s';
        $params[] = $action;
    }

    $user_id = intval($request->get_param('user_id'));
    if ($user_id > 0) {
        $wheres[] = 'l.user_id = %d';
        $params[] = $user_id;
    }

    $where_sql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

    $sql = "SELECT l.id, l.post_id, l.action, l.user_id, l.display_name, l.initials, l.summary, l.details, l.request_id, l.created_at, p.post_title 
            FROM {$table} l 
            LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID 
            {$where_sql} 
            ORDER BY l.created_at DESC 
            LIMIT %d OFFSET %d";

    $items = $wpdb->get_results($wpdb->prepare($sql, array_merge($params, array($per_page, $offset))), ARRAY_A);

    $count_sql = "SELECT COUNT(*) FROM {$table} l {$where_sql}";
    $total = $wpdb->get_var($params ? $wpdb->prepare($count_sql, $params) : $count_sql);

    foreach ($items as &$row) {
        if ($row['details']) {
            $row['details'] = json_decode($row['details'], true);
        }
        // If post_title is empty or missing, fetch stock number as fallback
        if (empty($row['post_title']) && $row['post_id'] > 0) {
            $stock = get_post_meta($row['post_id'], '_stock_number', true);
            if ($stock) {
                $row['post_title'] = 'Unit Stock #' . $stock;
            } else {
                $row['post_title'] = 'Deleted Unit (ID #' . $row['post_id'] . ')';
            }
        }
    }

    return rest_ensure_response(array(
        'items'    => $items,
        'total'    => intval($total),
        'page'     => $page,
        'per_page' => $per_page,
    ));
}

function varner_backend_get_settings_defaults() {
    return array(
        'hero_title'                 => "Beyond the <br />\n<span class=\"text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-red-600\">Standard.</span>",
        'hero_subtitle'              => "Your trusted local source for Mahindra tractors, Big Tex trailers, Deutz-Fahr machinery, and many more to choose from. Discover heavy-duty solutions built for your toughest jobs.",
        'hero_button1_text'          => "Shop Inventory",
        'hero_button1_link'          => "/inventory",
        'hero_button2_text'          => "Book Service",
        'hero_button2_link'          => "/service-request",
        'hero_video_url'             => "",
        'support_hub_service_link'   => "/service-request",
        'support_hub_parts_link'     => "https://www.allpartsstore.com/index.htm?customernumber=CO0612",
        'support_hub_finance_link'   => "/contact",
        'youtube_tagline'            => "Varner Equipment Media",
        'youtube_title'              => "See Our Machines<br class=\"hidden sm:block\"/><span class=\"text-red-500 sm:inline block\">In Action</span>",
        'youtube_paragraph'          => "Subscribe to our YouTube channel for walkthroughs, reviews, and heavy-duty demonstrations right here in Colorado.",
        'youtube_channel_url'        => "https://www.youtube.com/@VarnerEquipment",
        'youtube_video_id'           => "goF_3TspZ6k",
        'youtube_custom_thumbnail'   => "",
        'cta_title'                  => "What's Next On<br class=\"hidden sm:block\" />\nYour To-Do List?",
        'cta_text'                   => "Varner Equipment is a family owned and operated tractor and trailer dealership. We are your one stop for equipment that you can rely on.",
        'cta_button_text'            => "Learn more",
        'cta_button_link'            => "/dealer-info/about-us",
        'about_why_choose_us_title'  => "Why Choose Us?",
        'about_why_choose_us_bullets'=> array(
            "Family Owned & Operated",
            "Expert Service Department",
            "Extensive Parts Inventory",
            "Top-Tier Equipment Brands"
        ),
        'contact_email'              => "ashley@varnerequipment.com",
        'contact_phone'              => "(970) 874-0612",
        'contact_phone_raw'          => "9708740612",
        'contact_address_line1'      => "1375 US-50",
        'contact_address_line2'      => "Delta, CO 81416",
        'contact_map_link'           => "https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9",
        'contact_map_embed_url'      => "https://www.google.com/maps?q=Varner%20Equipment%2C%201375%20US-50%2C%20Delta%2C%20CO%2081416&z=8&output=embed",
        'hours_mon_fri'              => "8am - 5pm",
        'hours_sat'                  => "9am - Noon",
        'hours_sun'                  => "Closed",
        'social_facebook'            => "https://www.facebook.com/varnerequipment",
        'social_youtube'             => "https://www.youtube.com/@VarnerEquipment",
        'social_custom_links'        => array(),
        'employment_tagline'         => 'Join The Crew',
        'employment_headline'        => 'Careers at Varner',
        'employment_intro'           => 'We are always looking for hardworking, reliable individuals to join our team in Delta, Colorado. If you have a passion for heavy equipment and a dedication to customer service, we want to hear from you.',
        'employment_jobs'            => array(
            array(
                'job_title'       => 'Heavy Equipment Mechanic',
                'job_type'        => 'Full-Time',
                'job_location'    => 'Delta, CO',
                'job_description' => 'Looking for an experienced mechanic specializing in tractors, trailers, and agricultural equipment. Must have own tools and reliable transportation.',
                'job_show_badge'  => true,
                'job_badge_text'  => 'Urgently Hiring',
            ),
            array(
                'job_title'       => 'Parts Counter Sales',
                'job_type'        => 'Full-Time',
                'job_location'    => 'Delta, CO',
                'job_description' => 'Assist customers in finding and ordering the right parts for their equipment. Previous parts or agricultural knowledge preferred.',
                'job_show_badge'  => false,
                'job_badge_text'  => '',
            ),
        ),
    );
}

/**
 * Get default theme settings values.
 */
if ( ! function_exists( 'varner_get_theme_settings_defaults' ) ) {
    function varner_get_theme_settings_defaults() {
        if (function_exists('varner_backend_get_settings_defaults')) {
            return varner_backend_get_settings_defaults();
        }
        return array();
    }
}

function varner_api_get_settings() {
    $settings = get_option('varner_theme_settings', array());
    $defaults = varner_backend_get_settings_defaults();
    
    $merged = array();
    foreach ($defaults as $key => $default_val) {
        $merged[$key] = isset($settings[$key]) ? $settings[$key] : $default_val;
    }
    
    return rest_ensure_response($merged);
}

function varner_api_save_settings(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (!is_array($params)) {
        return new WP_Error('invalid_data', 'Invalid settings data', array('status' => 400));
    }
    
    $defaults = varner_backend_get_settings_defaults();
    $sanitized = array();
    
    foreach ($defaults as $key => $default_val) {
        if (isset($params[$key])) {
            if ($key === 'employment_jobs') {
                $jobs = array();
                if (is_array($params[$key])) {
                    foreach ($params[$key] as $job) {
                        if (!is_array($job)) continue;
                        $jobs[] = array(
                            'job_title'       => sanitize_text_field($job['job_title'] ?? ''),
                            'job_type'        => sanitize_text_field($job['job_type'] ?? 'Full-Time'),
                            'job_location'    => sanitize_text_field($job['job_location'] ?? 'Delta, CO'),
                            'job_description' => sanitize_textarea_field($job['job_description'] ?? ''),
                            'job_show_badge'  => filter_var($job['job_show_badge'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'job_badge_text'  => sanitize_text_field($job['job_badge_text'] ?? ''),
                        );
                    }
                }
                $sanitized[$key] = $jobs;
            } else if ($key === 'social_custom_links') {
                $links = array();
                if (is_array($params[$key])) {
                    foreach ($params[$key] as $link) {
                        if (!is_array($link)) continue;
                        $links[] = array(
                            'platform' => sanitize_text_field($link['platform'] ?? 'custom'),
                            'url'      => esc_url_raw($link['url'] ?? ''),
                            'label'    => sanitize_text_field($link['label'] ?? ''),
                        );
                    }
                }
                $sanitized[$key] = $links;
            } else if (is_array($default_val)) {
                $sanitized[$key] = array_map('sanitize_text_field', (array)$params[$key]);
            } else if (is_bool($default_val)) {
                $sanitized[$key] = (bool)$params[$key];
            } else {
                if (in_array($key, array('hero_title', 'hero_subtitle', 'youtube_title', 'youtube_paragraph', 'cta_title', 'cta_text', 'employment_intro'), true)) {
                    $sanitized[$key] = wp_kses_post($params[$key]);
                } else {
                    $sanitized[$key] = sanitize_text_field($params[$key]);
                }
            }
        } else {
            $sanitized[$key] = $default_val;
        }
    }
    
    update_option('varner_theme_settings', $sanitized);
    return rest_ensure_response(array('success' => true, 'settings' => $sanitized));
}

function varner_api_save_preview_settings(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (!is_array($params)) {
        return new WP_Error('invalid_data', 'Invalid settings data', array('status' => 400));
    }
    
    $defaults = varner_backend_get_settings_defaults();
    $sanitized = array();
    
    foreach ($defaults as $key => $default_val) {
        if (isset($params[$key])) {
            if ($key === 'employment_jobs') {
                $jobs = array();
                if (is_array($params[$key])) {
                    foreach ($params[$key] as $job) {
                        if (!is_array($job)) continue;
                        $jobs[] = array(
                            'job_title'       => sanitize_text_field($job['job_title'] ?? ''),
                            'job_type'        => sanitize_text_field($job['job_type'] ?? 'Full-Time'),
                            'job_location'    => sanitize_text_field($job['job_location'] ?? 'Delta, CO'),
                            'job_description' => sanitize_textarea_field($job['job_description'] ?? ''),
                            'job_show_badge'  => filter_var($job['job_show_badge'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'job_badge_text'  => sanitize_text_field($job['job_badge_text'] ?? ''),
                        );
                    }
                }
                $sanitized[$key] = $jobs;
            } else if ($key === 'social_custom_links') {
                $links = array();
                if (is_array($params[$key])) {
                    foreach ($params[$key] as $link) {
                        if (!is_array($link)) continue;
                        $links[] = array(
                            'platform' => sanitize_text_field($link['platform'] ?? 'custom'),
                            'url'      => esc_url_raw($link['url'] ?? ''),
                            'label'    => sanitize_text_field($link['label'] ?? ''),
                        );
                    }
                }
                $sanitized[$key] = $links;
            } else if (is_array($default_val)) {
                $sanitized[$key] = array_map('sanitize_text_field', (array)$params[$key]);
            } else if (is_bool($default_val)) {
                $sanitized[$key] = (bool)$params[$key];
            } else {
                if (in_array($key, array('hero_title', 'hero_subtitle', 'youtube_title', 'youtube_paragraph', 'cta_title', 'cta_text', 'employment_intro'), true)) {
                    $sanitized[$key] = wp_kses_post($params[$key]);
                } else {
                    $sanitized[$key] = sanitize_text_field($params[$key]);
                }
            }
        } else {
            $sanitized[$key] = $default_val;
        }
    }
    
    update_option('varner_theme_settings_preview', $sanitized);
    return rest_ensure_response(array('success' => true, 'settings' => $sanitized));
}

// ─── MOBILE COMPANION SECURE AUTHENTICATION & TOKEN SERVICES ──────────────────

function varner_api_generate_mobile_token() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Not logged in', array('status' => 401));
    }
    
    // Generate a secure 16-character token
    $token = strtoupper(bin2hex(random_bytes(8)));
    
    // Store in transient for 30 minutes (1800 seconds)
    // Links token -> user_id
    set_transient('varner_mobile_token_' . $token, $user_id, 1800);
    
    $site_url = site_url();
    $path_prefix = parse_url($site_url, PHP_URL_PATH);
    $path_prefix = $path_prefix ? '/' . trim($path_prefix, '/') : '';
    $is_https = is_ssl() || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $protocol = $is_https ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'varnerequipment.com';
    $url = $protocol . $host . $path_prefix . '/mobile-app/?token=' . $token;

    return rest_ensure_response(array(
        'token'      => $token,
        'expires_in' => 1800,
        'url'        => $url,
    ));
}

/**
 * Bypasses cookie nonce validation for mobile token requests.
 */
add_filter('rest_authentication_errors', function($result) {
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']) || (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer/i', $_SERVER['HTTP_AUTHORIZATION']))) {
        $token = '';
        if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
            $token = sanitize_text_field($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches);
            $token = sanitize_text_field($matches[1] ?? '');
        }
        if ($token && get_transient('varner_mobile_token_' . $token)) {
            return true; // Bypass nonce error
        }
    }
    return $result;
}, 9);

/**
 * Authenticates user from X-Varner-Mobile-Token header or query arg.
 */
add_filter('determine_current_user', 'varner_authenticate_mobile_token', 15);
function varner_authenticate_mobile_token($user_id) {
    if ($user_id) {
        return $user_id;
    }
    
    $token = '';
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $token = sanitize_text_field($matches[1]);
        }
    } elseif (isset($_GET['mobile_token'])) {
        $token = sanitize_text_field($_GET['mobile_token']);
    }
    
    if (empty($token)) {
        return $user_id;
    }
    
    $stored_user_id = get_transient('varner_mobile_token_' . $token);
    if ($stored_user_id) {
        // Extend rolling session for another 30 mins
        set_transient('varner_mobile_token_' . $token, $stored_user_id, 1800);
        return intval($stored_user_id);
    }
    
    return $user_id;
}

/**
 * Tracks session activity and handles mobile session lifecycle (deduplication, auto-supersede)
 */
add_action('init', 'varner_update_session_activity');
function varner_update_session_activity() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'varner_user_sessions';

    $token = '';
    $is_mobile = false;

    // Check if it's a mobile request using token
    if (isset($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN'])) {
        $token = sanitize_text_field($_SERVER['HTTP_X_VARNER_MOBILE_TOKEN']);
        $is_mobile = true;
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $token = sanitize_text_field($matches[1]);
            $is_mobile = true;
        }
    } elseif (isset($_GET['mobile_token'])) {
        $token = sanitize_text_field($_GET['mobile_token']);
        $is_mobile = true;
    }

    // If not mobile, get standard WP session token
    if (empty($token)) {
        $token = wp_get_session_token();
    }

    if (empty($token)) {
        return;
    }

    // Check if this session already exists in DB
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT id, last_activity_at, logout_at FROM {$table} WHERE session_token = %s",
        $token
    ));

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    if ($session) {
        if ($session->logout_at !== null) {
            // Re-activate session if it is actively being used
            $wpdb->update(
                $table,
                array(
                    'logout_at'        => null,
                    'ended_reason'     => null,
                    'last_activity_at' => current_time('mysql')
                ),
                array('id' => $session->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Throttle database writes: update last_activity_at only once per 60 seconds
            $now = current_time('timestamp');
            $last_act = $session->last_activity_at ? strtotime($session->last_activity_at) : 0;
            if ($now - $last_act > 60) {
                $wpdb->update(
                    $table,
                    array('last_activity_at' => current_time('mysql')),
                    array('id' => $session->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
    } else {
        // This is a new session. If it's a mobile session, auto-supersede existing mobile sessions for this user.
        if ($is_mobile) {
            $active_mobile_sessions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, session_token FROM {$table} WHERE user_id = %d AND logout_at IS NULL AND session_token != %s",
                $user_id,
                $token
            ));

            foreach ($active_mobile_sessions as $old_sess) {
                // Confirm it is indeed a mobile session (16-char hex string)
                if (strlen($old_sess->session_token) === 16 && ctype_xdigit($old_sess->session_token)) {
                    $wpdb->update(
                        $table,
                        array(
                            'logout_at'    => current_time('mysql'),
                            'ended_reason' => 'superseded'
                        ),
                        array('id' => $old_sess->id),
                        array('%s', '%s'),
                        array('%d')
                    );
                    delete_transient('varner_mobile_token_' . $old_sess->session_token);
                }
            }
        }

        // Insert new session row
        $wpdb->insert(
            $table,
            array(
                'user_id'          => $user_id,
                'session_token'    => $token,
                'login_at'         => current_time('mysql'),
                'last_activity_at' => current_time('mysql'),
                'ip'               => $ip,
                'user_agent'       => $agent,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
}

// ─── 8. VIDEOS & VIDEO CATEGORIES REST HANDLERS ──────────────────────────────

function varner_extract_youtube_url($embed_html) {
    if (preg_match('/src="([^"]+)"/', $embed_html, $match)) {
        $src = $match[1];
        if (preg_match('/embed\/([^?&"]+)/', $src, $id_match)) {
            return 'https://www.youtube.com/watch?v=' . $id_match[1];
        }
        return $src;
    }
    return $embed_html;
}

function varner_get_youtube_embed_html($url) {
    if (strpos($url, '<iframe') !== false) {
        return $url;
    }
    
    $video_id = '';
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|win/.+/|shorte.t/.+/|user/.+/|embed/|vne/)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    
    if ($video_id) {
        return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }
    
    return $url;
}

function varner_save_video_fields($post_id, $youtube_url, $category_id) {
    $embed_html = varner_get_youtube_embed_html($youtube_url);
    if (function_exists('update_field')) {
        update_field('youtube_link', $embed_html, $post_id);
    } else {
        update_post_meta($post_id, 'youtube_link', $embed_html);
    }
    
    if ($category_id) {
        wp_set_post_terms($post_id, array(intval($category_id)), 'video_category');
    } else {
        wp_set_post_terms($post_id, array(), 'video_category');
    }
}

function varner_api_get_videos() {
    $posts = get_posts(array(
        'post_type'      => 'video',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ));

    $videos = array();
    foreach ($posts as $p) {
        $cats = wp_get_post_terms($p->ID, 'video_category');
        $cat_id = !empty($cats) ? $cats[0]->term_id : 0;
        $cat_name = !empty($cats) ? $cats[0]->name : 'Uncategorized';
        
        $videos[] = array(
            'id'            => $p->ID,
            'title'         => $p->post_title,
            'youtube_link'  => varner_extract_youtube_url(get_post_meta($p->ID, 'youtube_link', true) ?: (function_exists('get_field') ? get_field('youtube_link', $p->ID) : '')),
            'category_id'   => $cat_id,
            'category_name' => $cat_name,
        );
    }
    return rest_ensure_response($videos);
}

function varner_api_create_video(WP_REST_Request $request) {
    $data = $request->get_json_params();
    $post_id = wp_insert_post(array(
        'post_title'  => sanitize_text_field($data['title'] ?? 'Untitled Video'),
        'post_type'   => 'video',
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', $post_id->get_error_message(), array('status' => 500));
    }
    
    $youtube_link = sanitize_text_field($data['youtube_link'] ?? '');
    $category_id = intval($data['category_id'] ?? 0);
    varner_save_video_fields($post_id, $youtube_link, $category_id);
    
    return rest_ensure_response(array('success' => true, 'id' => $post_id));
}

function varner_api_update_video(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    $data = $request->get_json_params();
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Video not found.', array('status' => 404));
    }
    
    $update_args = array('ID' => $post_id);
    if (isset($data['title'])) {
        $update_args['post_title'] = sanitize_text_field($data['title']);
    }
    wp_update_post($update_args);
    
    $youtube_link = sanitize_text_field($data['youtube_link'] ?? '');
    $category_id = intval($data['category_id'] ?? 0);
    varner_save_video_fields($post_id, $youtube_link, $category_id);
    
    return rest_ensure_response(array('success' => true));
}

function varner_api_delete_video(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Video not found.', array('status' => 404));
    }
    wp_delete_post($post_id, true);
    return rest_ensure_response(array('success' => true));
}

function varner_api_get_video_categories() {
    $terms = get_terms(array(
        'taxonomy'   => 'video_category',
        'hide_empty' => false,
    ));
    if (is_wp_error($terms)) {
        return new WP_Error('terms_failed', $terms->get_error_message(), array('status' => 500));
    }
    
    $categories = array();
    foreach ($terms as $t) {
        $categories[] = array(
            'id'          => $t->term_id,
            'name'        => $t->name,
            'slug'        => $t->slug,
            'description' => $t->description,
        );
    }
    return rest_ensure_response($categories);
}

function varner_api_create_video_category(WP_REST_Request $request) {
    $data = $request->get_json_params();
    $name = sanitize_text_field($data['name'] ?? '');
    if (empty($name)) {
        return new WP_Error('missing_name', 'Category name is required.', array('status' => 400));
    }
    
    $term = wp_insert_term($name, 'video_category', array(
        'description' => sanitize_text_field($data['description'] ?? ''),
    ));
    if (is_wp_error($term)) {
        return new WP_Error('insert_failed', $term->get_error_message(), array('status' => 500));
    }
    
    return rest_ensure_response(array('success' => true, 'id' => $term['term_id']));
}

function varner_api_delete_video_category(WP_REST_Request $request) {
    $term_id = intval($request->get_param('id'));
    $result = wp_delete_term($term_id, 'video_category');
    if (is_wp_error($result)) {
        return new WP_Error('delete_failed', $result->get_error_message(), array('status' => 500));
    }
    return rest_ensure_response(array('success' => true));
}

