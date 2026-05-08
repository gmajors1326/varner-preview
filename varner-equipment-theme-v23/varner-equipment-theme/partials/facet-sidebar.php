<?php
/**
 * Partial: FacetWP Filter Sidebar
 *
 * Optional variables (set before including this partial):
 *   $facet_search_label  — label for the search box  (default: 'Search Inventory')
 *   $facet_show_condition — bool, show Condition facet (default: true)
 */
$facet_search_label    = $facet_search_label    ?? 'Search Inventory';
$facet_show_condition  = $facet_show_condition  ?? true;
?>
<aside class="w-full lg:w-72 shrink-0">
    <div class="sticky top-28 space-y-8">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-8">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4"><?php echo esc_html( $facet_search_label ); ?></h3>
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
            <?php if ( $facet_show_condition ) : ?>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Condition</h3>
                <?php echo do_shortcode('[facetwp facet="inventory_condition"]'); ?>
            </div>
            <?php endif; ?>
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
