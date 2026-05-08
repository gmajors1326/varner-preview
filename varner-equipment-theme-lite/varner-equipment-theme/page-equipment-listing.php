<?php
/**
 * Template Name: Equipment Listing (Dynamic)
 * Description: A powerful SEO-focused listing template for categories and conditions.
 */

get_header();

// 1. Identify the segment (slug) from the query or page title
$slug = get_query_var('inventory_segment') ?: sanitize_title(get_the_title());
$seo = varner_get_segment_seo($slug);

// Fallback to "All Inventory" style if no specific SEO data found
if (!$seo) {
    $seo = array(
        'title' => get_the_title(),
        'h1'    => get_the_title(),
        'sub'   => 'Browse our high-performance inventory.',
        'blurb' => '',
        'filter' => array()
    );
}

// 2. Build Query
$filter_data = varner_get_filter_data();
$base_meta = array();

// Apply the hard-coded segment filters (e.g. category => Tractors)
if (!empty($seo['filter'])) {
    foreach ($seo['filter'] as $key => $vals) {
        $base_meta[] = array('key' => $key, 'value' => $vals, 'compare' => 'IN');
    }
}

$query_args = varner_build_inventory_query($base_meta);
$inventory_query = new WP_Query($query_args);
$total = $inventory_query->found_posts;

// 3. Active filter count for badge
$active_filter_count =
    count(array_filter((array)($_GET['category'] ?? []))) +
    count(array_filter((array)($_GET['make'] ?? []))) +
    count(array_filter((array)($_GET['condition'] ?? []))) +
    (!empty($_GET['year_min']) || !empty($_GET['year_max']) ? 1 : 0) +
    (!empty($_GET['price_min']) || !empty($_GET['price_max']) ? 1 : 0) +
    (!empty($_GET['s']) ? 1 : 0) +
    (!empty($_GET['stock_number']) ? 1 : 0) +
    (!empty($_GET['vin']) ? 1 : 0);
?>

<!-- SEO HERO -->
<section class="pt-36 pb-10 bg-slate-950 text-white relative overflow-hidden">
    <div class="absolute -right-24 -top-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
            <div class="max-w-3xl">
                <div class="text-red-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Varner Equipment Segment</div>
                <h1 class="text-5xl md:text-7xl font-black tracking-tight uppercase mb-4 leading-[1.1]"><?php echo esc_html($seo['h1']); ?></h1>
                <p class="text-lg text-slate-400 font-bold leading-relaxed"><?php echo esc_html($seo['sub']); ?></p>
            </div>
            <div class="bg-white/5 backdrop-blur-md border border-white/10 p-6 rounded-2xl shrink-0 min-w-[200px]">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">Available Units</div>
                <div class="text-4xl font-black text-white"><?php echo $total; ?></div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">Live Inventory</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($seo['blurb']) : ?>
<!-- SEO BLURB -->
<div class="bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <p class="text-slate-600 font-bold leading-relaxed max-w-4xl italic">
            "<?php echo esc_html($seo['blurb']); ?>"
        </p>
    </div>
</div>
<?php endif; ?>

<!-- MAIN LISTING -->
<section class="py-12 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex flex-col lg:flex-row gap-12">
            
            <!-- LEFT SIDEBAR: FILTERS -->
            <?php include get_template_directory() . '/partials/facet-sidebar.php'; ?>

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

                <!-- Inventory Grid -->
                <div class="facetwp-template grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    // 2. Build Query for FacetWP
                    $query_args = varner_build_inventory_query($base_meta);
                    $query_args['facetwp'] = true; // Enable FacetWP
                    $inventory_query = new WP_Query($query_args);

                    if ($inventory_query->have_posts()) : 
                        while ($inventory_query->have_posts()) : $inventory_query->the_post();
                            varner_include_equipment_card();
                        endwhile;
                        wp_reset_postdata(); 
                    else : ?>
                        <div class="col-span-full bg-white rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center shadow-inner">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <p class="text-slate-900 font-black uppercase tracking-widest text-sm mb-2">No Match Found</p>
                            <p class="text-slate-400 font-bold text-xs mb-8">We couldn't find any units matching your specific filters.</p>
                            <button onclick="FWP.reset()" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-lg active:scale-95">
                                Reset All Filters
                            </button>
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

    </div>
</section>

<?php get_footer(); ?>