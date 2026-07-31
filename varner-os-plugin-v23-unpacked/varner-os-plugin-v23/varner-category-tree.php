<?php
defined('ABSPATH') || exit;

function varner_default_category_tree(): array {
    return array(
        "Utility Vehicles" => array(
            "Utility" => array()
        ),
        "Tractors" => array(
            "175 HP to 299 HP" => array(),
            "100 HP to 174 HP" => array(),
            "40 HP to 99 HP" => array(),
            "Less than 40 HP" => array()
        ),
        "Planting Equipment" => array(
            "Other" => array()
        ),
        "Tillage Equipment" => array(
            "Chisel Plows" => array(),
            "Disks" => array(),
            "Plows" => array(),
            "Rippers" => array(),
            "Rotary Tillage" => array(),
            "Row Crop Cultivators" => array(),
            "Other" => array()
        ),
        "Hay and Forage Equipment" => array(
            "Bale Accumulators / Movers" => array(),
            "Disc Mowers" => array(),
            "Mower Conditioners/Windrowers" => array("Self-Propelled", "Pull-Type", "Mounted"),
            "Hay Rakes" => array(),
            "Tedders" => array(),
            "Rotary Mowers" => array(),
            "Round Balers" => array(),
            "Square Balers" => array("Large", "Small"),
            "Tub Grinders/Bale Processors" => array(),
            "Other" => array()
        ),
        "Chemical Applicators" => array(
            "Sprayers" => array("3 pt/Mounted")
        ),
        "Grain Handling / Storage Equipment" => array(
            "Grain Augers" => array()
        ),
        "Ag Trailers" => array(
            "Other" => array()
        ),
        "Snow Equipment" => array(
            "Lawn Mowers" => array("Riding"),
            "Snow Blowers" => array()
        ),
        "Implements" => array(
            "Blades/Box Scrapers" => array(),
            "Manure Spreaders" => array("Dry")
        ),
        "Turf Equipment" => array(
            "Mowers" => array("Fairway")
        ),
        "Trucks" => array(
            "Pickup Trucks" => array("1/2 Ton"),
            "Service Trucks / Utility Trucks / Mechanic Trucks" => array(),
            "Truck Bodies Only" => array("Other")
        ),
        "Semi-Trailers" => array(
            "Log Trailers" => array()
        ),
        "Trailers" => array(
            "Car Hauler Trailers" => array("Enclosed", "Open"),
            "Cargo / Enclosed Trailers" => array(),
            "Dump Trailers" => array(),
            "Flatbed / Tag Trailers" => array(),
            "Livestock Trailers" => array(),
            "Tilt Trailers" => array(),
            "Landscaping Trailers" => array(),
            "Utility Trailers" => array("ATV", "Snowmobile"),
            "Other Trailers" => array()
        )
    );
}

function varner_derive_flat_options_from_tree(): void {
    $tree = get_option('varner_category_tree', array());
    if (!is_array($tree) || empty($tree)) {
        return;
    }

    $categories     = array();
    $subcategories  = array();
    $sub_subcats    = array();

    foreach ($tree as $cat => $subs) {
        $categories[] = (string) $cat;
        foreach ((array) $subs as $sub => $sub_subs) {
            $subcategories[] = (string) $sub;
            foreach ((array) $sub_subs as $ss) {
                $sub_subcats[] = (string) $ss;
            }
        }
    }

    $categories    = array_unique($categories);
    $subcategories = array_unique($subcategories);
    $sub_subcats   = array_unique($sub_subcats);
    sort($categories);
    sort($subcategories);
    sort($sub_subcats);

    update_option('varner_categories', $categories);
    update_option('varner_subcategories', $subcategories);
    update_option('varner_sub_subcategories', $sub_subcats);
}

function varner_count_equipment_by_meta_scoped(array $conditions): int {
    global $wpdb;
    if (empty($conditions)) return 0;
    $joins = ''; $i = 0;
    foreach ($conditions as $key => $val) {
        $a = "m{$i}";
        $joins .= $wpdb->prepare(
            " INNER JOIN {$wpdb->postmeta} {$a} ON {$a}.post_id = p.ID AND {$a}.meta_key = %s AND {$a}.meta_value = %s",
            $key, $val
        );
        $i++;
    }
    $sql = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p{$joins}
            WHERE p.post_type = 'equipment' AND p.post_status IN ('publish','draft')";
    return (int) $wpdb->get_var($sql);
}

function varner_migrate_equipment_meta_scoped(array $match, string $update_key, string $new_value): int {
    global $wpdb;
    if (empty($match)) return 0;
    $joins = ''; $i = 0;
    foreach ($match as $key => $val) {
        $a = "m{$i}";
        $joins .= $wpdb->prepare(
            " INNER JOIN {$wpdb->postmeta} {$a} ON {$a}.post_id = p.ID AND {$a}.meta_key = %s AND {$a}.meta_value = %s",
            $key, $val
        );
        $i++;
    }
    $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p{$joins}
            WHERE p.post_type = 'equipment' AND p.post_status IN ('publish','draft')";
    $ids = $wpdb->get_col($sql);
    if (empty($ids)) return 0;
    foreach ($ids as $pid) {
        update_post_meta((int) $pid, $update_key, $new_value);
        clean_post_cache((int) $pid);
    }
    return count($ids);
}

function varner_category_tree_mutate(string $op, array $args): array|WP_Error {
    $type        = $args['type'] ?? '';
    $name        = $args['name'] ?? '';
    $new_name    = $args['new_name'] ?? '';
    $parent_cat  = $args['parent_category'] ?? '';
    $parent_sub  = $args['parent_subcategory'] ?? '';
    $reassign_to = $args['reassign_to'] ?? '';

    if (!in_array($type, array('category', 'subcategory', 'sub_subcategory'), true)) {
        return new WP_Error('invalid_type', 'Type must be category, subcategory, or sub_subcategory.', array('status' => 400));
    }
    if (!in_array($op, array('add', 'rename', 'delete'), true)) {
        return new WP_Error('invalid_op', 'Operation must be add, rename, or delete.', array('status' => 400));
    }
    if ($op !== 'rename' && empty($name)) {
        return new WP_Error('missing_name', 'Name is required.', array('status' => 400));
    }
    if ($op === 'rename' && empty($new_name)) {
        return new WP_Error('missing_new_name', 'new_name is required for rename.', array('status' => 400));
    }

    $tree = get_option('varner_category_tree', array());
    if (!is_array($tree) || empty($tree)) {
        return new WP_Error('no_tree', 'Category tree not seeded.', array('status' => 500));
    }

    $affected_posts = 0;

    if ($op === 'add') {
        if ($type === 'category') {
            if (isset($tree[$name])) {
                return new WP_Error('already_exists', "Category '{$name}' already exists.", array('status' => 409));
            }
            $tree[$name] = array();
        } elseif ($type === 'subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (isset($tree[$parent_cat][$name])) {
                return new WP_Error('already_exists', "Subcategory '{$name}' already exists under '{$parent_cat}'.", array('status' => 409));
            }
            $tree[$parent_cat][$name] = array();
        } elseif ($type === 'sub_subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (!$parent_sub || !isset($tree[$parent_cat][$parent_sub])) {
                return new WP_Error('parent_not_found', "Parent subcategory '{$parent_sub}' not found under '{$parent_cat}'.", array('status' => 404));
            }
            if (in_array($name, $tree[$parent_cat][$parent_sub], true)) {
                return new WP_Error('already_exists', "Sub-subcategory '{$name}' already exists.", array('status' => 409));
            }
            $tree[$parent_cat][$parent_sub][] = $name;
        }
    } elseif ($op === 'rename') {
        if ($type === 'category') {
            if (!isset($tree[$name])) {
                return new WP_Error('not_found', "Category '{$name}' not found.", array('status' => 404));
            }
            if (isset($tree[$new_name])) {
                return new WP_Error('already_exists', "Category '{$new_name}' already exists.", array('status' => 409));
            }
            $tree[$new_name] = $tree[$name];
            unset($tree[$name]);
            $affected_posts = varner_migrate_equipment_meta_scoped(
                ['category' => $name], 'category', $new_name
            );
        } elseif ($type === 'subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (!isset($tree[$parent_cat][$name])) {
                return new WP_Error('not_found', "Subcategory '{$name}' not found under '{$parent_cat}'.", array('status' => 404));
            }
            if (isset($tree[$parent_cat][$new_name])) {
                return new WP_Error('already_exists', "Subcategory '{$new_name}' already exists under '{$parent_cat}'.", array('status' => 409));
            }
            $tree[$parent_cat][$new_name] = $tree[$parent_cat][$name];
            unset($tree[$parent_cat][$name]);
            $affected_posts = varner_migrate_equipment_meta_scoped(
                ['category' => $parent_cat, 'subcategory' => $name], 'subcategory', $new_name
            );
        } elseif ($type === 'sub_subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (!$parent_sub || !isset($tree[$parent_cat][$parent_sub])) {
                return new WP_Error('parent_not_found', "Parent subcategory '{$parent_sub}' not found under '{$parent_cat}'.", array('status' => 404));
            }
            $idx = array_search($name, $tree[$parent_cat][$parent_sub], true);
            if ($idx === false) {
                return new WP_Error('not_found', "Sub-subcategory '{$name}' not found.", array('status' => 404));
            }
            if (in_array($new_name, $tree[$parent_cat][$parent_sub], true)) {
                return new WP_Error('already_exists', "Sub-subcategory '{$new_name}' already exists.", array('status' => 409));
            }
            $tree[$parent_cat][$parent_sub][$idx] = $new_name;
            $affected_posts = varner_migrate_equipment_meta_scoped(
                ['category' => $parent_cat, 'subcategory' => $parent_sub, 'sub_subcategory' => $name],
                'sub_subcategory', $new_name
            );
        }
    } elseif ($op === 'delete') {
        if ($type === 'category') {
            if (!isset($tree[$name])) {
                return new WP_Error('not_found', "Category '{$name}' not found.", array('status' => 404));
            }
            $affected_posts = varner_count_equipment_by_meta_scoped(['category' => $name]);
            if ($affected_posts > 0) {
                return new WP_Error('category_not_empty',
                    sprintf('%d units are in this category. Rename or reassign its subcategories first, or move the units, then delete the empty category.', $affected_posts),
                    array('status' => 409, 'affected_posts' => $affected_posts));
            }
            unset($tree[$name]);
        } elseif ($type === 'subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (!isset($tree[$parent_cat][$name])) {
                return new WP_Error('not_found', "Subcategory '{$name}' not found under '{$parent_cat}'.", array('status' => 404));
            }
            $affected_posts = varner_count_equipment_by_meta_scoped(
                ['category' => $parent_cat, 'subcategory' => $name]
            );
            if ($affected_posts > 0 && $reassign_to === '') {
                return new WP_Error('has_units',
                    sprintf('%d equipment %s assigned to this subcategory. Provide reassign_to or move units first.', $affected_posts, _n('post is', 'posts are', $affected_posts)),
                    array('status' => 409, 'affected_posts' => $affected_posts));
            }
            if ($reassign_to !== '' && $affected_posts > 0) {
                varner_migrate_equipment_meta_scoped(
                    ['category' => $parent_cat, 'subcategory' => $name],
                    'subcategory', $reassign_to
                );
            }
            unset($tree[$parent_cat][$name]);
        } elseif ($type === 'sub_subcategory') {
            if (!$parent_cat || !isset($tree[$parent_cat])) {
                return new WP_Error('parent_not_found', "Parent category '{$parent_cat}' not found.", array('status' => 404));
            }
            if (!$parent_sub || !isset($tree[$parent_cat][$parent_sub])) {
                return new WP_Error('parent_not_found', "Parent subcategory '{$parent_sub}' not found under '{$parent_cat}'.", array('status' => 404));
            }
            if (!in_array($name, $tree[$parent_cat][$parent_sub], true)) {
                return new WP_Error('not_found', "Sub-subcategory '{$name}' not found.", array('status' => 404));
            }
            $affected_posts = varner_count_equipment_by_meta_scoped(
                ['category' => $parent_cat, 'subcategory' => $parent_sub, 'sub_subcategory' => $name]
            );
            if ($affected_posts > 0 && $reassign_to === '') {
                return new WP_Error('has_units',
                    sprintf('%d equipment %s assigned to this sub-subcategory. Provide reassign_to or move units first.', $affected_posts, _n('post is', 'posts are', $affected_posts)),
                    array('status' => 409, 'affected_posts' => $affected_posts));
            }
            if ($reassign_to !== '' && $affected_posts > 0) {
                varner_migrate_equipment_meta_scoped(
                    ['category' => $parent_cat, 'subcategory' => $parent_sub, 'sub_subcategory' => $name],
                    'sub_subcategory', $reassign_to
                );
            }
            $idx = array_search($name, $tree[$parent_cat][$parent_sub], true);
            if ($idx !== false) {
                array_splice($tree[$parent_cat][$parent_sub], $idx, 1);
            }
        }
    }

    update_option('varner_category_tree', $tree);
    varner_derive_flat_options_from_tree();
    delete_transient('varner_brand_counts');

    return array(
        'tree' => $tree,
        'affected_posts' => $affected_posts,
    );
}

function varner_seed_category_tree(): void {
    if (get_option('varner_category_tree')) {
        return; // Seed once
    }

    $tree = varner_default_category_tree();

    global $wpdb;
    // 1. Scan all equipment posts to infer parents from actual usage
    $posts = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, 
               m_cat.meta_value as category, 
               m_sub.meta_value as subcategory, 
               m_ss.meta_value as sub_subcategory
        FROM %i p
        LEFT JOIN %i m_cat ON p.ID = m_cat.post_id AND m_cat.meta_key = 'category'
        LEFT JOIN %i m_sub ON p.ID = m_sub.post_id AND m_sub.meta_key = 'subcategory'
        LEFT JOIN %i m_ss ON p.ID = m_ss.post_id AND m_ss.meta_key = 'sub_subcategory'
        WHERE p.post_type = 'equipment' AND p.post_status IN ('publish', 'draft')
    ", $wpdb->posts, $wpdb->postmeta, $wpdb->postmeta, $wpdb->postmeta), ARRAY_A);

    foreach ((array) $posts as $post) {
        $cat = trim($post['category'] ?? '');
        if (!$cat) continue;

        // Ensure category exists in tree
        if (!isset($tree[$cat])) {
            $tree[$cat] = array();
        }

        // Subcategory — raw meta_value may be a serialized array after migration
        $sub_raw = $post['subcategory'] ?? '';
        $sub_unser = maybe_unserialize($sub_raw);
        $sub_names = is_array($sub_unser)
            ? array_filter($sub_unser, 'strlen')
            : (is_string($sub_unser) && $sub_unser !== '' ? array($sub_unser) : array());

        // Sub-subcategory — may also be serialized in future
        $ss_raw = $post['sub_subcategory'] ?? '';
        $ss_unser = maybe_unserialize($ss_raw);
        $ss_names = is_array($ss_unser)
            ? array_filter($ss_unser, 'strlen')
            : (is_string($ss_unser) && $ss_unser !== '' ? array($ss_unser) : array());

        foreach ($sub_names as $sub) {
            $sub = trim($sub);
            if (!$sub) continue;
            if (!isset($tree[$cat][$sub])) {
                $tree[$cat][$sub] = array();
            }
            foreach ($ss_names as $ss) {
                $ss = trim($ss);
                if (!$ss) continue;
                if (!in_array($ss, $tree[$cat][$sub], true)) {
                    $tree[$cat][$sub][] = $ss;
                }
            }
        }
    }

    // 2. Merge remaining flat custom subcategories / sub-subcategories from options (orphans)
    $flat_subs = get_option('varner_subcategories', array());
    if (is_array($flat_subs)) {
        foreach ($flat_subs as $sub) {
            // Flat options may contain serialized strings from a previous poisoned tree
            $sub_unser = maybe_unserialize($sub);
            if (is_array($sub_unser)) {
                foreach ($sub_unser as $s) {
                    $s = is_string($s) ? trim($s) : '';
                    if ($s === '') continue;
                    _varner_seed_orphan_sub($tree, $s);
                }
                continue;
            }
            $sub = is_string($sub_unser) ? trim($sub_unser) : '';
            if ($sub === '') continue;
            _varner_seed_orphan_sub($tree, $sub);
        }
    }

    $flat_ss = get_option('varner_sub_subcategories', array());
    if (is_array($flat_ss)) {
        foreach ($flat_ss as $ss) {
            $ss_unser = maybe_unserialize($ss);
            if (is_array($ss_unser)) {
                foreach ($ss_unser as $s) {
                    $s = is_string($s) ? trim($s) : '';
                    if ($s === '') continue;
                    _varner_seed_orphan_ss($tree, $s);
                }
                continue;
            }
            $ss = is_string($ss_unser) ? trim($ss_unser) : '';
            if ($ss === '') continue;
            _varner_seed_orphan_ss($tree, $ss);
        }
    }

    update_option('varner_category_tree', $tree);
    varner_derive_flat_options_from_tree();
}

function _varner_seed_orphan_sub(array &$tree, string $sub): void {
    foreach ($tree as $c => $subs) {
        if (isset($subs[$sub])) return;
    }
    if (!isset($tree['Uncategorized'])) {
        $tree['Uncategorized'] = array();
    }
    $tree['Uncategorized'][$sub] = array();
}

function _varner_seed_orphan_ss(array &$tree, string $ss): void {
    foreach ($tree as $c => $subs) {
        foreach ($subs as $s => $sub_subs) {
            if (in_array($ss, $sub_subs, true)) return;
        }
    }
    if (!isset($tree['Uncategorized'])) {
        $tree['Uncategorized'] = array();
    }
    if (!isset($tree['Uncategorized']['General'])) {
        $tree['Uncategorized']['General'] = array();
    }
    $tree['Uncategorized']['General'][] = $ss;
}
