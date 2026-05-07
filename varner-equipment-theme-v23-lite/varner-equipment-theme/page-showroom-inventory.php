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

// Active filter count for badge
$active_filter_count =
    count( array_filter( (array) ( $_GET['category']     ?? [] ) ) ) +
    count( array_filter( (array) ( $_GET['make']         ?? [] ) ) ) +
    count( array_filter( (array) ( $_GET['condition']    ?? [] ) ) ) +
    ( !empty( $_GET['year_min']     ) || !empty( $_GET['year_max']    ) ? 1 : 0 ) +
    ( !empty( $_GET['price_min']    ) || !empty( $_GET['price_max']   ) ? 1 : 0 ) +
    ( !empty( $_GET['s']            ) ? 1 : 0 ) +
    ( !empty( $_GET['stock_number'] ) ? 1 : 0 ) +
    ( !empty( $_GET['vin']          ) ? 1 : 0 );
?>

<!-- FILTER DRAWER BACKDROP -->
<div id="vf-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden opacity-0 transition-opacity duration-300"></div>

<!-- FILTER DRAWER -->
<div id="vf-drawer" class="fixed top-0 left-0 h-full w-80 max-w-[90vw] bg-white z-[201] shadow-2xl -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto flex flex-col">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 sticky top-0 bg-white z-10 shrink-0">
        <span class="font-black text-sm uppercase tracking-widest text-slate-900">Filter &amp; Search</span>
        <button id="vf-drawer-close" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-all text-xl leading-none">×</button>
    </div>
    <div class="flex-1 overflow-y-auto">
        <?php include get_template_directory() . '/partials/filter-sidebar.php'; ?>
    </div>
</div>

<section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Page header -->
        <div class="mb-10">
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-4"><?php the_title(); ?></h1>
            <div class="w-24 h-2 bg-red-600 mb-4"></div>
            <p class="text-lg text-slate-600 font-bold max-w-3xl">Browse our pristine showroom models available for order or direct purchase.</p>
        </div>

        <!-- Toolbar: filter button + result count -->
        <div class="flex items-center justify-between mb-8 gap-4">
            <button id="vf-drawer-open" class="flex items-center gap-2.5 bg-slate-900 text-white px-5 py-3 rounded-xl font-black uppercase tracking-widest text-xs hover:bg-red-600 transition-all shadow-sm active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                Filters
                <?php if ( $active_filter_count > 0 ) : ?>
                <span class="bg-red-600 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center leading-none"><?php echo $active_filter_count; ?></span>
                <?php endif; ?>
            </button>
            <p class="text-sm font-black text-slate-500 uppercase tracking-widest">
                <span class="text-slate-900"><?php echo $total; ?></span> Unit<?php echo $total !== 1 ? 's' : ''; ?> Found
            </p>
        </div>

        <!-- Inventory grid -->
        <?php if ( $inventory_query->have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                $post_id         = get_the_ID();
                $year            = get_field( 'year',           $post_id );
                $make            = get_field( 'make',           $post_id );
                $model           = get_field( 'model',          $post_id );
                $price           = get_field( 'price',          $post_id );
                $call_for_price  = get_field( 'call_for_price', $post_id );
                $category        = get_field( 'category',       $post_id );
                $condition       = get_field( 'condition',      $post_id );
                $stock_number    = get_field( 'stock_number',   $post_id );
                $length          = get_field( 'length',         $post_id );
                $formatted_price = $call_for_price ? 'Call For Price' : ( is_numeric( $price ) ? number_format( $price ) : (string) $price );
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

    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);
})();
</script>

<?php get_footer(); ?>
