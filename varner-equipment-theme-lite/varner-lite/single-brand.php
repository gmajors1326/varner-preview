<?php
/**
 * Dedicated Brand Landing Page — /brands/<slug>/
 * Location: wp-content/themes/varner-equipment-theme-v23-lite-4/single-brand.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$slug  = sanitize_title( get_query_var( 'brand_name' ) );
$brand = varner_get_brand( $slug );

if ( ! $brand ) {
    status_header( 404 );
    get_template_part( '404' );
    return;
}

$logo_path = get_template_directory() . '/assets/brands/' . $brand['logo'];
$logo_url  = get_template_directory_uri() . '/assets/brands/' . $brand['logo'];
$has_logo  = ! empty( $brand['logo'] ) && file_exists( $logo_path );

// Filtered-inventory URL for the "shop all" CTA
$shop_url = home_url( '/inventory/all-units/?make=' . rawurlencode( $brand['make'] ) );

// Pull a sample of this brand's live units
$units = new WP_Query( array(
    'post_type'      => 'equipment',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'meta_query'     => array(
        array( 'key' => 'make', 'value' => $brand['make'], 'compare' => '=' ),
    ),
) );

get_header();
?>
<main id="main-content" class="max-w-7xl mx-auto px-4 py-10">

    <nav aria-label="Breadcrumb" class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-6">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-red-600">Home</a>
        <span class="mx-2">/</span>
        <a href="<?php echo esc_url( home_url( '/brands/' ) ); ?>" class="hover:text-red-600">Brands</a>
        <span class="mx-2">/</span><span class="text-slate-900"><?php echo esc_html( $brand['name'] ); ?></span>
    </nav>

    <header class="flex flex-col md:flex-row md:items-center gap-6 mb-10">
        <?php if ( $has_logo ) : ?>
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand['name'] ); ?> logo"
                 class="max-h-20 w-auto object-contain" width="220" height="80">
        <?php endif; ?>
        <div>
            <span class="text-[11px] font-black uppercase tracking-widest text-red-600"><?php echo esc_html( $brand['category'] ); ?> &middot; Delta, CO</span>
            <h1 class="text-3xl md:text-5xl font-black tracking-tighter text-slate-900 mt-1">
                <?php echo esc_html( $brand['name'] ); ?> <span class="text-slate-400">at Varner Equipment</span>
            </h1>
            <p class="mt-2 text-lg text-slate-700 font-semibold"><?php echo esc_html( $brand['tagline'] ); ?></p>
        </div>
    </header>

    <div class="max-w-3xl text-slate-600 leading-relaxed mb-8">
        <p><?php echo esc_html( $brand['description'] ); ?></p>
    </div>

    <a href="<?php echo esc_url( $shop_url ); ?>"
       class="inline-flex bg-red-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg hover:bg-red-700 transition-all active:scale-95 mb-12">
        Shop All <?php echo esc_html( $brand['name'] ); ?> Inventory
    </a>

    <?php if ( $units->have_posts() ) : ?>
        <h2 class="text-2xl font-black tracking-tighter text-slate-900 mb-6"><?php echo esc_html( $brand['name'] ); ?> Currently in Stock</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ( $units->have_posts() ) : $units->the_post();
                $post_id        = get_the_ID();
                $year           = get_field( 'year', $post_id );
                $make           = get_field( 'make', $post_id );
                $model          = get_field( 'model', $post_id );
                $category       = get_field( 'category', $post_id );
                $condition      = get_field( 'condition', $post_id );
                $price          = get_field( 'price', $post_id );
                $formatted_price = function_exists('varner_format_price') ? varner_format_price( $price ) : ( ( is_numeric($price) && $price > 0 ) ? number_format( $price, 2 ) : 'Call For Price' );
                $stock_number   = get_field( 'stock_number', $post_id );
                $length         = get_field( 'length', $post_id );
                $images         = function_exists('varner_get_card_images') ? varner_get_card_images( $post_id ) : array();
                include get_template_directory() . '/partials/equipment-card.php';
            endwhile; ?>
        </div>
    <?php else : ?>
        <p class="text-slate-500">No <?php echo esc_html( $brand['name'] ); ?> units are listed online right now.
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="text-red-600 font-bold">Contact us</a>
            — we can source what you need.</p>
    <?php endif; wp_reset_postdata(); ?>
</main>

<?php
/* BreadcrumbList + ItemList of the brand's units */
$graph = array(
    array(
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array(
            array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
            array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Brands', 'item' => home_url( '/brands/' ) ),
            array( '@type' => 'ListItem', 'position' => 3, 'name' => $brand['name'], 'item' => home_url( '/brands/' . $slug . '/' ) ),
        ),
    ),
);

if ( $units->have_posts() ) {
    $els = array(); $pos = 1;
    while ( $units->have_posts() ) { $units->the_post();
        $els[] = array(
            '@type'    => 'ListItem',
            'position' => $pos++,
            'url'      => get_permalink(),
            'name'     => get_the_title(),
        );
    }
    wp_reset_postdata();
    $graph[] = array(
        '@type'           => 'ItemList',
        'name'            => $brand['name'] . ' inventory at Varner Equipment',
        'numberOfItems'   => count( $els ),
        'itemListElement' => $els,
    );
}

echo '<script type="application/ld+json">' . wp_json_encode(
    array( '@context' => 'https://schema.org', '@graph' => $graph ),
    JSON_UNESCAPED_SLASHES
) . '</script>';

get_footer();
