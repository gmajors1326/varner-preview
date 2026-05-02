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

<section class="py-16 bg-slate-950 text-white">
    <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center gap-6">
        <div class="flex-1">
            <?php if ( $brand_logo ) : ?>
                <img src="<?php echo esc_url( $brand_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" class="h-20 w-auto object-contain drop-shadow mb-3">
            <?php else : ?>
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter"><?php echo esc_html( $brand_name ); ?></h1>
            <?php endif; ?>
            <p class="text-slate-400 font-bold text-sm"><?php echo $brand_query->found_posts; ?> unit<?php echo $brand_query->found_posts !== 1 ? 's' : ''; ?> <?php echo $has_filters ? 'match your filters' : 'in stock'; ?></p>
        </div>
        <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-red-500 transition-colors">← All Brands</a>
    </div>
</section>

<section class="bg-white border-b border-slate-200 sticky top-[72px] z-30 shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <form method="GET" action="<?php echo esc_url( $page_url ); ?>" id="brand-filter-form" class="flex flex-wrap items-center gap-x-6 gap-y-0 py-0">

            <?php if ( ! empty( $brand_categories ) ) : ?>
            <div class="relative group">
                <button type="button" class="brand-filter-toggle flex items-center gap-1.5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 transition-colors border-b-2 border-transparent group-[.open]:border-red-600 group-[.open]:text-red-600">
                    Category
                    <?php if ( $f_cats ) : ?><span class="bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]"><?php echo count($f_cats); ?></span><?php endif; ?>
                    <svg class="w-3 h-3 transition-transform group-[.open]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="brand-filter-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 space-y-2.5 min-w-[180px] z-50">
                    <?php foreach ( $brand_categories as $key => $obj ) : ?>
                    <label class="flex items-center justify-between gap-3 cursor-pointer group/cb">
                        <span class="flex items-center gap-2">
                            <input type="checkbox" name="category[]" value="<?php echo esc_attr( $key ); ?>"
                                   <?php checked( in_array( $key, $f_cats, true ) ); ?>
                                   class="brand-filter-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                            <span class="text-sm font-bold text-slate-700 group-hover/cb:text-slate-900 leading-tight"><?php echo esc_html( $key ); ?></span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold">(<?php echo intval( $obj->cnt ); ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $brand_conditions ) ) : ?>
            <div class="relative group">
                <button type="button" class="brand-filter-toggle flex items-center gap-1.5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 transition-colors border-b-2 border-transparent group-[.open]:border-red-600 group-[.open]:text-red-600">
                    Condition
                    <?php if ( $f_cds ) : ?><span class="bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]"><?php echo count($f_cds); ?></span><?php endif; ?>
                    <svg class="w-3 h-3 transition-transform group-[.open]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="brand-filter-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 space-y-2.5 min-w-[160px] z-50">
                    <?php foreach ( $brand_conditions as $key => $obj ) : ?>
                    <label class="flex items-center justify-between gap-3 cursor-pointer group/cb">
                        <span class="flex items-center gap-2">
                            <input type="checkbox" name="condition[]" value="<?php echo esc_attr( $key ); ?>"
                                   <?php checked( in_array( $key, $f_cds, true ) ); ?>
                                   class="brand-filter-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                            <span class="text-sm font-bold text-slate-700 group-hover/cb:text-slate-900"><?php echo esc_html( $key ); ?></span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold">(<?php echo intval( $obj->cnt ); ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Year range -->
            <div class="relative group">
                <button type="button" class="brand-filter-toggle flex items-center gap-1.5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 transition-colors border-b-2 border-transparent group-[.open]:border-red-600 group-[.open]:text-red-600">
                    Year
                    <?php if ( $f_ymin || $f_ymax ) : ?><span class="bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]">✓</span><?php endif; ?>
                    <svg class="w-3 h-3 transition-transform group-[.open]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="brand-filter-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 z-50 min-w-[220px]">
                    <div class="flex items-center gap-2">
                        <input type="number" name="year_min" value="<?php echo $f_ymin ?: ''; ?>" placeholder="Min" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                        <span class="text-slate-400 font-bold text-sm shrink-0">–</span>
                        <input type="number" name="year_max" value="<?php echo $f_ymax ?: ''; ?>" placeholder="Max" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                        <button type="submit" class="bg-slate-900 text-white px-3 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">Go</button>
                    </div>
                </div>
            </div>

            <!-- Price range -->
            <div class="relative group">
                <button type="button" class="brand-filter-toggle flex items-center gap-1.5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 transition-colors border-b-2 border-transparent group-[.open]:border-red-600 group-[.open]:text-red-600">
                    Price
                    <?php if ( $f_pmin || $f_pmax ) : ?><span class="bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]">✓</span><?php endif; ?>
                    <svg class="w-3 h-3 transition-transform group-[.open]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="brand-filter-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 z-50 min-w-[220px]">
                    <div class="flex items-center gap-2">
                        <input type="number" name="price_min" value="<?php echo $f_pmin ?: ''; ?>" placeholder="$Min" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                        <span class="text-slate-400 font-bold text-sm shrink-0">–</span>
                        <input type="number" name="price_max" value="<?php echo $f_pmax ?: ''; ?>" placeholder="$Max" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                        <button type="submit" class="bg-slate-900 text-white px-3 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">Go</button>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative group">
                <button type="button" class="brand-filter-toggle flex items-center gap-1.5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 transition-colors border-b-2 border-transparent group-[.open]:border-red-600 group-[.open]:text-red-600">
                    Search
                    <?php if ( $fs ) : ?><span class="bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]">✓</span><?php endif; ?>
                    <svg class="w-3 h-3 transition-transform group-[.open]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="brand-filter-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 z-50 min-w-[240px]">
                    <div class="flex gap-2">
                        <input type="text" name="s" value="<?php echo esc_attr( $fs ); ?>" placeholder="Keyword…" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
                        <button type="submit" class="bg-slate-900 text-white px-3 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">Go</button>
                    </div>
                </div>
            </div>

            <?php if ( $has_filters ) : ?>
            <a href="<?php echo esc_url( $page_url ); ?>" class="ml-auto py-4 text-[10px] font-black uppercase tracking-widest text-red-600 hover:text-red-700 transition-colors">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>
</section>

<?php if ( $has_filters ) : ?>
<div class="bg-slate-50 border-b border-slate-200 py-3 px-4">
    <div class="max-w-7xl mx-auto flex flex-wrap gap-2 items-center">
        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Active:</span>
        <?php foreach ( $f_cats as $v ) : ?>
        <a href="<?php echo esc_url( varner_remove_filter( 'category', $v ) ); ?>" class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">× <?php echo esc_html( $v ); ?></a>
        <?php endforeach; ?>
        <?php foreach ( $f_cds as $v ) : ?>
        <a href="<?php echo esc_url( varner_remove_filter( 'condition', $v ) ); ?>" class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">× <?php echo esc_html( $v ); ?></a>
        <?php endforeach; ?>
        <?php if ( $f_ymin || $f_ymax ) : ?>
        <a href="<?php echo esc_url( varner_remove_range_filter( 'year_min', 'year_max' ) ); ?>" class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">× Year <?php echo esc_html( ( $f_ymin ?: '…' ) . '–' . ( $f_ymax ?: '…' ) ); ?></a>
        <?php endif; ?>
        <?php if ( $f_pmin || $f_pmax ) : ?>
        <a href="<?php echo esc_url( varner_remove_range_filter( 'price_min', 'price_max' ) ); ?>" class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">× $<?php echo esc_html( ( $f_pmin ? number_format($f_pmin) : '0' ) . '–' . ( $f_pmax ? '$'.number_format($f_pmax) : '…' ) ); ?></a>
        <?php endif; ?>
        <?php if ( $fs ) : ?>
        <a href="<?php echo esc_url( varner_remove_filter( 's' ) ); ?>" class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">× "<?php echo esc_html( $fs ); ?>"</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<section class="py-16 bg-slate-100">
    <div class="max-w-7xl mx-auto px-4">
        <?php if ( $brand_query->have_posts() ) : ?>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php while ( $brand_query->have_posts() ) : $brand_query->the_post();
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
            </div>
        <?php else : ?>
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8 text-center">
                <p class="font-bold text-slate-600 mb-4">No units found<?php echo $has_filters ? ' matching your filters' : ' for this brand yet'; ?>.</p>
                <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( $page_url ); ?>" class="inline-block bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-700 transition-all">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var toggles = document.querySelectorAll('.brand-filter-toggle');

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var parent = btn.closest('.group');
            var isOpen = parent.classList.contains('open');
            // close all
            document.querySelectorAll('.group.open').forEach(function (g) { g.classList.remove('open'); g.querySelector('.brand-filter-dropdown').classList.add('hidden'); });
            if (!isOpen) {
                parent.classList.add('open');
                parent.querySelector('.brand-filter-dropdown').classList.remove('hidden');
            }
        });
    });

    // Close on outside click
    document.addEventListener('click', function () {
        document.querySelectorAll('.group.open').forEach(function (g) { g.classList.remove('open'); g.querySelector('.brand-filter-dropdown').classList.add('hidden'); });
    });

    // Auto-submit checkboxes
    document.querySelectorAll('.brand-filter-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            document.getElementById('brand-filter-form').submit();
        });
    });
})();
</script>

<?php get_footer(); ?>
