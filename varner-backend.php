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

// ─── 6. HELPERS ─────────────────────────────────────────────────────────────

function varner_format_unit($post_id) {
    $post = get_post($post_id);
    if (!$post) return null;

    // Gallery
    $gallery    = get_field('gallery', $post_id);
    $image_urls = array();
    $image_ids  = array();
    if (!empty($gallery)) {
        foreach ($gallery as $img) {
            if (is_array($img)) {
                $image_urls[] = $img['url'];
                $image_ids[]  = $img['ID'];
            } elseif (is_numeric($img)) {
                $image_urls[] = wp_get_attachment_url($img);
                $image_ids[]  = intval($img);
            }
        }
    }

    // Implements
    $raw        = get_field('implements', $post_id);
    $implements = array();
    if (!empty($raw)) {
        foreach ($raw as $imp) {
            $img_url = '';
            $img_id  = 0;
            if (!empty($imp['implement_image'])) {
                if (is_array($imp['implement_image'])) {
                    $img_url = $imp['implement_image']['url'];
                    $img_id  = $imp['implement_image']['ID'];
                } else {
                    $img_url = wp_get_attachment_url($imp['implement_image']);
                    $img_id  = intval($imp['implement_image']);
                }
            }
            $implements[] = array(
                'title'       => $imp['implement_title']       ?? '',
                'price'       => $imp['implement_price']       ?? '',
                'description' => $imp['implement_description'] ?? '',
                'image'       => $img_url,
                'image_id'    => $img_id,
            );
        }
    }

    return array(
        'id'           => $post_id,
        'title'        => $post->post_title,
        'year'         => get_field('year',         $post_id) ?? '',
        'make'         => get_field('make',         $post_id) ?? '',
        'model'        => get_field('model',        $post_id) ?? '',
        'stock_number' => get_field('stock_number', $post_id) ?? '',
        'vin'          => get_field('vin',          $post_id) ?? '',
        'price'        => (string)(get_field('price', $post_id) ?? ''),
        'call_for_price' => (bool) get_field('call_for_price', $post_id),
        'condition'    => get_field('condition',    $post_id) ?? 'New',
        'stock_status' => get_field('stock_status', $post_id) ?? 'Draft',
        'category'     => get_field('category',     $post_id) ?? '',
        'color'        => get_field('color',        $post_id) ?? '',
        'length'       => get_field('length',       $post_id) ?? '',
        'meter'        => get_field('meter',        $post_id) ?? '',
        'meter_type'   => get_field('meter_type',   $post_id) ?? 'Hours',
        'intake_date'  => get_field('intake_date',  $post_id) ?? '',
        'description'  => get_field('description',  $post_id) ?? '',
        'seller_info'  => get_field('seller_info',  $post_id) ?? '',
        'featured'     => (bool) get_field('featured', $post_id),
        'show_on_website' => (bool) (get_field('show_on_website', $post_id) !== false ? get_field('show_on_website', $post_id) : true),
        'images'       => $image_urls,
        'image_ids'    => $image_ids,
        'implements'   => $implements,
        'deleted_at'   => get_post_meta($post_id, '_varner_deleted_at', true),
    );
}

function varner_save_unit_fields($post_id, $data) {
    $text_fields = array('year','make','model','stock_number','vin','condition','stock_status','category','color','length','meter','meter_type','intake_date');
    foreach ($text_fields as $field) {
        if (array_key_exists($field, $data)) {
            update_field($field, sanitize_text_field($data[$field]), $post_id);
        }
    }

    if (array_key_exists('price', $data)) {
        update_field('field_varner_price', floatval($data['price']), $post_id);
    }
    if (array_key_exists('call_for_price', $data)) {
        update_field('field_varner_call_for_price', (bool) $data['call_for_price'], $post_id);
    }
    if (array_key_exists('featured', $data)) {
        update_field('field_varner_featured', (bool) $data['featured'], $post_id);
    }
    if (array_key_exists('show_on_website', $data)) {
        update_field('field_varner_show_on_website', (bool) $data['show_on_website'], $post_id);
    }
    if (array_key_exists('description', $data)) {
        update_field('description', wp_kses_post($data['description']), $post_id);
    }
    if (array_key_exists('seller_info', $data)) {
        update_field('seller_info', wp_kses_post($data['seller_info']), $post_id);
    }

    // Gallery — expects array of WP attachment IDs
    if (array_key_exists('image_ids', $data)) {
        $ids = array_map('intval', (array) $data['image_ids']);
        update_field('gallery', $ids, $post_id);
        // Keep WP post thumbnail in sync with the first gallery image
        if (!empty($ids)) {
            set_post_thumbnail($post_id, $ids[0]);
        } else {
            delete_post_thumbnail($post_id);
        }
    }

    // Implements — expects array of objects {title, price, description, image_id}
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
    return rest_ensure_response(varner_format_unit($post_id));
}

function varner_api_update_unit(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    $data    = $request->get_json_params();
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Unit not found.', array('status' => 404));
    }
    if (isset($data['title'])) {
        wp_update_post(array('ID' => $post_id, 'post_title' => sanitize_text_field($data['title'])));
    }
    varner_save_unit_fields($post_id, $data);
    return rest_ensure_response(varner_format_unit($post_id));
}

function varner_api_soft_delete(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Unit not found.', array('status' => 404));
    }
    update_post_meta($post_id, '_varner_deleted_at', current_time('c'));
    wp_trash_post($post_id);
    return rest_ensure_response(array('success' => true, 'id' => $post_id));
}

function varner_api_restore_unit(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    wp_untrash_post($post_id);
    delete_post_meta($post_id, '_varner_deleted_at');
    return rest_ensure_response(varner_format_unit($post_id));
}

function varner_api_permanent_delete(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    wp_delete_post($post_id, true);
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
