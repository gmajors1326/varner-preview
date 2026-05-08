<?php
/* Template Name: In Stock Inventory */
get_header();

$filter_data = varner_get_filter_data();

$query_args = varner_build_inventory_query(
    array(
        array(
            'key'     => 'stock_status',
            'value'   => array( 'In Stock', 'Pending Sale' ),
            'compare' => 'IN',
        ),
    )
);

$inventory_query = new WP_Query( $query_args );
$total           = $inventory_query->found_posts;
?>

<!-- MAIN LISTING -->
<section class="py-12 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex flex-col lg:flex-row gap-12">
            
            <!-- LEFT SIDEBAR: FILTERS -->
            <?php include get_template_directory() . '/partials/facet-sidebar.php'; ?>

            <!-- RIGHT CONTENT: GRID -->
            <div class="flex-1 min-w-0">
                
                <!-- Results Meta -->
                <div class="flex items-center justify-between mb-8 gap-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <?php echo do_shortcode('[facetwp facet="inventory_counts"]'); ?>
                    </p>
                </div>

                <!-- Inventory grid -->
                <div class="facetwp-template grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    $query_args['facetwp'] = true;
                    $inventory_query = new WP_Query( $query_args );

                    if ( $inventory_query->have_posts() ) :
                        while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                            varner_include_equipment_card();
                        endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="col-span-full bg-white rounded-3xl border border-slate-200 p-20 text-center shadow-inner">
                            <p class="text-slate-400 font-black uppercase tracking-widest text-sm mb-4">No Units Found</p>
                            <button onclick="FWP.reset()" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Reset All Filters</button>
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