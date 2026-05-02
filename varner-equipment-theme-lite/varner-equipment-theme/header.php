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

                <div class="flex items-center gap-4 relative z-10">
                    <a href="tel:9708740612" class="bg-red-600 text-white px-6 md:px-8 py-3 md:py-4 rounded-2xl font-black flex items-center gap-2 shadow-lg hover:bg-red-700 transition-all active:scale-95 text-lg md:text-xl">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="hidden sm:inline">(970) 874-0612</span>
                        <span class="sm:hidden">CALL</span>
                    </a>
                </div>
            </div>

            <!-- NAVIGATION ROW -->
            <div class="bg-slate-50 border-t border-slate-200 border-b-4 border-red-600 w-full">
                <!-- Removed overflow-x-auto so dropdowns don't get clipped, use flex-wrap instead if needed for mobile -->
                <div class="max-w-7xl mx-auto px-4">
                    <nav class="flex items-center justify-center gap-6 lg:gap-8 py-4 flex-wrap relative">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Home</a>
                        
                        <!-- INVENTORY DROPDOWN -->
                        <div class="group relative">
                            <a href="<?php echo esc_url( home_url( '/inventory' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1">
                                Inventory
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/inventory/in-stock-inventory' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">In Stock Inventory</a>
                                <a href="<?php echo esc_url( home_url( '/inventory/showroom-inventory' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Showroom Inventory</a>
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
                            echo '<a href="' . esc_url( $href ) . '"' . $target . ' class="flex items-center gap-2 py-2 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-red-600 hover:pl-2 transition-all border-b border-slate-50 last:border-0' . $dim . '">'
                                . esc_html( $brand ) . $badge
                                . '</a>';
                        }
                        ?>

                        <!-- BRANDS DROPDOWN (MEGA MENU) -->
                        <div class="group relative">
                            <a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1">
                                Brands
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </a>

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
                            <a href="<?php echo esc_url( home_url( '/financing' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1">
                                Financing
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
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
                            <a href="<?php echo esc_url( home_url( '/services' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1">
                                Services
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <!-- Dropdown Menu -->
                            <div class="absolute left-0 top-full mt-2 w-56 bg-white border-t-2 border-red-600 shadow-[0_10px_40px_rgba(0,0,0,0.1)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top group-hover:translate-y-0 translate-y-2">
                                <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 border-b border-slate-100 transition-colors">Service Request</a>
                                <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="block px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Parts Request</a>
                            </div>
                        </div>

                        <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1">
                            Online Parts Store
                        </a>
                        <a href="<?php echo esc_url( home_url( '/product-videos' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors">Product Videos</a>
                        
                        <!-- DEALER INFO DROPDOWN -->
                        <div class="group relative">
                            <a href="<?php echo esc_url( home_url( '/dealer-info' ) ); ?>" class="font-black uppercase text-xs tracking-widest text-slate-700 hover:text-red-600 transition-colors flex items-center gap-1 pb-1">
                                Dealer Info
                                <svg class="w-3 h-3 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
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
            </div>
        </header>
    </div>
