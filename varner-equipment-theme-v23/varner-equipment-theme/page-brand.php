<?php
/**
 * Template Name: Brand Landing
 * Description: Shows inventory for a specific brand (matches page title to equipment make).
 */

get_header();

global $wpdb;

$brand_name = get_the_title();

$brand_logos = array(
    'Mahindra'        => 'Mahindra_white.png',
    'Big Tex'         => 'BigTex_white.png',
    'Deutz-Fahr'      => 'DuetzFahr_white.png',
    'KRONE'           => 'KRONE_white.png',
    'MacDon'          => 'MacDon_white.png',
    'McHale'          => 'McHALE_white.png',
    'ROXOR'           => 'ROXR_white.png',
    'Titan Trailers'  => 'TitanTrailersMFG_white.png',
    'Triton'          => 'Triton_white.png',
    'TYM'             => 'TYM_white.png',
    'Zetor'           => 'Zetor_white.png',
    'CM Truck Beds'   => 'CMTruckbeds_white.png',
);
$assets_base = trailingslashit( get_template_directory_uri() ) . 'assets/';
$brand_logo  = isset( $brand_logos[ $brand_name ] ) ? $assets_base . $brand_logos[ $brand_name ] : '';

// ── Active filters ──────────────────────────────────────────────────────────
$f_cats = array_map( 'sanitize_text_field', (array) ( $_GET['category']  ?? [] ) );
$f_cds  = array_map( 'sanitize_text_field', (array) ( $_GET['condition'] ?? [] ) );
$f_ymin = intval( $_GET['year_min']  ?? 0 );
$f_ymax = intval( $_GET['year_max']  ?? 0 );
$f_pmin = intval( $_GET['price_min'] ?? 0 );
$f_pmax = intval( $_GET['price_max'] ?? 0 );
$fs     = sanitize_text_field( $_GET['s'] ?? '' );
$has_filters = $f_cats || $f_cds || $f_ymin || $f_ymax || $f_pmin || $f_pmax || $fs;

$page_url = get_permalink();

// ── Brand-specific filter options ───────────────────────────────────────────
$brand_safe = esc_sql( $brand_name );

$brand_categories = $wpdb->get_results( $wpdb->prepare(
    "SELECT pm.meta_value AS val, COUNT(*) AS cnt
     FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'make' AND pm2.meta_value = %s
     WHERE p.post_type = 'equipment' AND p.post_status = 'publish'
       AND pm.meta_key = 'category' AND pm.meta_value != ''
     GROUP BY pm.meta_value ORDER BY cnt DESC",
    $brand_name
), OBJECT_K );

$brand_conditions = $wpdb->get_results( $wpdb->prepare(
    "SELECT pm.meta_value AS val, COUNT(*) AS cnt
     FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'make' AND pm2.meta_value = %s
     WHERE p.post_type = 'equipment' AND p.post_status = 'publish'
       AND pm.meta_key = 'condition' AND pm.meta_value != ''
     GROUP BY pm.meta_value ORDER BY cnt DESC",
    $brand_name
), OBJECT_K );

// ── Query ───────────────────────────────────────────────────────────────────
$base_meta = array( array( 'key' => 'make', 'value' => $brand_name, 'compare' => 'LIKE' ) );
$args      = varner_build_inventory_query( $base_meta, -1 );
$brand_query = new WP_Query( $args );
?>

<section class="pt-36 pb-10 bg-slate-950 text-white shadow-2xl relative overflow-hidden">
    <!-- Decorative background element -->
    <div class="absolute -right-24 -bottom-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto px-4 relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-8">
        <div class="flex-1">
            <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-red-500 hover:text-white transition-colors mb-4 group">
                <svg class="w-3 h-3 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                Back to All Brands
            </a>
            
            <?php if ( $brand_logo ) : ?>
                <img src="<?php echo esc_url( $brand_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" class="h-16 md:h-20 w-auto object-contain drop-shadow-2xl mb-4">
            <?php else : ?>
                <h1 class="text-5xl md:text-7xl font-black tracking-tight uppercase leading-[1.1] mb-2"><?php echo esc_html( $brand_name ); ?></h1>
            <?php endif; ?>
            <p class="text-slate-400 font-bold text-sm"><?php echo $brand_query->found_posts; ?> unit<?php echo $brand_query->found_posts !== 1 ? 's' : ''; ?> <?php echo $has_filters ? 'match your filters' : 'in stock'; ?></p>
        </div>
    </div>
</section>

<!-- MAIN LISTING -->
<section class="py-12 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex flex-col lg:flex-row gap-12">
            
            <!-- LEFT SIDEBAR: FILTERS -->
            <aside class="w-full lg:w-72 shrink-0">
                <div class="sticky top-28 space-y-8">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-8">
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Search <?php echo esc_html($brand_name); ?></h3>
                            <?php echo do_shortcode('[facetwp facet="inventory_search"]'); ?>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Category</h3>
                            <?php echo do_shortcode('[facetwp facet="inventory_category"]'); ?>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Condition</h3>
                            <?php echo do_shortcode('[facetwp facet="inventory_condition"]'); ?>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Price Range</h3>
                            <div class="px-1">
                                <?php echo do_shortcode('[facetwp facet="inventory_price"]'); ?>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Year</h3>
                            <?php echo do_shortcode('[facetwp facet="inventory_year"]'); ?>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <button onclick="FWP.reset()" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Clear All Filters</button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- RIGHT CONTENT: GRID -->
            <div class="flex-1">
                
                <!-- Results Meta -->
                <div class="flex items-center justify-between mb-8 gap-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <?php echo do_shortcode('[facetwp facet="inventory_counts"]'); ?>
                    </p>
                    <button onclick="window.print()" class="flex items-center gap-1.5 text-slate-400 hover:text-red-600 transition-colors text-[9px] font-black uppercase tracking-[0.15em] shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>

                <!-- Inventory grid -->
                <div class="facetwp-template grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    // Use FacetWP enabled query
                    $args['facetwp'] = true;
                    $brand_query = new WP_Query( $args );

                    if ( $brand_query->have_posts() ) :
                        while ( $brand_query->have_posts() ) : $brand_query->the_post();
                            $post_id        = get_the_ID();
                            $year           = get_field( 'year', $post_id );
                            $make           = get_field( 'make', $post_id );
                            $model          = get_field( 'model', $post_id );
                            $category       = get_field( 'category', $post_id );
                            $condition      = get_field( 'condition', $post_id );
                            $stock_status   = get_field( 'stock_status', $post_id );
                            $price          = get_field( 'price', $post_id );
                            $call_for_price = get_field( 'call_for_price', $post_id );
                            $formatted_price = $call_for_price ? 'Call for Price' : ( is_numeric( $price ) ? number_format( $price ) : ( $price ?: '—' ) );
                            $stock_number   = get_field( 'stock_number', $post_id );
                            $length         = get_field( 'length', $post_id );
                            $images         = varner_get_card_images( $post_id );
                            include get_template_directory() . '/partials/equipment-card.php';
                        endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="col-span-full bg-white border border-slate-200 rounded-3xl p-20 text-center shadow-inner">
                            <p class="font-black uppercase tracking-widest text-slate-400 text-sm mb-4">No Units Found</p>
                            <button onclick="FWP.reset()" class="inline-block bg-red-600 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-900 transition-all">Clear All Filters</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-12 flex justify-center">
                    <?php echo do_shortcode('[facetwp facet="inventory_pagination"]'); ?>
                </div>
            </div>

        </div>

    </div>
</section>

<?php get_footer(); ?>
