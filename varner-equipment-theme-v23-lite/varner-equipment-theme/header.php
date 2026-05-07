<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 selection:bg-red-100 selection:text-red-600'); ?>>
<?php wp_body_open(); ?>

    <div class="sticky top-0 z-[100] w-full flex flex-col shadow-xl">
        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-slate-950 text-white py-2 px-4 border-b border-white/10 relative z-20">
            <div class="max-w-7xl mx-auto flex justify-between items-center text-[10px] font-black uppercase tracking-[0.2em]">
                <div class="flex gap-4 sm:gap-6 items-center flex-wrap">
                    <span>Mon-Fri: 8am - 5pm</span>
                    <span class="hidden sm:inline text-slate-500">|</span>
                    <span class="hidden sm:inline">Sat: 9am - Noon</span>
                    <span class="hidden sm:inline text-slate-500">|</span>
                    <span class="hidden sm:inline">Sun: Closed</span>
                </div>
                <div class="flex gap-4">
                    <span class="text-red-500">●</span>
                    <?php
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
            <div class="max-w-7xl mx-auto px-4 py-4 md:py-5 flex items-center justify-between w-full relative">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center justify-center transform hover:rotate-1 transition-transform shrink-0">
                    <?php 
                    $brand_logo_url = function_exists('varner_get_brand_logo_url') ? varner_get_brand_logo_url('red') : '';
                    ?>
                    <img src="<?php echo esc_url($brand_logo_url); ?>" alt="Varner Equipment" class="h-16 md:h-20 w-auto object-contain">
                </a>

                <!-- CENTERED ADDRESS -->
                <div class="hidden lg:flex absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center justify-center z-10 pointer-events-none pl-20 xl:pl-32 w-[350px]">
                    <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener" class="group flex items-center justify-center gap-1.5 hover:scale-105 transition-transform pointer-events-auto w-full">
                        <svg class="w-8 h-8 text-red-600 group-hover:text-slate-900 transition-colors shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <div class="flex flex-col text-center">
                            <span class="font-black text-slate-900 uppercase tracking-tighter text-2xl leading-none">1375 US-50</span>
                            <span class="font-black text-red-600 uppercase tracking-[0.1em] text-[10px] group-hover:text-slate-900 transition-colors mt-1">Delta, CO 81416</span>
                        </div>
                    </a>
                </div>

                <div class="hidden md:flex items-center gap-4 relative z-10">
                    <a href="tel:9708740612" class="bg-red-600 text-white px-6 md:px-8 py-3 md:py-4 rounded-2xl font-black flex items-center gap-2 shadow-lg hover:bg-red-700 transition-all active:scale-95 text-lg md:text-xl">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="hidden sm:inline">(970) 874-0612</span>
                        <span class="sm:hidden">CALL</span>
                    </a>
                </div>
            </div>

            <!-- MOBILE ADDRESS + PHONE -->
            <div class="md:hidden px-4 pb-4 -mt-1 space-y-3">
                <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 11a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>
                    <div class="flex flex-col leading-tight">
                        <span class="text-slate-900 font-black text-sm tracking-tight">1375 US-50</span>
                        <span class="text-red-600 font-black text-[11px] uppercase tracking-[0.15em]">Delta, CO 81416</span>
                    </div>
                </a>
                <a href="tel:9708740612" class="flex items-center justify-center gap-2 bg-red-600 text-white px-4 py-3 rounded-2xl font-black uppercase tracking-[0.18em] text-sm shadow-lg hover:bg-red-700 active:scale-95 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    <span>(970) 874-0612</span>
                </a>
            </div>

            <!-- NAVIGATION ROW -->
            <div class="bg-slate-50 border-t border-slate-200 border-b-4 border-red-600 w-full">
                <!-- Removed overflow-x-auto so dropdowns don't get clipped, use flex-wrap instead if needed for mobile -->
                <div class="max-w-7xl mx-auto px-4">
                    <?php
                    $inventory_page = get_page_by_path( 'inventory' );
                    $in_stock_page  = get_page_by_path( 'inventory/in-stock-inventory' ) ?: get_page_by_path( 'in-stock-inventory' );
                    $showroom_page  = get_page_by_path( 'inventory/showroom-inventory' ) ?: get_page_by_path( 'showroom-inventory' );

                    $all_inventory_link = $inventory_page ? get_permalink( $inventory_page ) : home_url( '/inventory' );
                    $in_stock_link      = $in_stock_page ? get_permalink( $in_stock_page ) : home_url( '/inventory/in-stock-inventory' );
                    $showroom_link      = $showroom_page ? get_permalink( $showroom_page ) : home_url( '/inventory/showroom-inventory' );
                    ?>

                    <div class="flex items-center justify-between md:justify-center py-3 md:py-4 gap-3 md:gap-0">
                        <button id="ve-nav-toggle" class="md:hidden flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm active:scale-95" aria-expanded="false" aria-controls="ve-mobile-nav">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            Menu
                        </button>

                        <nav id="ve-desktop-nav" class="hidden md:flex items-center justify-center gap-6 lg:gap-8 flex-wrap relative">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Home</a>
                            
                            <!-- INVENTORY DROPDOWN -->
                            <div class="group relative">
                                <span class="font-black uppercase text-xs tracking-widest text-slate-700 group-hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default select-none">
                                    Inventory
                                    <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                                <!-- Dropdown Menu -->
                                <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                    <a href="<?php echo esc_url( $all_inventory_link ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">All Inventory</a>
                                    <a href="<?php echo esc_url( $in_stock_link ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">In Stock Inventory</a>
                                    <a href="<?php echo esc_url( $showroom_link ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Showroom Inventory</a>
                                </div>
                            </div>

                            <?php
                        // Brand unit counts — cached 1 hour so it doesn't query on every page load
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

                        function varner_brand_link( $brand, $brand_counts, $external_url = '' ) {
                            $count    = $brand_counts[ strtolower( $brand ) ] ?? 0;
                            $slug     = sanitize_title( $brand );
                            $href     = $external_url ?: home_url( '/brands/' . $slug );
                            $target   = $external_url ? ' target="_blank" rel="noopener"' : '';
                            $dim      = $count === 0 ? ' opacity-40' : '';
                            $badge    = $count > 0
                                ? '<span class="ml-auto shrink-0 bg-green-100 text-green-700 text-[8px] font-black px-1.5 py-0.5 rounded-full leading-none">' . $count . '</span>'
                                : '';
                            echo '<a href="' . esc_url( $href ) . '"' . $target . ' class="flex items-center gap-2 py-2 text-xs font-black uppercase tracking-widest text-slate-600 hover:text-red-600 hover:pl-2 transition-all border-b border-slate-50 last:border-0' . $dim . '">'
                                . esc_html( $brand ) . $badge
                                . '</a>';
                        }
                        ?>

                        <!-- BRANDS DROPDOWN (MEGA MENU) -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 group-hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default select-none">
                                Brands
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>

                            <!-- Mega Menu Content -->
                            <div class="absolute left-0 lg:-left-48 top-full mt-2 w-[90vw] max-w-5xl bg-white border-t-4 border-red-600 shadow-[0_20px_50px_rgba(0,0,0,0.2)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-4 p-8 rounded-b-2xl">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-12 gap-y-1">
                                    <!-- Column 1 -->
                                    <div class="flex flex-col">
                                        <?php
                                        foreach ( ['Bale King', 'Baumalight', 'Beaver Valley', 'Big Tex', 'Bison', 'Brush Chief', 'CM Truck Beds', 'Custom Made'] as $brand ) {
                                            varner_brand_link( $brand, $brand_counts );
                                        } ?>
                                    </div>
                                    <!-- Column 2 -->
                                    <div class="flex flex-col">
                                        <?php
                                        foreach ( ['Danuser', 'Degelman', 'Deutz Fahr', 'Donahue', 'Enorossi', 'Hackett', 'Interstate', 'Krone'] as $brand ) {
                                            $ext = $brand === 'Interstate' ? 'https://www.interstatebatteries.com' : '';
                                            varner_brand_link( $brand, $brand_counts, $ext );
                                        } ?>
                                    </div>
                                    <!-- Column 3 -->
                                    <div class="flex flex-col">
                                        <?php
                                        foreach ( ['Legend', 'Macdon', 'Mahindra', 'Maschio', 'Massey Ferguson', 'Maxon', 'McHale', 'MK Martin'] as $brand ) {
                                            varner_brand_link( $brand, $brand_counts );
                                        } ?>
                                    </div>
                                    <!-- Column 4 -->
                                    <div class="flex flex-col">
                                        <?php
                                        foreach ( ['RC Trailers', 'Speeco', 'Tar River', 'Tidenberg', 'Titan MFG', 'Triton', 'TYM', 'Worksaver'] as $brand ) {
                                            varner_brand_link( $brand, $brand_counts );
                                        } ?>
                                    </div>
                                </div>
                                
                                <!-- Bottom Footer -->
                                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Authorized Premium Dealer</span>
                                    </div>
                                    <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="text-[9px] font-black uppercase tracking-[0.2em] text-red-600 hover:text-slate-900 transition-colors flex items-center gap-2">
                                        Explore All Brand Partnerships
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FINANCING DROPDOWN -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 group-hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default select-none">
                                Financing
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                            <!-- Dropdown Menu (PDF Links) -->
                            <div class="absolute left-0 top-full mt-2 w-64 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <?php 
                                $uploads = wp_get_upload_dir();
                                // We are assuming the files were uploaded in the current month. 
                                // If they don't load, WordPress allows linking directly from the root of uploads if organized differently.
                                // It's safer to use a WordPress function to fetch the attachment URL if possible, but for static files this works well.
                                ?>
                                <a href="<?php echo esc_url( $uploads['baseurl'] . '/2026/04/Wells-Fargo-Application.pdf' ); ?>" target="_blank" rel="noopener" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Wells Fargo Application</a>
                                <a href="<?php echo esc_url( $uploads['baseurl'] . '/2026/04/dll-application-rev.pdf' ); ?>" target="_blank" rel="noopener" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">DLL Application</a>
                                <a href="<?php echo esc_url( $uploads['baseurl'] . '/2026/04/AgDirect-Application.pdf' ); ?>" target="_blank" rel="noopener" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">AgDirect Application</a>
                                <a href="<?php echo esc_url( $uploads['baseurl'] . '/2026/04/sheffield-application-rev.pdf' ); ?>" target="_blank" rel="noopener" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Sheffield Application</a>
                            </div>
                        </div>

                        <!-- SERVICES DROPDOWN -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 group-hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default select-none">
                                Services
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Service Request</a>
                                <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Parts Request</a>
                            </div>
                        </div>

                        <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1">
                            Online Parts Store
                        </a>
                        <a href="https://www.youtube.com/@VarnerEquipment" target="_blank" rel="noopener" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Product Videos</a>
                        
                        <!-- DEALER INFO DROPDOWN -->
                        <div class="group relative">
                            <span class="font-black uppercase text-xs tracking-widest text-slate-700 group-hover:text-red-600 transition-colors flex items-center gap-1 pb-1 cursor-default select-none">
                                Dealer Info
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-48 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">About Us</a>
                                <a href="<?php echo esc_url( home_url( '/dealer-info/our-team' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Our Team</a>
                                <a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Employment</a>
                            </div>
                        </div>

                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Contact</a>
                        </nav>
                    </div>

                    <div id="ve-mobile-nav" class="md:hidden hidden flex-col gap-2 pb-4">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Home</a>
                        <a href="<?php echo esc_url( $all_inventory_link ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">All Inventory</a>
                        <a href="<?php echo esc_url( $in_stock_link ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">In Stock</a>
                        <a href="<?php echo esc_url( $showroom_link ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Showroom</a>
                        <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Brands</a>
                        <a href="<?php echo esc_url( home_url( '/financing' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Financing</a>
                        <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Service Request</a>
                        <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Parts Request</a>
                        <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Online Parts Store</a>
                        <a href="https://www.youtube.com/@VarnerEquipment" target="_blank" rel="noopener" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Product Videos</a>
                        <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">About Us</a>
                        <a href="<?php echo esc_url( home_url( '/dealer-info/our-team' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Our Team</a>
                        <a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Employment</a>
                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-black uppercase text-[11px] tracking-[0.18em] shadow-sm">Contact</a>
                    </div>
                </div>
            </div>
        </header>
    </div>

    <script>
    (function() {
        var toggle = document.getElementById('ve-nav-toggle');
        var mobile = document.getElementById('ve-mobile-nav');
        if (!toggle || !mobile) return;

        function hideMobile() {
            mobile.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var isHidden = mobile.classList.contains('hidden');
            if (isHidden) {
                mobile.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                hideMobile();
            }
        });

        document.addEventListener('click', function(e) {
            if (!mobile.classList.contains('hidden') && !mobile.contains(e.target) && e.target !== toggle) {
                hideMobile();
            }
        });
    })();
    </script>
