<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="speculationrules">
    {
      "prerender": [
        {
          "source": "document",
          "where": {
            "href_matches": "/equipment/*"
          },
          "eagerness": "moderate"
        }
      ]
    }
    </script>

    <?php
    // Dynamic SEO Logic
    $seo_description = "Varner Equipment Delta CO — Premier source for utility trailers for sale western colorado, Big Tex trailers, Mahindra & TYM tractors across Western Colorado.";
    $seo_keywords = "utility trailers for sale western colorado, Mahindra tractors Delta CO, Mahindra dealer Colorado, Big Tex trailers Delta Colorado, Big Tex dealer western Colorado, Deutz-Fahr tractors Colorado, Krone equipment dealer Colorado, Western trailers Delta CO, Varner Equipment Delta CO, TYM tractors Colorado, tractor dealer Delta CO, tractor dealer western Colorado, used tractors Delta Colorado, trailer dealer Montrose CO, farm equipment dealer Delta County, agricultural equipment Delta Colorado, hay equipment dealer Colorado, utility trailers for sale Delta CO, dump trailers Montrose CO, equipment financing Delta CO, tractor parts near Delta CO, tractor service Delta Colorado, Varner Equipment inventory, Varner Equipment tractors for sale, Varner Equipment trailers for sale, Varner Equipment Delta Colorado reviews";
    $og_title = get_bloginfo('name');
    $og_url = home_url(add_query_arg(null, null));
    $og_image = get_template_directory_uri() . '/assets/VarnerEquipment_red.png';
    $og_type = 'website';

    if (is_singular('equipment') && function_exists('get_field')) {
        $post_id = get_the_ID();
        $year = get_field('year', $post_id);
        $make = get_field('make', $post_id);
        $model = get_field('model', $post_id);
        $cat = get_field('category', $post_id);
        $seo_description = "View pricing & specs for this $year $make $model $cat at Varner Equipment Delta CO. Your trusted $make dealer Colorado for heavy equipment in Delta, CO.";
        $seo_keywords = "$make $model for sale Colorado, $make $model price Delta CO, $make dealer Colorado, Varner Equipment inventory, $make $cat for sale Delta CO";
        $og_title = "$year $make $model | Varner Equipment Delta CO";
        $og_type = 'product';
        $images = varner_get_card_images($post_id);
        if (!empty($images)) $og_image = $images[0];
    } elseif ( get_query_var('inventory_segment') && get_query_var('brand_name') ) {
        $seg = get_query_var('inventory_segment');
        $b_slug = sanitize_title(get_query_var('brand_name'));
        $b_obj = function_exists('varner_get_brand') ? varner_get_brand($b_slug) : null;
        $b_name = $b_obj ? $b_obj['name'] : ucfirst($b_slug);
        $s_seo = function_exists('varner_get_segment_seo') ? varner_get_segment_seo($seg) : null;
        $s_name = $s_seo ? $s_seo['h1'] : ucfirst($seg);
        $og_title = "$b_name $s_name for Sale | Varner Equipment Delta CO";
        $seo_description = "Shop in-stock $b_name $s_name at Varner Equipment in Delta, CO. Serving Western Colorado with high-quality tractors, trailers, and implements.";
        $canonical_url = home_url( "/inventory/$seg/$b_slug/" );
    } elseif ( get_query_var('inventory_segment') || is_page_template('page-equipment-listing.php') ) {
        $slug = get_query_var('inventory_segment') ?: sanitize_title(get_the_title());
        $seo = function_exists('varner_get_segment_seo') ? varner_get_segment_seo($slug) : null;
        if ($seo) {
            $seo_description = $seo['blurb'] ?: ($seo['sub'] . " Browse live inventory at Varner Equipment Delta CO.");
            $seo_keywords = $seo['keywords'] ?? $seo_keywords;
            $og_title = $seo['title'] ?? ($seo['h1'] . " | Varner Equipment Delta CO");
        }
    } elseif ( get_query_var( 'brands_hub' ) ) {
        $og_title        = "Shop by Brand | Tractors, Trailers & Hay Equipment | Varner Equipment";
        $seo_description = "Explore every brand Varner Equipment carries in Delta, CO - Mahindra, TYM, and Deutz Fahr tractors, Big Tex, CM and Triton trailers, plus Krone and Macdon hay tools.";
        $canonical_url   = home_url( '/brands/' );
    } elseif ( get_query_var( 'brand_name' ) && function_exists( 'varner_get_brand' ) && ( $b = varner_get_brand( sanitize_title( get_query_var( 'brand_name' ) ) ) ) ) {
        $og_title        = $b['name'] . " " . $b['category'] . " | Varner Equipment Delta CO";
        $seo_description = $b['tagline'] . " Shop " . $b['name'] . " at Varner Equipment in Delta, CO, serving Western Colorado.";
        $seo_keywords    = $b['keywords'];
        $canonical_url   = home_url( '/brands/' . sanitize_title( get_query_var( 'brand_name' ) ) . '/' );
        $og_image        = ( ! empty( $b['logo'] ) && file_exists( get_template_directory() . '/assets/brands/' . $b['logo'] ) )
            ? get_template_directory_uri() . '/assets/brands/' . $b['logo']
            : $og_image;
    } elseif ( is_page( array( 'parts-request', 'online-parts-store' ) ) || is_page_template('page-parts-request.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'parts-request' ) !== false ) {
        $seo_description = "Request parts for your tractor, trailer, or equipment from Varner Equipment in Delta, CO. Extensive parts inventory and fast turnaround.";
        $seo_keywords = "tractor parts near Delta CO, Mahindra parts Colorado, Big Tex parts, farm equipment parts Delta County, TYM tractor parts, Varner Equipment parts";
        $og_title = "Parts Request | Tractor & Trailer Parts | Delta, CO";
    } elseif ( is_page( array( 'service-request', 'services' ) ) || is_page_template('page-service-request.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'service-request' ) !== false ) {
        $seo_description = "Request service for your tractor, trailer, or equipment at Varner Equipment in Delta, CO. Expert technicians for maintenance and repairs.";
        $seo_keywords = "tractor service Delta Colorado, equipment repair Delta County, Mahindra tractor service, tractor mechanic near me, farm machinery repair Delta CO";
        $og_title = "Service Request | Equipment Repair & Maintenance | Delta CO";
    } elseif ( is_page( array( 'finance', 'financing' ) ) || is_page_template('page-finance.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'finance' ) !== false ) {
        $seo_description = "Apply for tractor and trailer financing through Wells Fargo, Sheffield, DLL, and AgDirect, or estimate monthly payments with our calculator. Delta, CO.";
        $seo_keywords = "equipment financing Delta CO, tractor financing Colorado, trailer financing Montrose, AgDirect, Sheffield Finance, Wells Fargo, low rate tractor loans";
        $og_title = "Financing & Payment Calculator | Varner Equipment, Delta CO";
    } elseif ( is_page( array( 'about-us', 'about', 'dealer-info' ) ) || is_page_template('page-about-us.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'about' ) !== false ) {
        $seo_description = "Varner Equipment is a family-run dealership in Delta, CO offering Mahindra, TYM, Big Tex, and Krone equipment, plus expert parts and service.";
        $seo_keywords = "Varner Equipment Delta Colorado reviews, Varner Equipment inventory, Varner Equipment tractors for sale, Varner Equipment trailers for sale, farm equipment dealer Delta County, tractor dealer near me";
        $og_title = "About Us | Family-Owned Equipment Dealer in Delta, CO";
    } elseif ( is_page( array( 'videos', 'product-videos' ) ) || is_page_template('page-videos.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'videos' ) !== false ) {
        $seo_description = "Watch tractor and trailer demos, how-to guides, and equipment walkarounds from Varner Equipment in Delta, CO. Subscribe on our YouTube channel.";
        $og_title = "Product & How-To Videos | Varner Equipment, Delta CO";
    } elseif ( is_page( array( 'employment', 'careers' ) ) || is_page_template('page-employment.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'employment' ) !== false ) {
        $seo_description = "Join the team at Varner Equipment, a family-owned equipment dealership in Delta, CO. View current job openings and apply today.";
        $og_title = "Careers & Employment | Varner Equipment, Delta CO";
    } elseif ( is_page( 'contact' ) || is_page_template('page-contact.php') || strpos( $_SERVER['REQUEST_URI'] ?? '', 'contact' ) !== false ) {
        $seo_description = "Contact Varner Equipment at 1375 US-50, Delta, CO. Call 970-874-0612 for sales, parts, service, and financing on farm and ag equipment.";
        $og_title = "Contact Varner Equipment | Delta, CO | 970-874-0612";
    } elseif ( is_front_page() || is_home() || ( $_SERVER['REQUEST_URI'] ?? '' ) === '/' ) {
        $seo_description = "Family-owned farm and agricultural equipment dealer in Delta, CO. Shop tractors, trailers, and hay equipment across Colorado's Western Slope.";
        $og_title = "Varner Equipment | Western Colorado's Top Equipment Dealer";
    }

    // Win 4: Detect active search/filter parameters to add noindex and override canonical URL
    $has_active_filters = !empty(array_filter(array(
        $_GET['category'] ?? null,
        $_GET['make'] ?? null,
        $_GET['condition'] ?? null,
        $_GET['year_min'] ?? null,
        $_GET['year_max'] ?? null,
        $_GET['price_min'] ?? null,
        $_GET['price_max'] ?? null,
        $_GET['s'] ?? null,
        $_GET['stock_number'] ?? null,
        $_GET['vin'] ?? null
    )));

    $canonical_url = home_url( add_query_arg( null, null ) );
    if ( is_singular() ) {
        $canonical_url = get_permalink();
    } elseif ( is_page_template('page-equipment-listing.php') ) {
        $slug = get_query_var('inventory_segment');
        if ( $slug ) {
            $canonical_url = home_url( '/inventory/' . $slug );
        } else {
            $canonical_url = get_permalink();
        }
    } elseif ( get_query_var('brand_name') ) {
        $canonical_url = home_url( '/brands/' . get_query_var('brand_name') );
    } else {
        $canonical_url = strtok( home_url( add_query_arg( null, null ) ), '?' );
    }

    $og_url = $canonical_url;

    // Visible <title>: reuse the per-page og:title; upgrade only the generic homepage default
    $seo_title = ( $og_title === get_bloginfo('name') )
        ? "Varner Equipment | Western Colorado's Top Equipment Dealer"
        : $og_title;
    ?>

    <title><?php echo esc_html( $seo_title ); ?></title>
    <meta name="description" content="<?php echo esc_attr($seo_description); ?>">
    <meta name="keywords" content="<?php echo esc_attr($seo_keywords); ?>">
    <?php if ( $has_active_filters ) : ?>
        <meta name="robots" content="noindex, follow">
    <?php endif; ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo esc_attr($og_type); ?>">
    <meta property="og:url" content="<?php echo esc_url($og_url); ?>">
    <meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($seo_description); ?>">
    <meta property="og:image" content="<?php echo esc_url($og_image); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo esc_url($og_url); ?>">
    <meta property="twitter:title" content="<?php echo esc_attr($og_title); ?>">
    <meta property="twitter:description" content="<?php echo esc_attr($seo_description); ?>">
    <meta property="twitter:image" content="<?php echo esc_url($og_image); ?>">

    <link rel="canonical" href="<?php echo esc_url($canonical_url); ?>">

    <!-- Preload critical fonts to avoid Cumulative Layout Shift (CLS) -->
    <link rel="preload" href="<?php echo esc_url( plugins_url( 'varner-os-plugin-v23/assets/fonts/inter/Inter.woff2' ) ); ?>" as="font" type="font/woff2" crossorigin>

    <!-- Resource hints: DNS prefetch to avoid idle TLS connection warnings while speeding up DNS -->
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://i.ytimg.com">

    <!-- LocalBusiness JSON-LD -->
    <?php
    $ld_business = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'LocalBusiness',
        '@id'        => home_url('/#business'),
        'name'       => 'Varner Equipment',
        'url'        => home_url('/'),
        'telephone'  => '+1-970-874-0612',
        'image'      => get_template_directory_uri() . '/assets/VarnerEquipment_red.png',
        'priceRange' => '$$',
        'address'    => array(
            '@type'           => 'PostalAddress',
            'streetAddress'   => '1375 US-50',
            'addressLocality' => 'Delta',
            'addressRegion'   => 'CO',
            'postalCode'      => '81416',
            'addressCountry'  => 'US',
        ),
        'geo' => array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => 38.7652,
            'longitude' => -108.1061,
        ),
        'openingHoursSpecification' => array(
            array('@type'=>'OpeningHoursSpecification','dayOfWeek'=>array('Monday','Tuesday','Wednesday','Thursday','Friday'),'opens'=>'08:00','closes'=>'17:00'),
            array('@type'=>'OpeningHoursSpecification','dayOfWeek'=>'Saturday','opens'=>'09:00','closes'=>'12:00'),
        ),
        'sameAs' => array(
            'https://www.facebook.com/varnerequipment',
            'https://www.youtube.com/@VarnerEquipment',
        ),
        'areaServed' => array('Delta','Montrose','Grand Junction','Olathe','Cedaredge','Hotchkiss','Paonia','Western Colorado'),
    );
    ?>
    <script type="application/ld+json"><?php echo wp_json_encode( $ld_business, JSON_UNESCAPED_SLASHES ); ?></script>

    <!-- Product JSON-LD (Single Equipment) -->
    <?php if ( is_singular('equipment') && function_exists('get_field') ) :
        $post_id     = get_the_ID();
        $price       = get_field('price', $post_id);
        $condition   = (string) get_field('condition', $post_id);
        $stock       = get_field('stock_number', $post_id);
        $hours       = get_field('hours', $post_id);
        $hp          = get_field('horsepower', $post_id);
        $vin         = get_field('vin', $post_id);
        $has_price   = is_numeric($price) && (float) $price > 0 && ! get_field('call_for_price', $post_id);
        
        $ld_product  = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => trim( "$year $make $model" ),
            'sku'         => $stock ?: (string) get_the_ID(),
            'mpn'         => $stock ?: (string) get_the_ID(),
            'image'       => $og_image,
            'description' => $seo_description,
            'brand'       => array( '@type' => 'Brand', 'name' => $make ?: 'Varner Equipment' ),
            'offers'      => array(
                '@type'         => 'Offer',
                'url'           => get_permalink(),
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/InStock',
                'itemCondition' => ( stripos( $condition, 'used' ) !== false )
                    ? 'https://schema.org/UsedCondition'
                    : 'https://schema.org/NewCondition',
                'seller'        => array( '@type' => 'LocalBusiness', 'name' => 'Varner Equipment' ),
            ),
        );
        if ( $has_price ) {
            $ld_product['offers']['price'] = (string) $price;
        }

        $add_props = array();
        if ( ! empty( $hours ) ) $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'Hours', 'value' => (string) $hours );
        if ( ! empty( $hp ) )    $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'Horsepower', 'value' => (string) $hp );
        if ( ! empty( $vin ) )   $add_props[] = array( '@type' => 'PropertyValue', 'name' => 'VIN', 'value' => (string) $vin );
        if ( ! empty( $add_props ) ) $ld_product['additionalProperty'] = $add_props;

        echo '<script type="application/ld+json">' . wp_json_encode( $ld_product, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    endif; ?>

    <!-- ItemList JSON-LD (Category / Inventory Listing Pages) -->
    <?php if ( get_query_var('inventory_segment') || is_page_template('page-equipment-listing.php') ) :
        $list_query = new WP_Query( array(
            'post_type'      => array( 'equipment', 'varner_equipment' ),
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'fields'         => 'ids',
        ) );
        if ( $list_query->have_posts() ) :
            $items = array();
            $pos = 1;
            foreach ( $list_query->posts as $item_id ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'url'      => get_permalink( $item_id ),
                    'name'     => get_the_title( $item_id ),
                );
            }
            $ld_itemlist = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                'numberOfItems'   => count( $items ),
                'itemListElement' => $items,
            );
            echo '<script type="application/ld+json">' . wp_json_encode( $ld_itemlist, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        endif;
    endif; ?>

    <!-- BreadcrumbList JSON-LD -->
    <?php if ( ! is_front_page() && ! is_home() ) :
        $breadcrumbs = array(
            array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/') ),
        );
        $pos = 2;
        if ( is_singular('equipment') ) {
            $breadcrumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => 'Inventory', 'item' => home_url('/inventory/all-units/') );
            $breadcrumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title(), 'item' => get_permalink() );
        } elseif ( get_query_var('inventory_segment') ) {
            $breadcrumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => 'Inventory', 'item' => home_url('/inventory/all-units/') );
            $breadcrumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => ucfirst( str_replace( '-', ' ', get_query_var('inventory_segment') ) ), 'item' => $canonical_url );
        } else {
            $breadcrumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title(), 'item' => $canonical_url );
        }
        $ld_breadcrumbs = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs,
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $ld_breadcrumbs, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    endif; ?>

    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 selection:bg-red-100 selection:text-red-600'); ?>>
<?php wp_body_open(); ?>

    <!-- ADA: Skip Navigation Link -->
    <a href="#main-content" class="skip-to-content">Skip to Main Content</a>

    <div class="sticky top-0 z-[100] w-full flex flex-col shadow-xl">
        <?php 
            $ann_1 = 'Mon-Fri: ' . varner_get_theme_setting( 'hours_mon_fri', '8am - 5pm' );
            $ann_2 = 'Sat: ' . varner_get_theme_setting( 'hours_sat', '9am - Noon' );
            $ann_3 = 'Sun: ' . varner_get_theme_setting( 'hours_sun', 'Closed' );
            $phone = varner_get_theme_setting( 'contact_phone', '(970) 874-0612' );
            $phone_tel = varner_get_theme_setting( 'contact_phone_raw', '9708740612' );
            $addr_1 = varner_get_theme_setting( 'contact_address_line1', '1375 US-50' );
            $addr_2 = varner_get_theme_setting( 'contact_address_line2', 'Delta, CO 81416' );
        ?>
        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-slate-950 text-white py-2 px-4 border-b border-white/10 relative z-20">
            <div class="max-w-7xl mx-auto flex justify-between items-center text-xs font-black uppercase tracking-[0.2em]">
                <div class="flex gap-4 sm:gap-6 items-center flex-wrap">
                    <span><?php echo esc_html($ann_1); ?></span>
                    <span class="hidden sm:inline text-slate-500">|</span>
                    <span class="hidden sm:inline"><?php echo esc_html($ann_2); ?></span>
                    <span class="hidden sm:inline text-slate-500">|</span>
                    <span class="hidden sm:inline"><?php echo esc_html($ann_3); ?></span>
                </div>
                <div class="flex gap-4">
                    <span class="text-red-500">●</span> 
                    <?php 
                        // Get total published inventory count dynamically
                        $count_posts = wp_count_posts('equipment');
                        $published_posts = $count_posts->publish ?? 0;
                        echo $published_posts . ' Units Available';
                    ?>
                </div>
            </div>
        </div>

        <!-- HEADER -->
        <header class="bg-white flex flex-col w-full relative z-10">
            <!-- LOGO & CTA ROW -->
            <div class="max-w-7xl mx-auto px-4 py-4 md:py-5 flex flex-col lg:flex lg:flex-row lg:justify-between items-center gap-4 w-full relative">
                
                <!-- MOBILE ROW 1: LOGO & HAMBURGER -->
                <div class="flex items-center justify-between w-full lg:w-auto">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center justify-start transform hover:rotate-1 transition-transform shrink-0">
                        <?php 
                        $brand_logo_url = function_exists('varner_get_brand_logo_url') ? varner_get_brand_logo_url('red') : '';
                        ?>
                        <img src="<?php echo esc_url($brand_logo_url); ?>" alt="Varner Equipment" class="h-16 md:h-20 w-auto object-contain" width="200" height="80">
                    </a>

                    <!-- MOBILE MENU TOGGLE -->
                    <button id="mobile-menu-toggle" class="lg:hidden p-3 bg-slate-100 text-slate-900 rounded-2xl hover:bg-red-600 hover:text-white transition-all shadow-sm shrink-0" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
                        <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- CENTERED ADDRESS -->
                <div class="flex justify-center text-center w-full lg:w-auto">
                    <a href="<?php echo esc_url( varner_get_theme_setting( 'contact_map_link', 'https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9' ) ); ?>" target="_blank" rel="noopener" class="group flex items-center justify-center gap-1.5 hover:scale-105 transition-transform pointer-events-auto" aria-label="Get directions to Varner Equipment">
                        <svg class="w-6 h-6 lg:w-8 lg:h-8 text-red-600 group-hover:text-slate-900 transition-colors shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <div class="flex flex-col text-center">
                            <span class="font-black text-slate-900 uppercase tracking-tighter text-lg lg:text-xl xl:text-2xl leading-none whitespace-nowrap"><?php echo esc_html($addr_1); ?></span>
                            <span class="font-black text-red-600 uppercase tracking-[0.1em] text-[8px] lg:text-[9px] xl:text-xs group-hover:text-slate-900 transition-colors mt-1"><?php echo esc_html($addr_2); ?></span>
                        </div>
                    </a>
                </div>

                <!-- PHONE NUMBER -->
                <div class="flex items-center justify-center lg:justify-end gap-2 md:gap-4 relative z-10 w-full lg:w-auto">
                    <a href="tel:<?php echo esc_attr($phone_tel); ?>" class="bg-red-600 text-white px-6 md:px-8 py-3 md:py-4 rounded-2xl font-black flex items-center justify-center gap-2 shadow-lg hover:bg-red-700 transition-all active:scale-95 text-base md:text-xl w-full lg:w-auto">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span><?php echo esc_html($phone); ?></span>
                    </a>
                </div>
            </div>

            <!-- NAVIGATION ROW (Desktop) -->
            <div class="hidden lg:block bg-slate-50 border-t border-t-slate-200 border-b-4 border-b-red-600 w-full">
                <div class="max-w-7xl mx-auto px-4">
                    <nav class="flex items-center justify-center gap-3 xl:gap-8 py-4 flex-wrap relative" aria-label="Primary Navigation">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors">Home</a>
                        
                        <!-- INVENTORY DROPDOWN -->
                        <div class="group relative" data-dropdown>
                            <button type="button" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default bg-transparent border-0 p-0" aria-expanded="false" aria-haspopup="true">
                                Inventory
                                <svg class="w-3 h-3 text-slate-500 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible focus-within:opacity-100 focus-within:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 group-focus-within:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">All Inventory</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/new' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">New</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/used' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Used</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/tractors' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Tractors</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/trailers' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Trailers</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/attachments' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Attachments</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/hay-equipment' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Hay Equipment</a>
                            </div>
                        </div>

                        <?php
                        // Brand counts (cached) + helper for link output
                        $brand_counts = get_transient( 'varner_brand_counts' );
                        if ( $brand_counts === false ) {
                            global $wpdb;
                            $hidden_sql = '';
                            if ( function_exists( 'varner_get_hidden_post_ids' ) ) {
                                $hidden_ids = varner_get_hidden_post_ids();
                                if ( ! empty( $hidden_ids ) ) {
                                    $hidden_sql = 'AND p.ID NOT IN (' . implode( ',', array_map( 'intval', $hidden_ids ) ) . ')';
                                }
                            }
                            $rows = $wpdb->get_results(
                                "SELECT LOWER(pm.meta_value) AS make, COUNT(*) AS cnt
                                 FROM {$wpdb->postmeta} pm
                                 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                                 WHERE pm.meta_key = 'make' AND pm.meta_value != ''
                                   AND p.post_type = 'equipment' AND p.post_status = 'publish'
                                   $hidden_sql
                                 GROUP BY LOWER(pm.meta_value)"
                            );
                            $brand_counts = array();
                            foreach ( $rows as $row ) {
                                $normalized_key = preg_replace( '/[^a-z0-9]/', '', $row->make );
                                $brand_counts[ $normalized_key ] = (int) $row->cnt;
                            }
                            set_transient( 'varner_brand_counts', $brand_counts, HOUR_IN_SECONDS );
                        }

                        if ( ! function_exists( 'varner_brand_link_nav' ) ) {
                            function varner_brand_link_nav( $brand, $brand_counts, $external_url = '' ) {
                                $normalized_lookup = preg_replace( '/[^a-z0-9]/', '', strtolower( $brand ) );
                                $count  = $brand_counts[ $normalized_lookup ] ?? 0;
                                $slug   = sanitize_title( $brand );
                                $href   = $external_url ?: home_url( '/brands/' . $slug );
                                $target = $external_url ? ' target="_blank" rel="noopener"' : '';
                                $dim    = $count === 0 ? ' opacity-40' : '';
                                $badge  = $count > 0
                                    ? '<span class="ml-auto shrink-0 bg-green-100 text-green-700 text-[8px] font-black px-1.5 py-0.5 rounded-full leading-none">' . $count . '</span>'
                                    : '';
                                echo '<a href="' . esc_url( $href ) . '"' . $target . ' class="flex items-center gap-2 py-2 text-xs font-black uppercase tracking-widest text-slate-600 hover:text-red-600 hover:pl-2 transition-all border-b border-slate-50 last:border-0' . $dim . '">' . esc_html( $brand ) . $badge . '</a>';
                            }
                        }
                        $all_brands = get_option( 'varner_brands' );
                        if ( ! is_array( $all_brands ) || empty( $all_brands ) ) {
                            $all_brands = function_exists( 'varner_default_brands' )
                                ? varner_default_brands()
                                : array( 'Big Tex', 'Mahindra', 'CM Truck Beds', 'TYM', 'Other' );
                        }
                        ?>

                        
                        <div class="group relative" data-dropdown>
                            <button type="button" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default bg-transparent border-0 p-0" aria-expanded="false" aria-haspopup="true">Financing</button>
                            <div class="absolute left-0 top-full mt-2 w-64 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible focus-within:opacity-100 focus-within:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 group-focus-within:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">FINANCIAL APPLICATIONS</a>
                                <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">FINANCIAL CALCULATOR</a>
                            </div>
                        </div>

                        <div class="group relative" data-dropdown>
                            <button type="button" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default bg-transparent border-0 p-0" aria-expanded="false" aria-haspopup="true">Services</button>
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible focus-within:opacity-100 focus-within:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 group-focus-within:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Service Request</a>
                                <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Parts Request</a>
                            </div>
                        </div>

                        <a href="<?php echo esc_url( varner_get_theme_setting( 'support_hub_parts_link', 'https://www.allpartsstore.com/index.htm?customernumber=CO0612' ) ); ?>" target="_blank" rel="noopener" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1">Online Parts Store</a>
                        <a href="<?php echo esc_url( home_url( '/videos' ) ); ?>" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors">Product Videos</a>
                        <a href="https://www.auctiontime.com/listings/upcoming-auctions/varner-equipment?EventCategoryID=7&amp;AccountCRMID=16566180" target="_blank" rel="noopener" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors">Online Auctions</a>
                        
                        <div class="group relative" data-dropdown>
                            <button type="button" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default bg-transparent border-0 p-0" aria-expanded="false" aria-haspopup="true">Dealer Info</button>
                            <div class="absolute left-0 top-full mt-2 w-48 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible focus-within:opacity-100 focus-within:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 group-focus-within:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">About Us</a>
                                <a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Employment</a>
                            </div>
                        </div>

                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="font-black uppercase text-xs xl:text-xs tracking-wider xl:tracking-widest text-slate-700 hover:text-red-600 transition-colors">Contact</a>
                    </nav>
                </div>
            </div>

            <!-- MOBILE MENU (Slide Down) -->
            <div id="mobile-menu" class="hidden lg:hidden bg-slate-900 text-white w-full border-t border-white/10 max-h-[80vh] overflow-y-auto">
                <nav class="flex flex-col py-6" aria-label="Mobile Navigation">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Home</a>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion" aria-expanded="false">
                            Inventory
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">All Units</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/new' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">New</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/used' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Used</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/tractors' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Tractors</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/trailers' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">All Trailers</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/utility-trailers' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Utility Trailers</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/dump-trailers' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Dump Trailers</a>
                        </div>
                    </div>

                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion" aria-expanded="false">
                            Financing
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">FINANCIAL APPLICATIONS</a>
                            <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">FINANCIAL CALCULATOR</a>
                        </div>
                    </div>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion" aria-expanded="false">
                            Services
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Service Request</a>
                            <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Parts Request</a>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( varner_get_theme_setting( 'support_hub_parts_link', 'https://www.allpartsstore.com/index.htm?customernumber=CO0612' ) ); ?>" target="_blank" rel="noopener" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Online Parts Store</a>
                    <a href="<?php echo esc_url( home_url( '/videos' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Product Videos</a>
                    <a href="https://www.auctiontime.com/listings/upcoming-auctions/varner-equipment?EventCategoryID=7&amp;AccountCRMID=16566180" target="_blank" rel="noopener" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Online Auctions</a>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion" aria-expanded="false">
                            Dealer Info
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">About Us</a>
                            <a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Employment</a>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] hover:text-red-500">Contact</a>
                </nav>
            </div>
        </header>
    </div>
    <?php get_template_part( 'partials/breadcrumb' ); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                const isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                menuIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
                toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                toggle.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
            });
        }

        const accordions = document.querySelectorAll('.mobile-accordion');
        accordions.forEach(acc => {
            acc.addEventListener('click', () => {
                const panel = acc.nextElementSibling;
                const isOpen = !panel.classList.contains('hidden');
                panel.classList.toggle('hidden');
                acc.querySelector('svg').classList.toggle('rotate-180');
                acc.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });
        });

        // ADA: Keyboard-accessible dropdown menus
        document.querySelectorAll('[data-dropdown] > button').forEach(function(btn) {
            var panel = btn.nextElementSibling;

            function openDropdown() {
                panel.classList.remove('opacity-0', 'invisible', 'translate-y-2');
                panel.classList.add('opacity-100', 'visible', 'translate-y-0');
                btn.setAttribute('aria-expanded', 'true');
            }

            function closeDropdown() {
                panel.classList.add('opacity-0', 'invisible', 'translate-y-2');
                panel.classList.remove('opacity-100', 'visible', 'translate-y-0');
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function(e) {
                var isOpen = btn.getAttribute('aria-expanded') === 'true';
                if (isOpen) { closeDropdown(); } else { openDropdown(); }
                e.stopPropagation();
            });

            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var isOpen = btn.getAttribute('aria-expanded') === 'true';
                    if (isOpen) { closeDropdown(); } else { openDropdown(); }
                }
                if (e.key === 'Escape') {
                    closeDropdown();
                    btn.focus();
                }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    openDropdown();
                    var firstLink = panel.querySelector('a');
                    if (firstLink) firstLink.focus();
                }
            });

            // Close when focus leaves the dropdown
            panel.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDropdown();
                    btn.focus();
                }
                if (e.key === 'Tab' && !e.shiftKey) {
                    var focusable = panel.querySelectorAll('a, button');
                    if (focusable.length && document.activeElement === focusable[focusable.length - 1]) {
                        closeDropdown();
                    }
                }
            });

            // Close on click outside
            document.addEventListener('click', function(e) {
                var dd = btn.closest('[data-dropdown]');
                if (dd && !dd.contains(e.target)) {
                    closeDropdown();
                }
            });
        });
    });
    </script>
