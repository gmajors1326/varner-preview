<?php
/* Template Name: All Inventory */
get_header();

if ( ! function_exists('get_field') ) {
    echo '<div class="p-20 text-center font-bold">Theme Error: ACF is required.</div>';
    get_footer();
    return;
}

$selected_categories = array_map( 'sanitize_text_field', (array) ( $_GET['category'] ?? array() ) );
$filter_data = varner_get_filter_data( array(), $selected_categories );

// Safer count query
$count_args = varner_build_inventory_query(array(), -1);
$count_args['posts_per_page'] = -1;
$count_args['fields'] = 'ids';
unset( $count_args['paged'] );
$total = count(get_posts($count_args));
?>

<section class="pt-36 pb-16 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Page header -->
        <div class="mb-10">
            <div class="text-red-600 font-black text-xs uppercase tracking-[0.4em] mb-4">Varner Equipment Collection</div>
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight uppercase mb-4 leading-[1.1]">All Inventory</h1>
            <p class="text-lg text-slate-500 font-bold max-w-2xl leading-relaxed italic border-l-4 border-red-600 pl-6">"The most complete selection of heavy-duty equipment in Western Colorado."</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-12">
            
            <!-- LEFT SIDEBAR: FILTERS -->
            <?php include get_template_directory() . '/partials/inventory-sidebar.php'; ?>

            <!-- RIGHT CONTENT: GRID -->
            <div class="flex-1">
                
                <?php 
                    $query_args = varner_build_inventory_query(array(), 12);
                    $inventory_query = new WP_Query( $query_args );
                    $current_page = max( 1, intval( get_query_var( 'paged' ) ?: ( get_query_var( 'page' ) ?: ( $_GET['paged'] ?? ( $_GET['page'] ?? 1 ) ) ) ) );
                    $total_found  = intval( $inventory_query->found_posts );
                    $raw_path     = wp_unslash( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) );
                    $reset_url    = home_url( preg_replace( '#/page/\d+/?#', '/', $raw_path ) );
                    if ( strpos( $reset_url, '/inventory/' ) === false ) {
                        $reset_url = home_url( '/inventory/all-units/' );
                    }
                ?>

                <!-- Results Meta -->
                <div class="flex items-center justify-between mb-8 gap-4">
                    <p class="text-xs font-black text-slate-500 uppercase tracking-[0.2em]">
                        <?php echo esc_html( varner_get_results_count_text( $inventory_query, 12 ) ); ?>
                    </p>
                </div>

                <!-- Inventory grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    if ( $inventory_query->have_posts() ) :
                        while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                            varner_include_equipment_card();
                        endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="col-span-full bg-white rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center shadow-inner">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <p class="text-slate-900 font-black uppercase tracking-widest text-sm mb-2">No Units Found</p>
                            <p class="text-slate-500 font-bold text-xs mb-8">Try adjusting your filters to find what you're looking for.</p>
                            <a href="<?php echo esc_url( $reset_url ); ?>" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-xs hover:bg-red-600 transition-all shadow-lg active:scale-95">
                                Reset All Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php varner_render_pagination( $inventory_query ); ?>
            </div>

        </div>

    </div>
</section>

<?php get_footer(); ?>
