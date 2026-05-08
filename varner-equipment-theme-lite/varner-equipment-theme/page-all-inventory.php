<?php
/* Template Name: All Inventory */
get_header();

if ( ! function_exists('get_field') ) {
    echo '<div class="p-20 text-center font-bold">Theme Error: ACF is required.</div>';
    get_footer();
    return;
}

$filter_data = varner_get_filter_data();

$query_args = varner_build_inventory_query( array() );

$inventory_query = new WP_Query( $query_args );
$total           = $inventory_query->found_posts;

?>

<section class="pt-36 pb-16 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Page header -->
        <div class="mb-10">
            <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Varner Equipment Collection</div>
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight uppercase mb-4 leading-[1.1]">All Inventory</h1>
            <p class="text-lg text-slate-500 font-bold max-w-2xl leading-relaxed italic border-l-4 border-red-600 pl-6">"The most complete selection of heavy-duty equipment in Western Colorado."</p>
        </div>

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
                    <button onclick="window.print()" class="flex items-center gap-1.5 text-slate-400 hover:text-red-600 transition-colors text-[9px] font-black uppercase tracking-[0.15em] shrink-0 border-l border-slate-200 pl-4">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>

                <!-- Inventory grid -->
                <div class="facetwp-template grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    $query_args = varner_build_inventory_query(array());
                    $query_args['facetwp'] = true;
                    $inventory_query = new WP_Query( $query_args );

                    if ( $inventory_query->have_posts() ) :
                        while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                            varner_include_equipment_card();
                        endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="col-span-full bg-white rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center shadow-inner">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <p class="text-slate-900 font-black uppercase tracking-widest text-sm mb-2">No Units Found</p>
                            <p class="text-slate-400 font-bold text-xs mb-8">Try adjusting your filters to find what you're looking for.</p>
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

<?php get_footer(); ?>
