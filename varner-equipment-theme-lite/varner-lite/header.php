<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Dynamic SEO Logic
    $seo_description = "Varner Equipment - Colorado's premier dealer for Mahindra tractors, Big Tex trailers, and heavy equipment. Quality machines for agricultural and industrial operations in Delta, CO.";
    $seo_keywords = "Mahindra tractors, Big Tex trailers, Deutz-Fahr, heavy equipment Colorado, farm equipment Delta CO, trailers for sale, agricultural machinery";
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
        $seo_description = "View details for this $year $make $model $cat at Varner Equipment. Your trusted source for high-performance heavy equipment in Delta, Colorado.";
        $seo_keywords = "$make $model, $cat for sale, Varner Equipment inventory, $make dealer Colorado";
        $og_title = "$year $make $model | Varner Equipment";
        $og_type = 'product';
        $images = varner_get_card_images($post_id);
        if (!empty($images)) $og_image = $images[0];
    } elseif (is_page_template('page-equipment-listing.php')) {
        $slug = get_query_var('inventory_segment') ?: sanitize_title(get_the_title());
        $seo = function_exists('varner_get_segment_seo') ? varner_get_segment_seo($slug) : null;
        if ($seo) {
            $seo_description = $seo['sub'] . " Browse our live inventory at Varner Equipment.";
            $og_title = $seo['h1'] . " | Varner Equipment";
        }
    }
    ?>

    <meta name="description" content="<?php echo esc_attr($seo_description); ?>">
    <meta name="keywords" content="<?php echo esc_attr($seo_keywords); ?>">
    
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

    <link rel="canonical" href="<?php echo esc_url($og_url); ?>">

    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 selection:bg-red-100 selection:text-red-600'); ?>>
<?php wp_body_open(); ?>

    <div class="sticky top-0 z-[100] w-full flex flex-col shadow-xl">
        <?php 
            $ann_1 = get_field('announcement_text_1', 'option') ?: 'Mon-Fri: 8am - 5pm';
            $ann_2 = get_field('announcement_text_2', 'option') ?: 'Sat: 9am - Noon';
            $ann_3 = get_field('announcement_text_3', 'option') ?: 'Sun: Closed';
            $phone = get_field('phone_number', 'option') ?: '(970) 874-0612';
            $phone_tel = preg_replace('/[^0-9]/', '', $phone);
            $addr_1 = get_field('address_line_1', 'option') ?: '1375 US-50';
            $addr_2 = get_field('address_line_2', 'option') ?: 'Delta, CO 81416';
        ?>
        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-slate-950 text-white py-2 px-4 border-b border-white/10 relative z-20">
            <div class="max-w-7xl mx-auto flex justify-between items-center text-[10px] font-black uppercase tracking-[0.2em]">
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
                        <img src="<?php echo esc_url($brand_logo_url); ?>" alt="Varner Equipment" class="h-16 md:h-20 w-auto object-contain">
                    </a>

                    <!-- MOBILE MENU TOGGLE -->
                    <button id="mobile-menu-toggle" class="lg:hidden p-3 bg-slate-100 text-slate-900 rounded-2xl hover:bg-red-600 hover:text-white transition-all shadow-sm shrink-0">
                        <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- CENTERED ADDRESS -->
                <div class="flex justify-center text-center w-full lg:w-auto">
                    <a href="#varner-map" class="group flex items-center justify-center gap-1.5 hover:scale-105 transition-transform pointer-events-auto">
                        <svg class="w-6 h-6 lg:w-8 lg:h-8 text-red-600 group-hover:text-slate-900 transition-colors shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <div class="flex flex-col text-center">
                            <span class="font-black text-slate-900 uppercase tracking-tighter text-lg lg:text-xl xl:text-2xl leading-none whitespace-nowrap"><?php echo esc_html($addr_1); ?></span>
                            <span class="font-black text-red-600 uppercase tracking-[0.1em] text-[8px] lg:text-[9px] xl:text-[10px] group-hover:text-slate-900 transition-colors mt-1"><?php echo esc_html($addr_2); ?></span>
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
            <div class="hidden lg:block bg-slate-50 border-t border-slate-200 border-b-4 border-red-600 w-full">
                <div class="max-w-7xl mx-auto px-4">
                    <nav class="flex items-center justify-center gap-6 lg:gap-8 py-4 flex-wrap relative">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Home</a>
                        
                        <!-- INVENTORY DROPDOWN -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default">
                                Inventory
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
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
                            $rows = $wpdb->get_results(
                                "SELECT LOWER(pm.meta_value) AS make, COUNT(*) AS cnt
                                 FROM {$wpdb->postmeta} pm
                                 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                                 WHERE pm.meta_key = 'make' AND pm.meta_value != ''
                                   AND p.post_type = 'equipment' AND p.post_status = 'publish'
                                 GROUP BY LOWER(pm.meta_value)"
                            );
                            $brand_counts = array();
                            foreach ( $rows as $row ) {
                                $brand_counts[ $row->make ] = (int) $row->cnt;
                            }
                            set_transient( 'varner_brand_counts', $brand_counts, HOUR_IN_SECONDS );
                        }

                        if ( ! function_exists( 'varner_brand_link_nav' ) ) {
                            function varner_brand_link_nav( $brand, $brand_counts, $external_url = '' ) {
                                $count  = $brand_counts[ strtolower( $brand ) ] ?? 0;
                                $slug   = sanitize_title( $brand );
                                $href   = $external_url ?: home_url( '/brands/' . $slug );
                                $target = $external_url ? ' target="_blank" rel="noopener"' : '';
                                $dim    = $count === 0 ? ' opacity-40' : '';
                                $badge  = $count > 0
                                    ? '<span class="ml-auto shrink-0 bg-green-100 text-green-700 text-[8px] font-black px-1.5 py-0.5 rounded-full leading-none">' . $count . '</span>'
                                    : '';
                                echo '<a href="' . esc_url( $href ) . '"' . $target . ' class="flex items-center gap-2 py-2 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 hover:pl-2 transition-all border-b border-slate-50 last:border-0' . $dim . '">' . esc_html( $brand ) . $badge . '</a>';
                            }
                        }
                        ?>

                        <!-- BRANDS DROPDOWN (MEGA MENU) -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default">
                                Brands
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                            
                            <!-- Mega Menu Content -->
                            <div class="absolute left-0 lg:-left-48 top-full mt-2 w-[90vw] max-w-5xl bg-white border-t-4 border-red-600 shadow-[0_20px_50px_rgba(0,0,0,0.2)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-4 p-8 rounded-b-2xl">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-12 gap-y-1">
                                    <div class="flex flex-col">
                                        <?php foreach ( ['Bale King', 'Baumalight', 'Beaver Valley', 'Big Tex', 'Bison', 'Brush Chief', 'CM Truck Beds', 'Custom Made'] as $brand ) { varner_brand_link_nav( $brand, $brand_counts ); } ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <?php foreach ( ['Danuser', 'Degelman', 'Deutz Fahr', 'Donahue', 'Enorossi', 'Hackett', 'Interstate', 'Krone'] as $brand ) { $ext = $brand === 'Interstate' ? 'https://www.interstatebatteries.com' : ''; varner_brand_link_nav( $brand, $brand_counts, $ext ); } ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <?php foreach ( ['Legend', 'Macdon', 'Mahindra', 'Maschio', 'Massey Ferguson', 'Maxon', 'MK Martin'] as $brand ) { varner_brand_link_nav( $brand, $brand_counts ); } ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <?php foreach ( ['RC Trailers', 'Speeco', 'Tar River', 'Tidenberg', 'Titan Trailers', 'Triton', 'TYM', 'Worksaver', 'Zetor'] as $brand ) { varner_brand_link_nav( $brand, $brand_counts ); } ?>
                                    </div>
                                </div>
                                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Authorized Premium Dealer</span>
                                    </div>
                                    <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="text-[9px] font-black uppercase tracking-[0.2em] text-red-600 hover:text-slate-900 transition-colors flex items-center gap-2">Explore All Brand Partnerships</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default">Financing</span>
                            <div class="absolute left-0 top-full mt-2 w-64 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Financial Applications</a>
                                <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Financial Calculator</a>
                            </div>
                        </div>

                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default">Services</span>
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Service Request</a>
                                <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Parts Request</a>
                            </div>
                        </div>

                        <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1">Online Parts Store</a>
                        <a href="<?php echo esc_url( home_url( '/videos' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Product Videos</a>
                        
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default">Dealer Info</span>
                            <div class="absolute left-0 top-full mt-2 w-48 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">About Us</a>
                                <a href="<?php echo esc_url( home_url( '/dealer-info/our-team' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Our Team</a>
                                <a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Employment</a>
                            </div>
                        </div>

                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Contact</a>
                    </nav>
                </div>
            </div>

            <!-- MOBILE MENU (Slide Down) -->
            <div id="mobile-menu" class="hidden lg:hidden bg-slate-900 text-white w-full border-t border-white/10 max-h-[80vh] overflow-y-auto">
                <nav class="flex flex-col py-6">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Home</a>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion">
                            Inventory
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">All Units</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/new' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">New</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/used' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Used</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/tractors' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Tractors</a>
                            <a href="<?php echo esc_url( home_url( '/inventory/trailers' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Trailers</a>
                        </div>
                    </div>

                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion">
                            Brands
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2 px-8 flex flex-col">
                            <?php 
                            $all_brands = ['Bale King', 'Baumalight', 'Beaver Valley', 'Big Tex', 'Bison', 'Brush Chief', 'CM Truck Beds', 'Custom Made', 'Danuser', 'Degelman', 'Deutz Fahr', 'Donahue', 'Enorossi', 'Hackett', 'Interstate', 'Krone', 'Legend', 'Macdon', 'Mahindra', 'Maschio', 'Massey Ferguson', 'Maxon', 'MK Martin', 'RC Trailers', 'Speeco', 'Tar River', 'Tidenberg', 'Titan Trailers', 'Triton', 'TYM', 'Worksaver', 'Zetor'];
                            foreach ( $all_brands as $brand ) {
                                $count  = $brand_counts[ strtolower( $brand ) ] ?? 0;
                                $slug   = sanitize_title( $brand );
                                $ext    = $brand === 'Interstate' ? 'https://www.interstatebatteries.com' : '';
                                $href   = $ext ?: home_url( '/brands/' . $slug );
                                $target = $ext ? ' target="_blank" rel="noopener"' : '';
                                $dim    = $count === 0 ? ' opacity-40' : '';
                                $badge  = $count > 0 ? '<span class="ml-auto shrink-0 bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full leading-none">' . $count . '</span>' : '';
                                
                                echo '<a href="' . esc_url( $href ) . '"' . $target . ' class="flex items-center justify-between py-3 text-xs font-bold uppercase text-slate-400 hover:text-white border-b border-white/5 last:border-0 transition-colors' . $dim . '">';
                                echo esc_html( $brand ) . $badge;
                                echo '</a>';
                            }
                            ?>
                            <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="block py-4 mt-2 text-center text-[10px] font-black uppercase tracking-[0.2em] text-red-500 hover:text-white">Explore All Brand Partnerships</a>
                        </div>
                    </div>
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion">
                            Financing
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Financial Applications</a>
                            <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Financial Calculator</a>
                        </div>
                    </div>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion">
                            Services
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/service-request' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Service Request</a>
                            <a href="<?php echo esc_url( home_url( '/parts-request' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Parts Request</a>
                        </div>
                    </div>

                    <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Online Parts Store</a>
                    <a href="<?php echo esc_url( home_url( '/videos' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] border-b border-white/5 hover:text-red-500">Product Videos</a>
                    
                    <div class="border-b border-white/5">
                        <button class="w-full text-left px-8 py-4 font-black uppercase text-sm tracking-[0.2em] flex justify-between items-center group mobile-accordion">
                            Dealer Info
                            <svg class="w-4 h-4 transition-transform group-active:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="hidden bg-white/5 py-2">
                            <a href="<?php echo esc_url( home_url( '/about-us' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">About Us</a>
                            <a href="<?php echo esc_url( home_url( '/our-team' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Our Team</a>
                            <a href="<?php echo esc_url( home_url( '/employment' ) ); ?>" class="block px-12 py-3 text-xs font-bold uppercase text-slate-400 hover:text-white">Employment</a>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="px-8 py-4 font-black uppercase text-sm tracking-[0.2em] hover:text-red-500">Contact</a>
                </nav>
            </div>
        </header>
    </div>
    <?php if ( function_exists('varner_render_breadcrumbs') ) varner_render_breadcrumbs(); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                menu.classList.toggle('hidden');
                menuIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });
        }

        const accordions = document.querySelectorAll('.mobile-accordion');
        accordions.forEach(acc => {
            acc.addEventListener('click', () => {
                const panel = acc.nextElementSibling;
                panel.classList.toggle('hidden');
                acc.querySelector('svg').classList.toggle('rotate-180');
            });
        });
    });
    </script>
