<?php
/**
 * Template Name: Brand Landing
 * Description: Shows inventory for a specific brand (matches page title to equipment make).
 */

get_header();

$brand_slug = get_query_var('brand_name');
if ( $brand_slug ) {
    $brand_name = str_replace('-', ' ', $brand_slug);
    $brand_name = ucwords($brand_name);
    // Special naming overrides
    $overrides = array(
        'big tex' => 'Big Tex',
        'tym'     => 'TYM',
        'zetor'   => 'Zetor',
        'krone'   => 'Krone',
        'macdon'  => 'MacDon',
        'mchale'  => 'McHale',
        'roxr'    => 'ROXR',
        'titan trailers' => 'Titan Trailers',
        'titan mfg' => 'Titan Trailers'
    );
    if ( isset( $overrides[ strtolower($brand_name) ] ) ) {
        $brand_name = $overrides[ strtolower($brand_name) ];
    }
} else {
    $brand_name = get_the_title();
}

$brand_norm   = sanitize_title( $brand_name );

// Inventory filter data
$selected_categories = array_map( 'sanitize_text_field', (array) ( $_GET['category'] ?? array() ) );
$filter_data = varner_get_filter_data( array(), $selected_categories );

// Attempt to locate a brand logo from the media library
if ( ! function_exists( 'varner_find_brand_logo_url' ) ) {
    function varner_find_brand_logo_url( $brand_slug, $brand_name ) {
        $candidates = array_filter( array( sanitize_title( $brand_name ), $brand_slug ) );
        foreach ( array_unique( $candidates ) as $candidate ) {
            $found = get_posts( array(
                'post_type'      => 'attachment',
                'name'           => $candidate,
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
            if ( $found ) {
                return wp_get_attachment_image_url( $found[0]->ID, 'large' );
            }
        }

        // Fallback: match by filename containing *_white.png
        $file_candidates = array();
        foreach ( $candidates as $c ) {
            $file_candidates[] = $c . '_white';
            $file_candidates[] = $c . '-white';
        }
        foreach ( array_unique( $file_candidates ) as $fc ) {
            $by_file = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_wp_attached_file',
                        'value'   => $fc,
                        'compare' => 'LIKE',
                    ),
                ),
            ) );
            if ( $by_file ) {
                return wp_get_attachment_image_url( $by_file[0]->ID, 'large' );
            }
        }

        // Fallback: look for bundled asset matching slug + _white.png
        $slug_key = sanitize_title( $brand_slug ?: $brand_name );
        $flat_key = preg_replace( '/[^a-z0-9]/i', '', $brand_name );
        $asset_candidates = array_unique( array_filter( array(
            $slug_key . '_white.png',
            $slug_key . '-white.png',
            str_replace( '-', '_', $slug_key ) . '_white.png',
            $flat_key ? strtolower( $flat_key ) . '_white.png' : '',
            $flat_key ? $flat_key . '_white.png' : '',
        ) ) );
        foreach ( $asset_candidates as $fname ) {
            foreach ( array( '/assets/', '/images/' ) as $subdir ) {
                $dir = get_template_directory() . $subdir . $fname;
                if ( file_exists( $dir ) ) {
                    return get_template_directory_uri() . $subdir . $fname;
                }
            }
        }

        // Fallback: bundled theme assets (_white.png)
        $theme_assets = array(
            'big-tex'              => 'BigTex_white.png',
            'cm-truck-beds'        => 'CMTruckbeds_white.png',
            'duetzfahr'            => 'DuetzFahr_white.png',
            'deutz-fahr'           => 'DuetzFahr_white.png',
            'krone'                => 'KRONE_white.png',
            'macdon'               => 'MacDon_white.png',
            'mahindra'             => 'Mahindra_white.png',
            'mchale'               => 'McHALE_white.png',
            'roxor'                => 'ROXR_white.png',
            'roxr'                 => 'ROXR_white.png',
            'titan-mfg'            => 'TitanTrailersMFG_white.png',
            'titan-trailers'       => 'TitanTrailersMFG_white.png',
            'titantrailersmfg'     => 'TitanTrailersMFG_white.png',
            'titon-mfg'            => 'TitanTrailersMFG_white.png',
            'triton'               => 'Triton_white.png',
            'tym'                  => 'TYM_white.png',
            'zetor'                => 'Zetor_white.png',
            'ford'                 => 'ford_white.png',
            'varnerequipment'      => 'VarnerEquipment_white.png',
            'bale-king'            => 'BaleKing_white.png',
            'baumalight'           => 'Baumalight_white.png',
            'beaver-valley'        => 'BeaverValley_white.png',
            'bison'                => 'Bison_white.png',
            'brush-chief'          => 'BrushChief_white.png',
            'danuser'              => 'Danuser_white.png',
            'degelman'             => 'Degelman_white.png',
            'enorossi'             => 'Enorossi_white.png',
            'mk-martin'            => 'MKMartin_white.png',
            'maschio'              => 'Maschio_white.png',
            'maxon'                => 'Maxon_white.png',
            'rc-trailers'          => 'RCTrailers_white.png',
            'speeco'               => 'Speeco_white.png',
            'tar-river'            => 'TarRiver_white.png',
            'tidenberg'            => 'Tidenberg_white.png',
            'worksaver'            => 'Worksaver_white.png',
        );

        $slug_key = sanitize_title( $brand_slug ?: $brand_name );
        if ( isset( $theme_assets[ $slug_key ] ) ) {
            $filename = $theme_assets[ $slug_key ];
            $paths = array(
                array( 'dir' => get_template_directory() . '/images/' . $filename, 'uri' => get_template_directory_uri() . '/images/' . $filename ),
                array( 'dir' => get_template_directory() . '/assets/' . $filename, 'uri' => get_template_directory_uri() . '/assets/' . $filename ),
            );
            foreach ( $paths as $p ) {
                if ( file_exists( $p['dir'] ) ) {
                    return $p['uri'];
                }
            }
        }
        return '';
    }
}

$brand_logo_url = varner_find_brand_logo_url( $brand_slug ?: $brand_norm, $brand_name );

$words        = array_filter( preg_split( '/\s+/', strtolower( $brand_name ) ) );
$meta_keys    = array( 'make', 'manufacturer', 'brand' );
$meta_clauses = array( 'relation' => 'OR' );

foreach ( $meta_keys as $mk ) {
    $and_clause = array( 'relation' => 'AND' );
    foreach ( $words as $w ) {
        $and_clause[] = array( 'key' => $mk, 'value' => $w, 'compare' => 'LIKE' );
    }
    $meta_clauses[] = $and_clause;
    $meta_clauses[] = array( 'key' => $mk, 'value' => $brand_name, 'compare' => 'LIKE' );
    $meta_clauses[] = array( 'key' => $mk, 'value' => $brand_norm, 'compare' => 'LIKE' );
}

$brand_meta = array(
    'relation' => 'OR',
    array( 'key' => 'make',          'value' => $brand_name, 'compare' => 'LIKE' ),
    array( 'key' => 'manufacturer',  'value' => $brand_name, 'compare' => 'LIKE' ),
    array( 'key' => 'brand',         'value' => $brand_name, 'compare' => 'LIKE' ),
);

$count_args = varner_build_inventory_query( array( $brand_meta ), -1 );
$count_args['posts_per_page'] = -1;
$count_args['fields'] = 'ids';
$total_units = count( get_posts( $count_args ) );

$query_args = varner_build_inventory_query( array( $brand_meta ), 12 );
$brand_query = new WP_Query( $query_args );
$current_page = max( 1, intval( get_query_var( 'paged' ) ?: ( get_query_var( 'page' ) ?: ( $_GET['paged'] ?? ( $_GET['page'] ?? 1 ) ) ) ) );
?>

<section class="py-16 bg-slate-950 text-white min-h-[400px] flex items-center relative overflow-hidden">
    <!-- Decorative background element -->
    <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/4 w-96 h-96 bg-red-600/10 rounded-full blur-[100px]"></div>
    <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/4 w-96 h-96 bg-slate-500/10 rounded-full blur-[100px]"></div>

    <div class="max-w-7xl mx-auto px-4 w-full relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-12">
            <div class="shrink-0">
                <?php if ( $brand_logo_url ) : ?>
                    <img src="<?php echo esc_url( $brand_logo_url ); ?>" 
                         alt="<?php echo esc_attr( $brand_name ); ?> logo" 
                         class="w-[280px] sm:w-[320px] h-auto max-h-[320px] object-contain drop-shadow-[0_20px_50px_rgba(0,0,0,0.3)]" 
                         loading="lazy" 
                         decoding="async" />
                <?php else : ?>
                    <h1 class="text-[46px] font-black tracking-tighter text-white m-0">
                        <?php echo esc_html( $brand_name ); ?>
                    </h1>
                <?php endif; ?>
            </div>
            
            <div class="flex-1 text-center md:text-left space-y-6">
                <div>
                    <h2 class="text-red-500 font-black uppercase tracking-[0.3em] text-sm mb-2">Authorized Dealer</h2>
                </div>
                
                <p class="text-slate-300 text-xl font-medium max-w-2xl leading-relaxed">
                    Explore our current selection of <span class="text-white font-bold"><?php echo esc_html( $brand_name ); ?></span> equipment. 
                    From new arrivals to certified pre-owned units, find the perfect machine for your operation.
                </p>

                <div class="flex flex-wrap items-center justify-center md:justify-start gap-6 pt-4">
                    <div class="flex items-center gap-3 bg-white/5 px-6 py-3 rounded-2xl border border-white/10">
                        <span class="text-3xl font-black text-white"><?php echo number_format_i18n( $total_units ); ?></span>
                        <span class="text-slate-400 font-black uppercase tracking-widest text-xs leading-tight">Units<br/>Available</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 font-black uppercase tracking-[0.2em] text-xs">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Live Inventory Tracking
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-slate-100">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-10">
            <div class="w-full lg:w-72 shrink-0">
                <?php 
                    $facet_search_label = $brand_name . ' Inventory';
                    $selected_makes_override = array();
                    $brand_count = 0;
                    if ( isset( $filter_data['makes'] ) ) {
                        foreach ( $filter_data['makes'] as $mk => $obj ) {
                            if ( strcasecmp( $mk, $brand_name ) === 0 ) { $brand_count = intval( $obj->cnt ?? 0 ); break; }
                        }
                    }
                    if ( $brand_count > 0 ) {
                        $selected_makes_override = array( $brand_name );
                    }
                    include get_template_directory() . '/partials/inventory-sidebar.php'; 
                ?>
            </div>

            <div class="flex-1">
                <div class="flex items-center justify-between mb-8 gap-4">
                    <p class="text-xs font-black text-slate-500 uppercase tracking-[0.2em]">
                        Showing <?php echo number_format_i18n( $brand_query->post_count ); ?> of <?php echo number_format_i18n( $total_units ); ?> units
                    </p>
                </div>

                <?php if ( $brand_query->have_posts() ) : ?>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php while ( $brand_query->have_posts() ) : $brand_query->the_post();
                            $post_id        = get_the_ID();
                            $year           = get_field( 'year',         $post_id );
                            $make           = get_field( 'make',         $post_id );
                            $model          = get_field( 'model',        $post_id );
                            $category       = get_field( 'category',     $post_id );
                            $condition      = get_field( 'condition',    $post_id );
                            $stock_status   = get_field( 'stock_status', $post_id );
                            $stock_number   = get_field( 'stock_number', $post_id );
                            $length         = get_field( 'length',       $post_id );
                            $price          = get_field( 'price',        $post_id );
                            $call_for_price = get_field( 'call_for_price', $post_id );
                            $formatted_price = $call_for_price ? 'Call For Price' : ( is_numeric( $price ) ? number_format( $price ) : (string) $price );
                            $images   = function_exists( 'varner_get_card_images' ) ? varner_get_card_images( $post_id ) : array();
                            include locate_template( 'partials/equipment-card.php', false, false );
                        endwhile; wp_reset_postdata(); ?>
                    </div>
                    <?php 
                        $pagination = paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'total'     => max( 1, $brand_query->max_num_pages ),
                            'current'   => $current_page,
                            'type'      => 'list',
                            'prev_text' => '&lt; Previous',
                            'next_text' => 'Next &gt;',
                        ) );
                    ?>
                    <?php if ( $pagination ) : ?>
                    <div class="mt-12 flex justify-center">
                        <div class="varner-pagination">
                            <?php echo $pagination; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 font-bold text-slate-600">No units found for this brand yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
