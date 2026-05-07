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

<!-- FILTER DRAWER BACKDROP -->
<div id="vf-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden opacity-0 transition-opacity duration-300"></div>

<!-- FILTER DRAWER -->
<div id="vf-drawer" class="fixed top-0 left-0 h-full w-80 max-w-[90vw] bg-white z-[201] shadow-2xl -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto flex flex-col">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 sticky top-0 bg-white z-10 shrink-0">
        <span class="font-black text-sm uppercase tracking-widest text-slate-900">Filter &amp; Search</span>
        <button id="vf-drawer-close" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-all text-xl leading-none">×</button>
    </div>
    <div class="flex-1 overflow-y-auto p-6 space-y-8">
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Search Inventory</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_search"]'); ?>
        </div>
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Category</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_category"]'); ?>
        </div>
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Manufacturer</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_make"]'); ?>
        </div>
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Condition</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_condition"]'); ?>
        </div>
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Price Range</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_price"]'); ?>
        </div>
        <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Year</h3>
            <?php echo do_shortcode('[facetwp facet="inventory_year"]'); ?>
        </div>
        <div class="pt-4">
            <button onclick="FWP.reset()" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Clear All Filters</button>
        </div>
    </div>
</div>

<!-- SEO HERO -->
<section class="pt-60 pb-16 bg-slate-950 text-white relative overflow-hidden">
...
        <!-- Toolbar -->
        <div class="flex items-center justify-between mb-10 gap-4">
            <button id="vf-drawer-open" class="flex items-center gap-2.5 bg-slate-900 text-white px-6 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-xl active:scale-95 group">
                <svg class="w-4 h-4 transition-transform group-hover:rotate-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                Filter Inventory
            </button>
            
            <div class="flex items-center gap-4 md:gap-8">
                <p class="hidden sm:block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    <?php echo do_shortcode('[facetwp facet="inventory_counts"]'); ?>
                </p>
                <div class="flex items-center gap-3 border-l border-slate-200 pl-4 md:pl-8">
...
        <!-- Inventory Grid -->
        <div class="facetwp-template grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php 
            // 2. Build Query for FacetWP
            $query_args = varner_build_inventory_query($base_meta);
            $query_args['facetwp'] = true; // Enable FacetWP
            $inventory_query = new WP_Query($query_args);

            if ($inventory_query->have_posts()) : 
                while ($inventory_query->have_posts()) : $inventory_query->the_post();
                    $post_id         = get_the_ID();
                    $year            = get_field('year',           $post_id);
                    $make            = get_field('make',           $post_id);
                    $model           = get_field('model',          $post_id);
                    $price           = get_field('price',          $post_id);
                    $call_for_price  = get_field('call_for_price', $post_id);
                    $category        = get_field('category',       $post_id);
                    $condition       = get_field('condition',      $post_id);
                    $stock_number    = get_field('stock_number',   $post_id);
                    $length          = get_field('length',         $post_id);
                    $formatted_price = $call_for_price ? 'Call For Price' : (is_numeric($price) ? number_format($price) : (string)$price);
                    $images          = varner_get_card_images($post_id);
                    include get_template_directory() . '/partials/equipment-card.php';
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
</section>

<!-- MAIN LISTING -->
<section class="py-12 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Toolbar -->
        <div class="flex items-center justify-between mb-10 gap-4">
            <button id="vf-drawer-open" class="flex items-center gap-2.5 bg-slate-900 text-white px-6 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-xl active:scale-95 group">
                <svg class="w-4 h-4 transition-transform group-hover:rotate-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                Refine Results
                <?php if ($active_filter_count > 0) : ?>
                <span class="bg-red-600 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center leading-none"><?php echo $active_filter_count; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="flex items-center gap-4 md:gap-8">
                <p class="hidden sm:block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    Total <span class="text-slate-900"><?php echo $total; ?></span> units found
                </p>
                <div class="flex items-center gap-3 border-l border-slate-200 pl-4 md:pl-8">
                    <button onclick="window.print()" class="flex items-center gap-1.5 text-slate-400 hover:text-red-600 transition-colors text-[9px] font-black uppercase tracking-[0.15em] shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                    <span class="text-slate-200 font-bold text-[10px]">|</span>
                    <button id="vne-share-page" class="flex items-center gap-1.5 text-slate-400 hover:text-red-600 transition-colors text-[9px] font-black uppercase tracking-[0.15em] shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        Share
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory Grid -->
        <?php if ($inventory_query->have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php while ($inventory_query->have_posts()) : $inventory_query->the_post();
                $post_id         = get_the_ID();
                $year            = get_field('year',           $post_id);
                $make            = get_field('make',           $post_id);
                $model           = get_field('model',          $post_id);
                $price           = get_field('price',          $post_id);
                $call_for_price  = get_field('call_for_price', $post_id);
                $category        = get_field('category',       $post_id);
                $condition       = get_field('condition',      $post_id);
                $stock_number    = get_field('stock_number',   $post_id);
                $length          = get_field('length',         $post_id);
                $formatted_price = $call_for_price ? 'Call For Price' : (is_numeric($price) ? number_format($price) : (string)$price);
                $images          = varner_get_card_images($post_id);
                include get_template_directory() . '/partials/equipment-card.php';
            endwhile;
            wp_reset_postdata(); ?>
        </div>
        <?php else : ?>
        <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center shadow-inner">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-slate-900 font-black uppercase tracking-widest text-sm mb-2">No Match Found</p>
            <p class="text-slate-400 font-bold text-xs mb-8">We couldn't find any units matching your specific filters in this segment.</p>
            <a href="<?php echo esc_url(get_permalink()); ?>" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-lg active:scale-95">
                Reset Segment Filters
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

<script>
(function () {
    var drawer   = document.getElementById('vf-drawer');
    var backdrop = document.getElementById('vf-backdrop');
    var openBtn  = document.getElementById('vf-drawer-open');
    var closeBtn = document.getElementById('vf-drawer-close');

    function open() {
        backdrop.classList.remove('hidden');
        requestAnimationFrame(function () {
            backdrop.classList.remove('opacity-0');
            drawer.classList.remove('-translate-x-full');
        });
        document.body.style.overflow = 'hidden';
    }

    function close() {
        backdrop.classList.add('opacity-0');
        drawer.classList.add('-translate-x-full');
        setTimeout(function () { backdrop.classList.add('hidden'); }, 300);
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);
})();
</script>

<?php get_footer(); ?>