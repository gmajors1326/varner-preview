<?php
/**
 * Silent Brands Hub — /brands/
 * Location: wp-content/themes/varner-equipment-theme-v23-lite-4/page-brands.php
 * Hidden from nav; reachable via footer link + sitemap; indexable.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
$brands = varner_get_brands();
?>
<main id="main-content" class="max-w-7xl mx-auto px-4 py-10">

    <nav aria-label="Breadcrumb" class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-6">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-red-600">Home</a>
        <span class="mx-2">/</span><span class="text-slate-900">Brands</span>
    </nav>

    <header class="mb-10">
        <h1 class="text-3xl md:text-5xl font-black tracking-tighter text-slate-900">Shop Equipment by Brand</h1>
        <p class="mt-3 max-w-2xl text-slate-600">
            Varner Equipment carries the tractor, trailer, and hay brands that Western Colorado
            depends on. Explore each lineup and browse live inventory in Delta, CO.
        </p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ( $brands as $slug => $b ) :
            $logo_path = get_template_directory() . '/assets/brands/' . $b['logo'];
            $logo_url  = get_template_directory_uri() . '/assets/brands/' . $b['logo'];
            $has_logo  = ! empty( $b['logo'] ) && file_exists( $logo_path );
        ?>
        <a href="<?php echo esc_url( home_url( '/brands/' . $slug . '/' ) ); ?>"
           class="group flex flex-col rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:border-red-500 transition-all bg-white">
            <div class="h-16 flex items-center mb-4">
                <?php if ( $has_logo ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $b['name'] ); ?> logo"
                         class="max-h-16 w-auto object-contain" width="180" height="64" loading="lazy">
                <?php else : ?>
                    <span class="text-2xl font-black tracking-tighter text-slate-900"><?php echo esc_html( $b['name'] ); ?></span>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-black uppercase tracking-widest text-red-600"><?php echo esc_html( $b['category'] ); ?></span>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed"><?php echo esc_html( $b['tagline'] ); ?></p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-black text-slate-900 group-hover:text-red-600">
                View <?php echo esc_html( $b['name'] ); ?> &rarr;
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</main>

<?php
/* BreadcrumbList + ItemList JSON-LD for the hub */
$item_els = array();
$pos = 1;
foreach ( $brands as $slug => $b ) {
    $item_els[] = array(
        '@type'    => 'ListItem',
        'position' => $pos++,
        'name'     => $b['name'],
        'url'      => home_url( '/brands/' . $slug . '/' ),
    );
}
$hub_ld = array(
    '@context' => 'https://schema.org',
    '@graph'   => array(
        array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
                array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Brands', 'item' => home_url( '/brands/' ) ),
            ),
        ),
        array(
            '@type'           => 'ItemList',
            'name'            => 'Equipment Brands at Varner Equipment',
            'numberOfItems'   => count( $item_els ),
            'itemListElement' => $item_els,
        ),
    ),
);
echo '<script type="application/ld+json">' . wp_json_encode( $hub_ld, JSON_UNESCAPED_SLASHES ) . '</script>';

get_footer();
