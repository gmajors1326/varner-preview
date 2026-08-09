<?php
/**
 * Varner Equipment — Smart Breadcrumb
 * Renders on every page except the homepage.
 * Outputs nothing on front page.
 */

if ( is_front_page() ) return;

// Build crumb trail
$crumbs = array();

// Home is always first
$crumbs[] = array(
    'label' => 'Home',
    'url'   => home_url( '/' ),
);

// ── Virtual pages (from parse_request router) ─────────────────
if ( defined( 'VARNER_VIRTUAL_PAGE' ) ) {
    $virtual_path = VARNER_VIRTUAL_PAGE;
    if ( $virtual_path === 'services/service-request' ) {
        $crumbs[] = array( 'label' => 'Services', 'url' => home_url( '/services' ) );
        $crumbs[] = array( 'label' => 'Service Request', 'url' => '' );
    } elseif ( $virtual_path === 'services/parts-request' ) {
        $crumbs[] = array( 'label' => 'Services', 'url' => home_url( '/services' ) );
        $crumbs[] = array( 'label' => 'Parts Request', 'url' => '' );
    } elseif ( $virtual_path === 'dealer-info/about-us' ) {
        $crumbs[] = array( 'label' => 'Dealer Info', 'url' => home_url( '/dealer-info' ) );
        $crumbs[] = array( 'label' => 'About Us', 'url' => '' );
    } elseif ( $virtual_path === 'dealer-info/our-team' ) {
        $crumbs[] = array( 'label' => 'Dealer Info', 'url' => home_url( '/dealer-info' ) );
        $crumbs[] = array( 'label' => 'Our Team', 'url' => '' );
    } elseif ( $virtual_path === 'dealer-info/employment' ) {
        $crumbs[] = array( 'label' => 'Dealer Info', 'url' => home_url( '/dealer-info' ) );
        $crumbs[] = array( 'label' => 'Employment', 'url' => '' );
    } else {
        $virtual_titles = array(
            'services'       => 'Services',
            'dealer-info'    => 'Dealer Info',
            'videos'         => 'Videos',
            'product-videos' => 'Product Videos',
            'finance'        => 'Finance',
            'financing'      => 'Financing',
            'contact'        => 'Contact',
            'brands'         => 'Brands',
        );
        $title = $virtual_titles[ $virtual_path ] ?? '';
        if ( ! $title ) {
            $path_parts = explode( '/', $virtual_path );
            $title = ucwords( str_replace( '-', ' ', end( $path_parts ) ) );
        }
        $crumbs[] = array( 'label' => $title, 'url' => '' );
    }
}
// ── Equipment detail page ────────────────────────────────────
elseif ( is_singular( 'equipment' ) ) {
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/inventory/all-units' ) );

    $category = get_field( 'category' );
    if ( $category ) {
        // Map category to its segment slug
        $seg_map = array(
            'Compact Tractors'          => 'tractors',
            'Utility Tractors'          => 'tractors',
            'Tractors'                  => 'tractors',
            'Farm Tractors'             => 'tractors',
            'Commercial Trailers'       => 'trailers',
            'Dump Trailers'             => 'dump-trailers',
            'Flatbed Trailers'          => 'trailers',
            'Flatbed / Tag Trailers'    => 'trailers',
            'Utility Trailers'          => 'utility-trailers',
            'Horse Trailers'            => 'trailers',
            'Livestock Trailers'        => 'trailers',
            'Ag Trailers'               => 'trailers',
            'Semi-Trailers'             => 'trailers',
            'Car Hauler Trailers'       => 'trailers',
            'Cargo / Enclosed Trailers' => 'trailers',
            'Tilt Trailers'             => 'trailers',
            'Landscaping Trailers'      => 'trailers',
            'Other Trailers'            => 'trailers',
            'Trailers'                  => 'trailers',
            'Implements'                => 'attachments',
            'Attachments'               => 'attachments',
            'Loaders'                   => 'attachments',
            'Mowers'                    => 'attachments',
            'Hay Equipment'             => 'hay-equipment',
            'Balers'                    => 'hay-equipment',
            'Rakes'                     => 'hay-equipment',
            'Tedders'                   => 'hay-equipment',
        );

        if ( isset( $seg_map[ $category ] ) ) {
            $seg_slug = $seg_map[ $category ];
        } elseif ( stripos( $category, 'trailer' ) !== false ) {
            $seg_slug = 'trailers';
        } elseif ( stripos( $category, 'tractor' ) !== false ) {
            $seg_slug = 'tractors';
        } elseif ( stripos( $category, 'hay' ) !== false || stripos( $category, 'baler' ) !== false || stripos( $category, 'rake' ) !== false ) {
            $seg_slug = 'hay-equipment';
        } elseif ( stripos( $category, 'implement' ) !== false || stripos( $category, 'attachment' ) !== false || stripos( $category, 'loader' ) !== false || stripos( $category, 'mower' ) !== false ) {
            $seg_slug = 'attachments';
        } else {
            $seg_slug = 'misc';
        }

        $seg_labels = array(
            'tractors'         => 'Tractors',
            'trailers'         => 'Trailers',
            'utility-trailers' => 'Utility Trailers',
            'dump-trailers'    => 'Dump Trailers',
            'attachments'      => 'Attachments',
            'hay-equipment'    => 'Hay Equipment',
            'misc'             => 'Misc.',
        );
        $crumbs[] = array(
            'label' => $seg_labels[ $seg_slug ] ?? $category,
            'url'   => home_url( '/inventory/' . $seg_slug ),
        );
    }

    $year  = get_field( 'year' );
    $make  = get_field( 'make' );
    $model = get_field( 'model' );
    $title = trim( "$year $make $model" ) ?: get_the_title();
    $crumbs[] = array( 'label' => $title, 'url' => '' );
}

// ── Inventory segment pages (e.g. /inventory/tractors) ──────
elseif ( get_query_var( 'inventory_segment' ) ) {
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/inventory/all-units' ) );
    $seg = get_query_var( 'inventory_segment' );
    $seg_labels = array(
        'new'           => 'New',
        'used'          => 'Used',
        'tractors'      => 'Tractors',
        'trailers'      => 'Trailers',
        'utility-trailers' => 'Utility Trailers',
        'dump-trailers' => 'Dump Trailers',
        'attachments'   => 'Attachments',
        'hay-equipment' => 'Hay Equipment',
        'misc'          => 'Misc.',
    );
    $crumbs[] = array( 'label' => $seg_labels[ $seg ] ?? ucfirst( $seg ), 'url' => '' );
}

// ── All Inventory ─────────────────────────────────────────────
elseif ( is_page() && get_page_template_slug() === 'page-all-inventory.php' ) {
    $crumbs[] = array( 'label' => 'All Inventory', 'url' => '' );
}

// ── Brand pages ───────────────────────────────────────────────
elseif ( is_page() && get_page_template_slug() === 'page-brand.php' ) {
    $crumbs[] = array( 'label' => 'Brands', 'url' => home_url( '/brands' ) );
    $crumbs[] = array( 'label' => get_the_title(), 'url' => '' );
}

// ── Brands overview ───────────────────────────────────────────
elseif ( is_page() && get_page_template_slug() === 'page-brands.php' ) {
    $crumbs[] = array( 'label' => 'Brands', 'url' => '' );
}

// ── Showroom / In-Stock ───────────────────────────────────────
elseif ( is_page() && in_array( get_page_template_slug(), array( 'page-showroom-inventory.php', 'page-in-stock-inventory.php' ) ) ) {
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/inventory/all-units' ) );
    $crumbs[] = array( 'label' => get_the_title(), 'url' => '' );
}

// ── Services ─────────────────────────────────────────────────
elseif ( is_page() && in_array( get_page_template_slug(), array( 'page-service-request.php', 'page-parts-request.php' ) ) ) {
    $crumbs[] = array( 'label' => 'Services', 'url' => home_url( '/services' ) );
    $crumbs[] = array( 'label' => get_the_title(), 'url' => '' );
}

// ── Generic page fallback ─────────────────────────────────────
elseif ( is_page() ) {
    $parent_id = wp_get_post_parent_id( get_the_ID() );
    if ( $parent_id ) {
        $parent_title = get_the_title( $parent_id );
        if ( ! empty( $parent_title ) ) {
            $crumbs[] = array(
                'label' => $parent_title,
                'url'   => get_permalink( $parent_id ),
            );
        }
    }
    $page_title = get_the_title();
    $crumbs[] = array( 'label' => $page_title ?: 'Page', 'url' => '' );
}

// ── Fallback for other standard post types/archives ───────────
else {
    $fallback_title = '';
    if ( is_single() ) {
        $fallback_title = get_the_title();
    } elseif ( is_archive() ) {
        $fallback_title = get_the_archive_title();
    } elseif ( is_search() ) {
        $fallback_title = 'Search Results';
    } elseif ( is_404() ) {
        $fallback_title = 'Page Not Found';
    }
    if ( ! empty( $fallback_title ) ) {
        $crumbs[] = array( 'label' => $fallback_title, 'url' => '' );
    }
}

// Don't render if we only have "Home"
if ( count( $crumbs ) <= 1 ) return;

// ── JSON-LD Breadcrumb Schema for Google Rich Results ─────────
$schema_items = array();
foreach ( $crumbs as $i => $crumb ) {
    $schema_items[] = array(
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $crumb['label'] ?: 'Page',
        'item'     => ! empty( $crumb['url'] ) ? $crumb['url'] : home_url( add_query_arg( null, null ) ),
    );
}
$schema = array(
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => $schema_items,
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<nav aria-label="Breadcrumb" class="bg-slate-100 border-b border-slate-200 w-full">
    <div class="max-w-7xl mx-auto px-4 py-2.5 flex items-center flex-wrap gap-1.5">
        <?php foreach ( $crumbs as $i => $crumb ) :
            $is_last = ( $i === count( $crumbs ) - 1 );
        ?>
            <?php if ( $i > 0 ) : ?>
                <svg class="w-3 h-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                </svg>
            <?php endif; ?>

            <?php if ( $is_last || empty( $crumb['url'] ) ) : ?>
                <span class="text-xs font-black uppercase tracking-widest text-slate-900 truncate max-w-[200px]">
                    <?php echo esc_html( $crumb['label'] ); ?>
                </span>
            <?php else : ?>
                <a href="<?php echo esc_url( $crumb['url'] ); ?>"
                   class="text-xs font-black uppercase tracking-widest text-slate-500 hover:text-red-600 transition-colors">
                    <?php echo esc_html( $crumb['label'] ); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>
