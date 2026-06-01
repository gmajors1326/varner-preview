<?php
/* Template Name: Showroom Inventory */
get_header();

$filter_data = varner_get_filter_data();

$query_args = varner_build_inventory_query(
    array(
        array(
            'key'   => 'condition',
            'value' => 'New',
        ),
    )
);

$inventory_query = new WP_Query( $query_args );
$total           = $inventory_query->found_posts;
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">

            <!-- Page header -->
            <div class="mb-10">
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-4"><?php the_title(); ?></h1>
                <div class="w-24 h-2 bg-red-600 mb-4"></div>
                <p class="text-lg text-slate-600 font-bold max-w-3xl">Browse our pristine showroom models available for order or direct purchase.</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <!-- ── FILTER SIDEBAR ─────────────────────────── -->
                <aside class="w-full lg:w-72 xl:w-80 shrink-0">
                    <button type="button" id="vf-mobile-toggle"
                            class="lg:hidden w-full flex items-center justify-between bg-slate-900 text-white px-5 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] mb-4">
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                            Filter &amp; Search
                        </span>
                        <span id="vf-toggle-icon">▼</span>
                    </button>
                    <div id="vf-sidebar-wrap" class="hidden lg:block">
                        <?php include get_template_directory() . '/partials/filter-sidebar.php'; ?>
                    </div>
                </aside>

                <!-- ── RESULTS ────────────────────────────────── -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-sm font-black text-slate-500 uppercase tracking-widest">
                            <span class="text-slate-900"><?php echo $total; ?></span> Unit<?php echo $total !== 1 ? 's' : ''; ?> Found
                        </p>
                    </div>

                    <?php if ( $inventory_query->have_posts() ) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                            $post_id         = get_the_ID();
                            $year            = get_field( 'year',         $post_id );
                            $make            = get_field( 'make',         $post_id );
                            $model           = get_field( 'model',        $post_id );
                            $price           = get_field( 'price',        $post_id );
                            $call_for_price  = get_field( 'call_for_price', $post_id );
                            $category        = get_field( 'category',     $post_id );
                            $condition       = get_field( 'condition',    $post_id );
                            $stock_number    = get_field( 'stock_number', $post_id );
                            $length          = get_field( 'length',       $post_id );
                            $formatted_price = $call_for_price ? 'Call For Price' : (is_numeric( $price ) ? number_format( $price ) : (string) $price);
                            $images          = varner_get_card_images( $post_id );
                            include get_template_directory() . '/partials/equipment-card.php';
                        endwhile;
                        wp_reset_postdata(); ?>
                    </div>
                    <?php else : ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
                        <div class="text-slate-300 text-6xl mb-4">⊘</div>
                        <p class="text-slate-500 font-black uppercase tracking-widest text-sm">No units match your current filters.</p>
                        <a href="<?php echo esc_url( get_permalink() ); ?>" class="inline-block mt-6 bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-700 transition-colors">
                            Clear All Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

<script>
(function () {
    var btn  = document.getElementById('vf-mobile-toggle');
    var wrap = document.getElementById('vf-sidebar-wrap');
    var icon = document.getElementById('vf-toggle-icon');
    if (btn && wrap) {
        btn.addEventListener('click', function () {
            var open = !wrap.classList.contains('hidden');
            wrap.classList.toggle('hidden', open);
            if (icon) icon.textContent = open ? '▼' : '▲';
        });
    }
})();
</script>

<?php get_footer(); ?>
