<?php
/**
 * Template Name: Brands
 * Description: Shows all brands with a featured unit card per brand.
 */

get_header();

global $wpdb;

$brands = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT pm.meta_value AS make, COUNT(*) as qty
         FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s AND pm.meta_value != ''
           AND p.post_type = %s AND p.post_status = 'publish'
         GROUP BY pm.meta_value
         ORDER BY pm.meta_value ASC",
        'make',
        'equipment'
    )
);

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

function varner_brand_featured_unit( $brand_slug ) {
    $args = array(
        'post_type'      => 'equipment',
        'post_status'    => 'publish',
        'meta_key'       => 'make',
        'meta_value'     => $brand_slug,
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $q = new WP_Query( $args );
    if ( ! $q->have_posts() ) return null;
    $q->the_post();
    $post_id        = get_the_ID();
    $year           = get_field( 'year', $post_id );
    $make           = get_field( 'make', $post_id );
    $model          = get_field( 'model', $post_id );
    $category       = get_field( 'category', $post_id );
    $condition      = get_field( 'condition', $post_id );
    $stock_status   = get_field( 'stock_status', $post_id );
    $price          = get_field( 'price', $post_id );
    $call_for_price = get_field( 'call_for_price', $post_id );
    $formatted_price = $call_for_price ? 'Call for Price' : ( is_numeric( $price ) ? '$' . number_format( $price ) : ( $price ?: '—' ) );
    $images         = get_field( 'images', $post_id );
    $image_url      = ( is_array( $images ) && ! empty( $images ) ) ? $images[0] : get_template_directory_uri() . '/assets/VE_Tractor_Icon.png';

    $status_label = '';
    $status_color = '';
    if ( $stock_status ) {
        $lc = strtolower( trim( $stock_status ) );
        if ( $lc === 'sold' ) {
            $status_label = 'Sold';
            $status_color = 'bg-red-600';
        } elseif ( in_array( $lc, array( 'sale pending', 'pending sale', 'pending' ), true ) ) {
            $status_label = 'Sale Pending';
            $status_color = 'bg-amber-500';
        }
    }

    $data = array(
        'post_id'   => $post_id,
        'title'     => trim( "$year $make $model" ) ?: get_the_title(),
        'category'  => $category,
        'condition' => $condition,
        'price'     => $formatted_price,
        'image'     => $image_url,
        'permalink' => get_permalink( $post_id ),
        'status'    => $status_label,
        'status_color' => $status_color,
    );
    wp_reset_postdata();
    return $data;
}
?>

<section class="py-16 bg-slate-950 text-white">
    <div class="max-w-7xl mx-auto px-4 space-y-6">
        <div class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.3em]">Brands</div>
        <h1 class="text-4xl md:text-6xl font-black tracking-tighter">Brands We Carry</h1>
        <p class="text-slate-300 max-w-2xl font-bold">Explore inventory by manufacturer. Each card links to live units for that brand.</p>
    </div>
</section>

<section class="py-16 bg-slate-100">
    <div class="max-w-7xl mx-auto px-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php if ( $brands ) : foreach ( $brands as $brand ) :
            $brand_name = $brand->make;
            $brand_logo = isset( $brand_logos[ $brand_name ] ) ? $assets_base . $brand_logos[ $brand_name ] : '';
            $featured   = varner_brand_featured_unit( $brand_name );
            $filter_url = add_query_arg( 'make', rawurlencode( $brand_name ), home_url( '/inventory' ) );
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-all p-5 flex flex-col gap-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Brand</div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight leading-tight"><?php echo esc_html( $brand_name ); ?></div>
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-red-600 mt-1"><?php echo intval( $brand->qty ); ?> Units</div>
                </div>
                <?php if ( $brand_logo ) : ?>
                    <img src="<?php echo esc_url( $brand_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" class="h-12 w-auto object-contain drop-shadow" />
                <?php endif; ?>
            </div>

            <?php if ( $featured ) : ?>
            <a href="<?php echo esc_url( $featured['permalink'] ); ?>" class="block rounded-xl overflow-hidden border border-slate-100 hover:border-red-200 transition-all shadow-sm hover:shadow-md">
                <div class="aspect-[16/10] bg-slate-100 relative">
                    <img src="<?php echo esc_url( $featured['image'] ); ?>" alt="<?php echo esc_attr( $featured['title'] ); ?>" class="w-full h-full object-cover">
                    <?php if ( $featured['condition'] ) : ?>
                        <span class="absolute top-2 left-2 bg-slate-900/80 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md"><?php echo esc_html( $featured['condition'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( $featured['status'] ) : ?>
                        <span class="absolute top-2 right-2 <?php echo esc_attr( $featured['status_color'] ); ?> text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md shadow"><?php echo esc_html( $featured['status'] ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-3 space-y-1">
                    <div class="text-sm font-black text-slate-900 leading-tight line-clamp-2"><?php echo esc_html( $featured['title'] ); ?></div>
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500"><?php echo esc_html( $featured['category'] ?: 'Inventory' ); ?></div>
                    <div class="text-base font-black text-slate-800"><?php echo esc_html( $featured['price'] ); ?></div>
                </div>
            </a>
            <?php else : ?>
            <div class="p-4 rounded-xl border border-dashed border-slate-200 text-slate-500 text-sm font-bold">No units found for this brand yet.</div>
            <?php endif; ?>

            <div class="flex gap-2">
                <a href="<?php echo esc_url( $filter_url ); ?>" class="flex-1 text-center bg-red-600 text-white py-2.5 rounded-lg font-black uppercase tracking-widest text-[10px] hover:bg-red-700 transition-all">View Brand Inventory</a>
            </div>
        </div>
        <?php endforeach; else : ?>
            <p>No brands found.</p>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
