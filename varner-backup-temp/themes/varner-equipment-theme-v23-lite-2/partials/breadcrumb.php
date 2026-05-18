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

// ── Equipment detail page ────────────────────────────────────
if ( is_singular( 'equipment' ) ) {
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/all-inventory' ) );

    $category = get_field( 'category' );
    if ( $category ) {
        // Map category to its segment slug
        $seg_map = array(
            'Compact Tractors'    => 'tractors',
            'Utility Tractors'    => 'tractors',
            'Tractors'            => 'tractors',
            'Commercial Trailers' => 'trailers',
            'Dump Trailers'       => 'trailers',
            'Flatbed Trailers'    => 'trailers',
            'Utility Trailers'    => 'trailers',
            'Horse Trailers'      => 'trailers',
            'Livestock Trailers'  => 'trailers',
            'Trailers'            => 'trailers',
            'Implements'          => 'attachments',
            'Attachments'         => 'attachments',
            'Loaders'             => 'attachments',
            'Hay Equipment'       => 'hay-equipment',
            'Balers'              => 'hay-equipment',
            'Rakes'               => 'hay-equipment',
            'Tedders'             => 'hay-equipment',
        );
        $seg_slug = $seg_map[ $category ] ?? 'misc';
        $seg_labels = array(
            'tractors'      => 'Tractors',
            'trailers'      => 'Trailers',
            'attachments'   => 'Attachments',
            'hay-equipment' => 'Hay Equipment',
            'misc'          => 'Misc.',
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
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/all-inventory' ) );
    $seg = get_query_var( 'inventory_segment' );
    $seg_labels = array(
        'new'           => 'New',
        'used'          => 'Used',
        'tractors'      => 'Tractors',
        'trailers'      => 'Trailers',
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
    $crumbs[] = array( 'label' => 'Inventory', 'url' => home_url( '/all-inventory' ) );
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
        $crumbs[] = array(
            'label' => get_the_title( $parent_id ),
            'url'   => get_permalink( $parent_id ),
        );
    }
    $crumbs[] = array( 'label' => get_the_title(), 'url' => '' );
}

// Don't render if we only have "Home"
if ( count( $crumbs ) <= 1 ) return;

// ── JSON-LD Breadcrumb Schema for Google Rich Results ─────────
$schema_items = array();
foreach ( $crumbs as $i => $crumb ) {
    $schema_items[] = array(
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $crumb['label'],
        'item'     => ! empty( $crumb['url'] ) ? $crumb['url'] : ( get_permalink() ?: home_url( '/' ) ),
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
                <svg class="w-3 h-3 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                </svg>
            <?php endif; ?>

            <?php if ( $is_last || empty( $crumb['url'] ) ) : ?>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-900 truncate max-w-[200px]">
                    <?php echo esc_html( $crumb['label'] ); ?>
                </span>
            <?php else : ?>
                <a href="<?php echo esc_url( $crumb['url'] ); ?>"
                   class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-red-600 transition-colors">
                    <?php echo esc_html( $crumb['label'] ); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>
