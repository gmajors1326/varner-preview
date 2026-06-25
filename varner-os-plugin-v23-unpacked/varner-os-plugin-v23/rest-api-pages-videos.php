<?php
/**
 * Varner OS — REST API Handlers: Pages & Video management.
 *
 * Extracted from rest-api.php. Loaded via require_once in that file.
 * Route registrations remain in rest-api.php (uses local closures).
 * All dependencies: WordPress globals (no varner-backend.php dependency).
 */

defined('ABSPATH') || exit;

// ─── 12. VIDEOS ──────────────────────────────────────────────────────────────

function varner_extract_youtube_url(string $embed_html): string {
    if (preg_match('/src="([^"]+)"/', $embed_html, $match)) {
        $src = $match[1];
        if (preg_match('/embed\/([^?&"]+)/', $src, $id_match)) {
            return 'https://www.youtube.com/watch?v=' . $id_match[1];
        }
        return $src;
    }
    return $embed_html;
}

function varner_get_youtube_embed_html(string $url): string {
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

function varner_save_video_fields(int $post_id, string $youtube_url, int $category_id, string $video_file_url = '', int $video_file_id = 0): void {
    $embed_html = varner_get_youtube_embed_html($youtube_url);
    if (function_exists('update_field')) {
        update_field('youtube_link', $embed_html, $post_id);
    } else {
        update_post_meta($post_id, 'youtube_link', $embed_html);
    }

    update_post_meta($post_id, 'video_file_url', $video_file_url);
    update_post_meta($post_id, 'video_file_id', $video_file_id);

    if ($category_id) {
        wp_set_post_terms($post_id, array($category_id), 'video_category');
    } else {
        wp_set_post_terms($post_id, array(), 'video_category');
    }
}

function varner_api_get_videos(): WP_REST_Response {
    $posts = get_posts(array(
        'post_type'      => 'video',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ));

    $videos = array();
    foreach ($posts as $p) {
        $cats     = wp_get_post_terms($p->ID, 'video_category');
        $cat_id   = !empty($cats) ? $cats[0]->term_id : 0;
        $cat_name = !empty($cats) ? $cats[0]->name : 'Uncategorized';

        $videos[] = array(
            'id'            => $p->ID,
            'title'         => $p->post_title,
            'youtube_link'  => varner_extract_youtube_url(
                get_post_meta($p->ID, 'youtube_link', true)
                ?: (function_exists('get_field') ? get_field('youtube_link', $p->ID) : '')
            ),
            'video_file_url' => get_post_meta($p->ID, 'video_file_url', true) ?: '',
            'video_file_id'  => intval(get_post_meta($p->ID, 'video_file_id', true)),
            'category_id'   => $cat_id,
            'category_name' => $cat_name,
        );
    }
    return rest_ensure_response($videos);
}

function varner_api_create_video(WP_REST_Request $request) {
    $data    = $request->get_json_params();
    $post_id = wp_insert_post(array(
        'post_title'  => sanitize_text_field($data['title'] ?? 'Untitled Video'),
        'post_type'   => 'video',
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', $post_id->get_error_message(), array('status' => 500));
    }

    $youtube_link  = sanitize_text_field($data['youtube_link'] ?? '');
    $category_id   = intval($data['category_id'] ?? 0);
    $video_file_url = esc_url_raw($data['video_file_url'] ?? '');
    $video_file_id  = intval($data['video_file_id'] ?? 0);
    varner_save_video_fields($post_id, $youtube_link, $category_id, $video_file_url, $video_file_id);

    return rest_ensure_response(array('success' => true, 'id' => $post_id));
}

function varner_api_update_video(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    $data    = $request->get_json_params();
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Video not found.', array('status' => 404));
    }
    if (!current_user_can('edit_post', $post_id)) {
        return new WP_Error('forbidden', 'You are not allowed to edit this video.', array('status' => 403));
    }

    $update_args = array('ID' => $post_id);
    if (isset($data['title'])) {
        $update_args['post_title'] = sanitize_text_field($data['title']);
    }
    wp_update_post($update_args);

    $youtube_link  = sanitize_text_field($data['youtube_link'] ?? '');
    $category_id   = intval($data['category_id'] ?? 0);
    $video_file_url = esc_url_raw($data['video_file_url'] ?? '');
    $video_file_id  = intval($data['video_file_id'] ?? 0);
    varner_save_video_fields($post_id, $youtube_link, $category_id, $video_file_url, $video_file_id);

    return rest_ensure_response(array('success' => true));
}

function varner_api_delete_video(WP_REST_Request $request) {
    $post_id = intval($request->get_param('id'));
    if (!get_post($post_id)) {
        return new WP_Error('not_found', 'Video not found.', array('status' => 404));
    }
    if (!current_user_can('delete_post', $post_id)) {
        return new WP_Error('forbidden', 'You are not allowed to delete this video.', array('status' => 403));
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
    $result  = wp_delete_term($term_id, 'video_category');
    if (is_wp_error($result)) {
        return new WP_Error('delete_failed', $result->get_error_message(), array('status' => 500));
    }
    return rest_ensure_response(array('success' => true));
}

// ─── PAGE MANAGEMENT HANDLERS ─────────────────────────────────────────────────

function varner_api_list_pages(): WP_REST_Response {
    $pages = get_pages(array(
        'post_status' => array('publish', 'draft'),
        'sort_column' => 'post_title',
        'sort_order'  => 'ASC',
    ));
    $out = array();
    foreach ($pages as $p) {
        $template = get_page_template_slug($p->ID);
        $out[] = array(
            'id'          => $p->ID,
            'title'       => $p->post_title,
            'slug'        => $p->post_name,
            'template'    => $template ?: 'default',
            'status'      => $p->post_status,
            'modified'    => $p->post_modified,
            'link'        => get_permalink($p->ID),
        );
    }
    return rest_ensure_response($out);
}

/**
 * Check if a page is protected (e.g. front page, blog page, privacy policy, or showroom).
 */
function varner_is_protected_page(int $id): bool {
    if ($id <= 0) {
        return false;
    }

    $front_page_id   = (int) get_option('page_on_front');
    $posts_page_id   = (int) get_option('page_for_posts');
    $privacy_page_id = (int) get_option('wp_page_for_privacy_policy');

    if ($id === $front_page_id || $id === $posts_page_id || $id === $privacy_page_id) {
        return true;
    }

    $post = get_post($id);
    if ($post && (has_shortcode($post->post_content, 'varner_showroom') || strpos($post->post_content, '[varner_showroom]') !== false)) {
        return true;
    }

    return false;
}

/**
 * Validate if a page template file exists and is registered in the active theme.
 */
function varner_is_valid_page_template(string $template): bool {
    if ($template === '' || $template === 'default') {
        return true;
    }
    $theme = wp_get_theme();
    $templates = $theme->get_page_templates();
    return is_array($templates) && array_key_exists($template, $templates);
}

function varner_api_create_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $title    = sanitize_text_field($request->get_param('title'));
    $slug     = sanitize_title($request->get_param('slug') ?: $title);
    $template = sanitize_text_field($request->get_param('template') ?: '');

    if (empty($title)) {
        return new WP_Error('missing_title', 'Page title is required.', array('status' => 400));
    }

    if (!varner_is_valid_page_template($template)) {
        return new WP_Error('invalid_template', 'The selected page template is invalid.', array('status' => 400));
    }

    $id = wp_insert_post(array(
        'post_type'   => 'page',
        'post_title'  => $title,
        'post_name'   => $slug,
        'post_status' => 'publish',
    ), true);

    if (is_wp_error($id)) {
        return $id;
    }

    if ($template && $template !== 'default') {
        update_post_meta($id, '_wp_page_template', $template);
    }

    return rest_ensure_response(array(
        'success'  => true,
        'page_id'  => $id,
        'link'     => get_permalink($id),
        'edit_link'=> get_edit_post_link($id, ''),
    ));
}

function varner_api_update_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $id = intval($request->get_param('id'));
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page') {
        return new WP_Error('not_found', 'Page not found.', array('status' => 404));
    }

    $slug   = $request->get_param('slug');
    $status = $request->get_param('status');

    // Hardened server-side guard for protected pages
    if (varner_is_protected_page($id)) {
        if ($status === 'draft') {
            return new WP_Error('protected_page', 'Protected system pages (e.g. front page or showroom) cannot be changed to draft status.', array('status' => 403));
        }
        if ($slug !== null && sanitize_title($slug) !== $post->post_name) {
            return new WP_Error('protected_page', 'Slugs of protected system pages cannot be modified as it will break site routing.', array('status' => 403));
        }
    }

    $update = array('ID' => $id);
    $title = $request->get_param('title');
    if ($title !== null) {
        $update['post_title'] = sanitize_text_field($title);
    }
    if ($slug !== null) {
        $update['post_name'] = sanitize_title($slug);
    }
    if ($status !== null && in_array($status, array('publish', 'draft'), true)) {
        $update['post_status'] = $status;
    }

    if (!empty($update)) {
        $result = wp_update_post($update, true);
        if (is_wp_error($result)) {
            return $result;
        }
    }

    $template = $request->get_param('template');
    if ($template !== null) {
        $template = sanitize_text_field($template);
        if (!varner_is_valid_page_template($template)) {
            return new WP_Error('invalid_template', 'The selected page template is invalid.', array('status' => 400));
        }
        update_post_meta($id, '_wp_page_template', $template);
    }

    return rest_ensure_response(array('success' => true, 'page_id' => $id));
}

function varner_api_delete_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $id = intval($request->get_param('id'));
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page') {
        return new WP_Error('not_found', 'Page not found.', array('status' => 404));
    }

    // Hardened server-side guard: do not allow trashing critical pages
    if (varner_is_protected_page($id)) {
        return new WP_Error('protected_page', 'This page is a protected system page (e.g. front page or showroom) and cannot be deleted.', array('status' => 403));
    }

    $result = wp_trash_post($id);
    if (!$result) {
        return new WP_Error('delete_failed', 'Could not trash page.', array('status' => 500));
    }
    return rest_ensure_response(array('success' => true));
}

function varner_api_get_page_templates(): WP_REST_Response {
    $theme = wp_get_theme();
    $templates = $theme->get_page_templates();
    $out = array();
    // Add default template
    $out[] = array('file' => '', 'name' => 'Default Template');
    foreach ($templates as $file => $name) {
        $out[] = array('file' => $file, 'name' => $name);
    }
    return rest_ensure_response($out);
}
