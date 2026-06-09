<?php
/**
 * Varner Equipment - Backend Registration
 * CPT, ACF fields, helpers, and REST API include.
 */

defined('ABSPATH') || exit;

// Load REST API handlers
require_once __DIR__ . '/rest-api.php';

// ─── 1. CUSTOM POST TYPES ────────────────────────────────────────────────────

add_action('init', 'varner_register_equipment_cpt');
function varner_register_equipment_cpt(): void {
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

if (!function_exists('varner_register_video_cpt')) {
    function varner_register_video_cpt(): void {
        register_post_type('video', array(
            'labels' => array(
                'name'          => __('Videos', 'varner-os'),
                'singular_name' => __('Video', 'varner-os'),
                'menu_name'     => __('Videos', 'varner-os'),
                'all_items'     => __('All Videos', 'varner-os'),
                'add_new'       => __('Add New Video', 'varner-os'),
                'add_new_item'  => __('Add New Video', 'varner-os'),
                'edit_item'     => __('Edit Video', 'varner-os'),
                'new_item'      => __('New Video', 'varner-os'),
                'view_item'     => __('View Video', 'varner-os'),
                'search_items'  => __('Search Videos', 'varner-os'),
                'not_found'     => __('No Videos Found', 'varner-os'),
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_rest'        => true,
            'rest_base'           => '',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'has_archive'         => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => true,
            'delete_with_user'    => false,
            'exclude_from_search' => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'rewrite'             => array('slug' => 'video', 'with_front' => true),
            'query_var'           => true,
            'menu_icon'           => 'dashicons-video-alt3',
            'supports'            => array('title'),
        ));

        register_taxonomy('video_category', array('video'), array(
            'labels' => array(
                'name'          => __('Video Categories', 'varner-os'),
                'singular_name' => __('Video Category', 'varner-os'),
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'hierarchical'       => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_nav_menus'  => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'video_category', 'with_front' => true),
            'show_admin_column'  => true,
            'show_in_rest'       => true,
            'rest_base'          => 'video_category',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
            'show_in_quick_edit' => false,
        ));
    }
}
add_action('init', 'varner_register_video_cpt');

// ─── 2. ACF JSON SYNC ────────────────────────────────────────────────────────

add_filter('acf/settings/save_json', function (): string {
    return __DIR__ . '/acf-json';
});

add_filter('acf/settings/load_json', function (array $paths): array {
    $paths[] = __DIR__ . '/acf-json';
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
});

// ─── 3. ACF FIELD GROUP ──────────────────────────────────────────────────────

if (function_exists('acf_add_local_field_group')):

acf_add_local_field_group(array(
    'key'   => 'group_varner_equipment_core',
    'title' => 'Varner OS - Core Data Bins',
    'fields' => array(
        array('key' => 'field_varner_year',         'label' => 'Year',                    'name' => 'year',             'type' => 'text'),
        array('key' => 'field_varner_make',          'label' => 'Brand / Manufacturer',    'name' => 'make',             'type' => 'select',
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
        array('key' => 'field_varner_model',         'label' => 'Model',          'name' => 'model',          'type' => 'text'),
        array('key' => 'field_varner_stock',         'label' => 'Stock Number',   'name' => 'stock_number',   'type' => 'text'),
        array('key' => 'field_varner_vin',           'label' => 'VIN / Serial',   'name' => 'vin',            'type' => 'text'),
        array('key' => 'field_varner_color',         'label' => 'Color',          'name' => 'color',          'type' => 'text'),
        array('key' => 'field_varner_length',        'label' => 'Length',         'name' => 'length',         'type' => 'text'),
        array('key' => 'field_varner_price',         'label' => 'Retail Price',   'name' => 'price',          'type' => 'number'),
        array('key' => 'field_varner_call_for_price','label' => 'Call For Price', 'name' => 'call_for_price',  'type' => 'true_false', 'ui' => 1, 'default_value' => 0),
        array('key' => 'field_varner_condition',     'label' => 'Condition',      'name' => 'condition',      'type' => 'select',
            'choices' => array('New' => 'New', 'Used' => 'Used'),
        ),
        array('key' => 'field_varner_status',        'label' => 'Stock Status',   'name' => 'stock_status',   'type' => 'select',
            'choices' => array('In Stock' => 'In Stock', 'Pending Sale' => 'Pending Sale', 'Sold' => 'Sold', 'Draft' => 'Draft'),
        ),
        array('key' => 'field_varner_category',      'label' => 'Category',       'name' => 'category',       'type' => 'select',
            'choices' => array(
                'Compact Tractors'=>'Compact Tractors','Utility Tractors'=>'Utility Tractors',
                'Tractors'=>'Tractors','Commercial Trailers'=>'Commercial Trailers',
                'Dump Trailers'=>'Dump Trailers','Flatbed Trailers'=>'Flatbed Trailers',
                'Utility Trailers'=>'Utility Trailers','Horse Trailers'=>'Horse Trailers',
                'Livestock Trailers'=>'Livestock Trailers','Trailers'=>'Trailers',
                'Utility Vehicles'=>'Utility Vehicles','Golf Carts'=>'Golf Carts',
                'Implements'=>'Implements','Attachments'=>'Attachments','Loaders'=>'Loaders',
                'Hay Equipment'=>'Hay Equipment','Balers'=>'Balers','Rakes'=>'Rakes',
                'Tedders'=>'Tedders','Snow Removal'=>'Snow Removal','Misc'=>'Misc','Other'=>'Other',
            ),
        ),
        array('key' => 'field_varner_subcategory',     'label' => 'Subcategory',      'name' => 'subcategory',      'type' => 'text'),
        array('key' => 'field_varner_sub_subcategory', 'label' => 'Sub-Subcategory',  'name' => 'sub_subcategory',  'type' => 'text'),
        array('key' => 'field_varner_meter',           'label' => 'Meter Reading',    'name' => 'meter',            'type' => 'text'),
        array('key' => 'field_varner_meter_type',      'label' => 'Meter Type',       'name' => 'meter_type',       'type' => 'select',
            'choices' => array('Hours' => 'Hours', 'Miles' => 'Miles', 'Acres' => 'Acres'),
        ),
        array('key' => 'field_varner_intake_date',     'label' => 'Intake Date',      'name' => 'intake_date',      'type' => 'date_picker',
            'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d',
        ),
        array('key' => 'field_varner_featured',        'label' => 'Featured on Home Page', 'name' => 'featured',   'type' => 'true_false',
            'ui' => 1, 'ui_on_text' => 'Featured', 'ui_off_text' => 'Not Featured', 'default_value' => 0,
        ),
        array('key' => 'field_varner_show_on_website', 'label' => 'Display on Website', 'name' => 'show_on_website', 'type' => 'true_false',
            'ui' => 1, 'ui_on_text' => 'Yes', 'ui_off_text' => 'No', 'default_value' => 1,
            'instructions' => 'If set to No, this unit will be hidden from all public-facing pages.',
        ),
        array('key' => 'field_varner_desc',            'label' => 'Public Description', 'name' => 'description',   'type' => 'wysiwyg'),
        array('key' => 'field_varner_seller_info',     'label' => 'Seller Info',        'name' => 'seller_info',   'type' => 'wysiwyg'),
        array('key' => 'field_varner_gallery',         'label' => 'Equipment Gallery',   'name' => 'gallery',       'type' => 'gallery',
            'return_format' => 'array', 'preview_size' => 'medium',
        ),
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
                array('key' => 'field_impl_image', 'label' => 'Image',       'name' => 'implement_image',       'type' => 'image',
                    'return_format' => 'id', 'preview_size' => 'medium',
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

// ─── 4. GUTENBERG BLOCK ──────────────────────────────────────────────────────

add_action('acf/init', 'varner_register_blocks');
function varner_register_blocks(): void {
    if (!function_exists('acf_register_block_type')) {
        return;
    }
    acf_register_block_type(array(
        'name'            => 'varner-editor',
        'title'           => 'Varner Inventory Editor',
        'description'     => 'The React-powered inventory editor.',
        'render_template' => __DIR__ . '/blocks/varner-editor.php',
        'category'        => 'formatting',
        'icon'            => 'admin-tools',
        'keywords'        => array('varner', 'inventory', 'editor'),
        'mode'            => 'edit',
        'enqueue_assets'  => function (): void {
            if (function_exists('varner_enqueue_react_assets')) {
                varner_enqueue_react_assets();
            }
        },
    ));
}

// ─── 5. HELPERS ──────────────────────────────────────────────────────────────

function varner_get_equipment_fields_config(): array {
    return array(
        'year'              => array('type' => 'text'),
        'make'              => array('type' => 'text'),
        'model'             => array('type' => 'text'),
        'stock_number'      => array('type' => 'text'),
        'vin'               => array('type' => 'text'),
        'price'             => array('type' => 'number'),
        'call_for_price'    => array('type' => 'bool'),
        'condition'         => array('type' => 'text', 'default' => 'New'),
        'stock_status'      => array('type' => 'text', 'default' => 'Draft'),
        'category'          => array('type' => 'text'),
        'subcategory'       => array('type' => 'text'),
        'sub_subcategory'   => array('type' => 'text'),
        'color'             => array('type' => 'text'),
        'length'            => array('type' => 'text'),
        'meter'             => array('type' => 'text'),
        'meter_type'        => array('type' => 'text', 'default' => 'Hours'),
        'intake_date'       => array('type' => 'text'),
        'description'       => array('type' => 'wysiwyg'),
        'seller_info'       => array('type' => 'wysiwyg'),
        'featured'          => array('type' => 'bool'),
        'show_on_website'   => array('type' => 'bool', 'default' => true),
        'has_attachments'   => array('type' => 'bool', 'default' => false),
        'attachment_details'=> array('type' => 'text'),
        'drive'             => array('type' => 'text'),
    );
}

function varner_format_unit(int $post_id): ?array {
    $post = get_post($post_id);
    if (!$post) return null;

    $config = varner_get_equipment_fields_config();
    $fields = function_exists('get_fields') ? get_fields($post_id) : array();

    $data = array('id' => $post_id, 'title' => $post->post_title);

    foreach ($config as $key => $meta) {
        $val = isset($fields[$key]) ? $fields[$key] : null;

        if ($meta['type'] === 'bool') {
            $data[$key] = (bool) ($val !== false && $val !== null ? $val : ($meta['default'] ?? false));
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

    $gallery = isset($fields['gallery']) ? $fields['gallery'] : array();
    $data['images']    = array();
    $data['image_ids'] = array();
    if (!empty($gallery)) {
        foreach ($gallery as $img) {
            if (is_array($img)) {
                $data['images'][]   = $img['url'];
                $data['image_ids'][] = $img['ID'];
            } elseif (is_numeric($img)) {
                $data['images'][]   = wp_get_attachment_url($img);
                $data['image_ids'][] = intval($img);
            }
        }
    }

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

    $data['created_at']         = $post->post_date;
    $data['deleted_at']         = get_post_meta($post_id, '_varner_deleted_at', true);
    $data['last_action']        = get_post_meta($post_id, '_varner_last_action', true);
    $data['last_actor_name']    = get_post_meta($post_id, '_varner_last_actor_name', true);
    $data['last_actor_initials'] = get_post_meta($post_id, '_varner_last_actor_initials', true);
    $data['last_action_at']     = get_post_meta($post_id, '_varner_last_action_at', true);

    return $data;
}

function varner_save_unit_fields(int $post_id, array $data): void {
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

    if (array_key_exists('image_ids', $data)) {
        $ids = array_map('intval', (array) $data['image_ids']);
        update_field('gallery', $ids, $post_id);
        if (!empty($ids)) {
            set_post_thumbnail($post_id, $ids[0]);
        } else {
            delete_post_thumbnail($post_id);
        }
    }

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

function varner_os_user_initials(WP_User $user): string {
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

function varner_os_current_actor(): ?array {
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) return null;

    return array(
        'id'           => $user->ID,
        'display_name' => $user->display_name,
        'initials'     => varner_os_user_initials($user),
    );
}

function varner_os_request_id(WP_REST_Request $request): string {
    $rid = $request->get_header('x-request-id');
    if (!$rid) {
        $rid = $request->get_param('request_id');
    }
    $rid = sanitize_text_field((string) $rid);
    return $rid ? substr($rid, 0, 64) : '';
}

function varner_os_update_last_meta(int $post_id, string $action, ?array $actor): void {
    if (!$actor) return;
    update_post_meta($post_id, '_varner_last_action', $action);
    update_post_meta($post_id, '_varner_last_actor_name', $actor['display_name']);
    update_post_meta($post_id, '_varner_last_actor_initials', $actor['initials']);
    update_post_meta($post_id, '_varner_last_action_at', current_time('mysql'));
}

function varner_os_log_ledger(int $post_id, string $action, string $summary, array $details = array(), string $request_id = ''): int {
    global $wpdb;
    $table  = $wpdb->prefix . 'varner_inventory_ledger';
    $actor  = varner_os_current_actor();

    if ($request_id) {
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE request_id = %s LIMIT 1", $request_id));
        if ($existing) {
            return intval($existing);
        }
    }

    $wpdb->insert(
        $table,
        array(
            'post_id'      => $post_id,
            'action'       => sanitize_text_field($action),
            'user_id'      => $actor ? $actor['id'] : null,
            'display_name' => $actor ? $actor['display_name'] : null,
            'initials'     => $actor ? $actor['initials'] : null,
            'summary'      => sanitize_text_field(substr($summary, 0, 255)),
            'details'      => wp_json_encode($details),
            'request_id'   => $request_id ?: null,
            'created_at'   => current_time('mysql'),
        ),
        array('%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    varner_os_update_last_meta($post_id, $action, $actor);
    return $wpdb->insert_id;
}

function varner_os_diff_unit(array $before, array $after): array {
    $diff   = array();
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

function varner_os_diff_summary(array $diff): string {
    if (empty($diff)) return 'updated unit';
    $parts = array();
    foreach ($diff as $field => $change) {
        $parts[] = sprintf('%s: %s -> %s', $field, $change['from'], $change['to']);
        if (count($parts) >= 3) break;
    }
    return implode('; ', $parts);
}

// ─── 6. SETTINGS DEFAULTS ────────────────────────────────────────────────────

function varner_backend_get_settings_defaults(): array {
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
            "Top-Tier Equipment Brands",
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

if (!function_exists('varner_get_theme_settings_defaults')) {
    function varner_get_theme_settings_defaults(): array {
        if (function_exists('varner_backend_get_settings_defaults')) {
            return varner_backend_get_settings_defaults();
        }
        return array();
    }
}
