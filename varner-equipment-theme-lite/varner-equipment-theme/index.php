<?php
// Load filter data once — used for category/condition counts in the browse grid below.
$filter_data = varner_get_filter_data();
get_header();
?>

    <!-- HERO SECTION -->
    <section id="hero-parallax" class="relative h-[85vh] bg-slate-950 flex items-center overflow-hidden">
        <div id="hero-parallax-media" class="absolute inset-0 z-0">
            <div class="absolute inset-0 w-full h-full scale-105">
                <!-- CINEMATIC VIDEO BACKGROUND -->
                <video autoplay muted loop playsinline preload="auto" class="w-full h-full object-cover opacity-70">
                    <source src="<?php echo get_template_directory_uri(); ?>/assets/VEHeroVid.mp4" type="video/mp4">
                </video>
                <!-- 40% DARK BLUE OVERLAY -->
                <div class="absolute inset-0 bg-blue-950/40"></div>
                <div class="absolute inset-0 hero-gradient"></div>
            </div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 w-full pb-12">
            <div class="max-w-3xl space-y-8">
                <h1 class="text-5xl md:text-8xl font-black text-white leading-[0.9] tracking-tighter uppercase drop-shadow-2xl">
                    Beyond the <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-red-600">Standard.</span>
                </h1>
                <p class="text-xl text-slate-200 font-bold max-w-xl leading-relaxed drop-shadow-md">
                    Colorado's high-performance source for Mahindra, Big Tex, and Deutz-Fahr. 
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-6 items-start">
                    <a href="<?php echo esc_url( home_url( '/inventory/new' ) ); ?>" class="bg-white text-slate-900 px-12 py-6 rounded-3xl font-black uppercase tracking-widest text-sm shadow-2xl hover:bg-red-600 hover:text-white transition-all">
                        Shop Inventory
                    </a>
                    <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="bg-white/10 backdrop-blur-md border-2 border-white/20 text-white px-12 py-6 rounded-3xl font-black uppercase tracking-widest text-sm hover:bg-white/20 transition-all">
                        Book Service
                    </a>
                </div>
            </div>
        </div>

        <!-- QUICK SEARCH UTILITY (Bottom of Hero) -->
        <div class="absolute bottom-12 left-0 right-0 z-30">
            <div class="max-w-7xl mx-auto px-4">
                <div class="bg-white p-4 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] border-2 border-slate-100 flex flex-col lg:flex-row gap-3 items-center" id="vne-hero-search-wrap">
                    <div class="flex-1 w-full">
                        <input
                            type="text"
                            id="vne-hero-keyword"
                            placeholder="Search Make, Model, VIN, or Keyword..."
                            autocomplete="off"
                            class="w-full h-[38px] px-4 bg-slate-50 rounded-lg font-bold text-xs border border-transparent focus:border-red-500 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div class="w-full lg:w-44">
                        <select id="vne-hero-segment" class="w-full h-[38px] px-3 bg-slate-50 rounded-lg font-black uppercase text-[10px] tracking-widest border border-transparent outline-none cursor-pointer">
                            <option value="">All Types</option>
                            <option value="new">New</option>
                            <option value="used">Used</option>
                            <option value="tractors">Tractors</option>
                            <option value="trailers">Trailers</option>
                            <option value="attachments">Attachments</option>
                            <option value="hay-equipment">Hay Equipment</option>
                            <option value="misc">Misc.</option>
                        </select>
                    </div>
                    <button type="button" id="vne-hero-btn" class="w-full lg:w-auto h-[38px] bg-slate-900 text-white px-8 rounded-lg font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all flex items-center justify-center gap-2 shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Find Machines
                    </button>
                </div>
                <!-- Error messages — shown individually below the bar -->
                <div class="max-w-7xl mx-auto px-4 mt-2 flex flex-col sm:flex-row gap-2">
                    <p id="vne-error-keyword" class="hidden flex-1 text-center bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl">
                        ✕ Please enter a make, model, or keyword
                    </p>
                    <p id="vne-error-type" class="hidden flex-1 text-center bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl">
                        ✕ Please choose a type from the dropdown
                    </p>
                </div>
            </div>
        </div>
    </section>

<script>
(function () {
    var btn          = document.getElementById('vne-hero-btn');
    var keywordInput = document.getElementById('vne-hero-keyword');
    var segmentSelect = document.getElementById('vne-hero-segment');
    var errKeyword   = document.getElementById('vne-error-keyword');
    var errType      = document.getElementById('vne-error-type');
    var base         = '<?php echo esc_js( home_url( '/inventory' ) ); ?>';
    var allBase      = '<?php echo esc_js( home_url( '/all-inventory' ) ); ?>';

    if ( ! btn ) return;

    function hideErrors() {
        errKeyword.classList.add('hidden');
        errType.classList.add('hidden');
        keywordInput && keywordInput.classList.remove('border-red-500');
        segmentSelect && segmentSelect.classList.remove('border-red-500');
    }

    function doSearch() {
        var keyword = keywordInput ? keywordInput.value.trim() : '';
        var seg     = segmentSelect ? segmentSelect.value : '';

        // Validate both fields independently
        var hasError = false;

        if ( ! keyword ) {
            errKeyword.classList.remove('hidden');
            keywordInput && keywordInput.classList.add('border-red-500');
            hasError = true;
        } else {
            errKeyword.classList.add('hidden');
            keywordInput && keywordInput.classList.remove('border-red-500');
        }

        if ( ! seg ) {
            errType.classList.remove('hidden');
            segmentSelect && segmentSelect.classList.add('border-red-500');
            hasError = true;
        } else {
            errType.classList.add('hidden');
            segmentSelect && segmentSelect.classList.remove('border-red-500');
        }

        if ( hasError ) {
            // Auto-hide after 4 seconds
            setTimeout( hideErrors, 4000 );
            return;
        }

        // Both valid — navigate
        var dest = base + '/' + seg + '?fwp_inventory_search=' + encodeURIComponent( keyword );
        window.location.href = dest;
    }

    btn.addEventListener('click', doSearch);

    // Also trigger on Enter key in keyword field
    keywordInput && keywordInput.addEventListener('keydown', function(e) {
        if ( e.key === 'Enter' ) { e.preventDefault(); doSearch(); }
    });

    // Clear errors as user types/selects
    keywordInput && keywordInput.addEventListener('input', function() {
        if ( this.value.trim() ) {
            errKeyword.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });
    segmentSelect && segmentSelect.addEventListener('change', function() {
        if ( this.value ) {
            errType.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });
})();
</script>

    <!-- BRAND TICKER -->
    <section class="bg-white py-0">
        <div class="w-full h-[50px] sm:h-[100px] md:h-[140px] bg-red-600 flex items-center relative overflow-hidden">
            <div class="flex animate-[scroll_70s_linear_infinite] hover:[animation-play-state:paused] w-max will-change-transform">
                <div class="flex items-center shrink-0">
                    <?php 
                    $uploads = wp_get_upload_dir();
                    $upload_base = $uploads['baseurl'] . $uploads['subdir'];
                    $theme_base = get_template_directory_uri() . '/assets/';
                    $theme_path_base = get_template_directory() . '/assets/';

                    // logo filename => [ brand page slug, display name ]
                    $logos = array(
                        'BigTex_white.png'           => array( 'slug' => 'big-tex',       'name' => 'Big Tex Trailers' ),
                        'CMTruckbeds_white.png'      => array( 'slug' => 'cm-truck-beds', 'name' => 'CM Truck Beds' ),
                        'DuetzFahr_white.png'        => array( 'slug' => 'deutz-fahr',    'name' => 'Deutz-Fahr' ),
                        'KRONE_white.png'            => array( 'slug' => 'krone',          'name' => 'Krone' ),
                        'MacDon_white.png'           => array( 'slug' => 'macdon',         'name' => 'MacDon' ),
                        'Mahindra_white.png'         => array( 'slug' => 'mahindra',       'name' => 'Mahindra' ),
                        'McHALE_white.png'           => array( 'slug' => 'mchale',         'name' => 'McHale' ),
                        'ROXR_white.png'             => array( 'slug' => 'roxr',           'name' => 'ROXOR' ),
                        'TitanTrailersMFG_white.png' => array( 'slug' => 'titan-mfg',      'name' => 'Titan Trailers MFG' ),
                        'Triton_white.png'           => array( 'slug' => 'triton',         'name' => 'Triton Trailers' ),
                        'TYM_white.png'              => array( 'slug' => 'tym',            'name' => 'TYM Tractors' ),
                        'Zetor_white.png'            => array( 'slug' => 'zetor',          'name' => 'Zetor' ),
                    );

                    foreach ($logos as $logo => $brand) {
                        $href         = home_url( '/brands/' . $brand['slug'] );
                        $alt          = esc_attr( $brand['name'] . ' — Varner Equipment Dealer' );
                        $extraClasses = ($logo === 'CMTruckbeds_white.png') ? ' scale-90 ' : ' ';
                        $logo_path    = $theme_path_base . $logo;
                        $logo_url     = file_exists($logo_path) ? ($theme_base . $logo) : ($upload_base . '/' . $logo);
                        $logo_version = file_exists($logo_path) ? filemtime($logo_path) : time();

                        echo '<a href="' . esc_url($href) . '" title="' . $alt . '" class="flex items-center justify-center shrink-0 w-32 sm:w-36 md:w-40 lg:w-44 h-12 sm:h-14 md:h-16 lg:h-18 mx-4 sm:mx-6 md:mx-8 group">'
                            . '<img src="' . esc_url($logo_url) . '?v=' . esc_attr($logo_version) . '" alt="' . $alt . '" class="w-full h-full object-contain drop-shadow-xl opacity-90 group-hover:opacity-100 transition-all duration-300 group-hover:scale-110' . $extraClasses . '">'
                            . '</a>';
                    }
                    ?>
                </div>
                <div class="flex items-center shrink-0">
                    <?php
                    foreach ($logos as $logo => $brand) {
                        $href         = home_url( '/brands/' . $brand['slug'] );
                        $alt          = esc_attr( $brand['name'] . ' — Varner Equipment Dealer' );
                        $extraClasses = ($logo === 'CMTruckbeds_white.png') ? ' scale-90 ' : ' ';
                        $logo_path    = $theme_path_base . $logo;
                        $logo_url     = file_exists($logo_path) ? ($theme_base . $logo) : ($upload_base . '/' . $logo);
                        $logo_version = file_exists($logo_path) ? filemtime($logo_path) : time();

                        echo '<a href="' . esc_url($href) . '" title="' . $alt . '" class="flex items-center justify-center shrink-0 w-32 sm:w-36 md:w-40 lg:w-44 h-12 sm:h-14 md:h-16 lg:h-18 mx-4 sm:mx-6 md:mx-8 group">'
                            . '<img src="' . esc_url($logo_url) . '?v=' . esc_attr($logo_version) . '" alt="' . $alt . '" class="w-full h-full object-contain drop-shadow-xl opacity-90 group-hover:opacity-100 transition-all duration-300 group-hover:scale-110' . $extraClasses . '">'
                            . '</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <style>
            @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        </style>
    </section>

    <!-- SUPPORT HUB BAR (Under Hero) -->
    <section class="py-12 bg-white relative z-20 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 p-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                <a href="<?php echo esc_url( home_url( '/service-request' ) ); ?>" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-50 hover:bg-red-50 hover:translate-y-[-2px] transition-all group">
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-900 group-hover:text-red-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.14.15a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.15.14a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.14.15a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0"/><path d="m2 22 5-5"/><path d="M9.5 14.5 16 8"/><path d="m17 2 5 5"/><path d="m3.5 14.5 4 4"/><path d="m10.5 7.5 4 4"/></svg>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-tighter text-lg leading-none mb-1">Request Service</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Certified Techs</div>
                    </div>
                </a>

                <a href="https://www.allpartsstore.com/index.htm?customernumber=CO0612" target="_blank" rel="noopener" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-50 hover:bg-red-50 hover:translate-y-[-2px] transition-all group">
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-900 group-hover:text-red-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-tighter text-lg leading-none mb-1">Order Parts</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">OEM Components</div>
                    </div>
                </a>

                <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-900 text-white hover:bg-red-600 hover:translate-y-[-2px] transition-all group shadow-xl shadow-slate-200">
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-tighter text-lg leading-none mb-1 text-white">Financing</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white/80">Get Pre-Approved</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- CATEGORY GRID -->
    <section class="pt-32 pb-24 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Operations Segments</div>
                    <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase">Browse Inventory</h2>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2">Serving Delta, Grand Junction, and Montrose with high-performance equipment.</p>
                </div>
                <a href="<?php echo esc_url( home_url( '/inventory' ) ); ?>" class="bg-slate-100 px-6 py-3 rounded-xl text-slate-500 font-black uppercase text-[10px] tracking-[0.2em] hover:bg-slate-200 hover:text-red-600 transition-all shadow-sm">See All Inventory</a>
            </div>
            <?php
            // Use varner_get_filter_data() counts instead of 5 separate WP_Query calls.
            // Sum counts across related category/condition values using a simple helper closure.
            $sum_counts = function( $index, $keys ) {
                $total = 0;
                foreach ( (array) $keys as $k ) {
                    if ( isset( $index[ $k ] ) ) $total += (int) $index[ $k ]->cnt;
                }
                return $total;
            };
            $cats  = $filter_data['categories'];
            $conds = $filter_data['conditions'];

            $browse_cards = array(
                array(
                    'label' => 'New Units',
                    'icon'  => 'VE_New_Icon.png',
                    'meta'  => $sum_counts( $conds, array( 'New' ) ) . ' In Stock',
                    'url'   => home_url( '/inventory/new' )
                ),
                array(
                    'label' => 'Used Units',
                    'icon'  => 'VE_Used_Icon.png',
                    'meta'  => $sum_counts( $conds, array( 'Used' ) ) . ' Available',
                    'url'   => home_url( '/inventory/used' )
                ),
                array(
                    'label' => 'Heavy Duty Tractors',
                    'icon'  => 'VE_Tractor_Icon.png',
                    'meta'  => $sum_counts( $cats, array( 'Compact Tractors', 'Tractors' ) ) . ' Machines',
                    'url'   => home_url( '/inventory/tractors' )
                ),
                array(
                    'label' => 'Commercial Trailers',
                    'icon'  => 'VE_Trailer_Icon.png',
                    'meta'  => $sum_counts( $cats, array( 'Commercial Trailers', 'Trailers', 'Dump Trailers' ) ) . ' Stocked',
                    'url'   => home_url( '/inventory/trailers' )
                ),
                array(
                    'label' => 'Attachments',
                    'icon'  => 'VE_Attachment_Icon-300x300.png',
                    'meta'  => $sum_counts( $cats, array( 'Implements', 'Attachments', 'Loaders' ) ) . ' Implements',
                    'url'   => home_url( '/inventory/attachments' )
                ),
                array(
                    'label' => 'Hay Equipment',
                    'icon'  => 'VE_Hay_Icon.png',
                    'meta'  => $sum_counts( $cats, array( 'Hay Equipment' ) ) . ' Units',
                    'url'   => home_url( '/inventory/hay-equipment' )
                ),
            );
            ?>
            <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory lg:grid lg:grid-cols-6 lg:gap-6 lg:overflow-visible lg:pb-0">
                <?php foreach ( $browse_cards as $card ) : ?>
                    <div class="flex flex-col items-center justify-start gap-3 text-slate-900 snap-start shrink-0 lg:shrink">
                        <a href="<?php echo esc_url( $card['url'] ); ?>" class="w-[200px] h-[200px] rounded-2xl bg-white border border-slate-200 shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center overflow-hidden group">
                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/' . $card['icon'] ); ?>" alt="<?php echo esc_attr( $card['label'] . ' for sale in Delta Colorado' ); ?>" class="w-[180px] h-[180px] object-contain group-hover:scale-110 transition-transform" loading="lazy" decoding="async" />
                        </a>
                        <div class="text-center flex flex-col items-center">
                            <a href="<?php echo esc_url( $card['url'] ); ?>" class="font-black text-2xl uppercase tracking-tighter leading-tight hover:text-red-600 transition-colors"><?php echo esc_html( $card['label'] ); ?></a>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1"><?php echo esc_html( $card['meta'] ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- INVENTORY SECTION -->
    <section id="inventory" class="py-24 bg-slate-50 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Recently Added Carousel -->
            <?php 
            $recent_inventory = new WP_Query(array(
                'post_type' => 'equipment',
                'post_status' => 'publish',
                'posts_per_page' => 6,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            $counts = wp_count_posts('equipment');
            $total_units = isset($counts->publish) ? (int) $counts->publish : 0;
            $featured_count_query = new WP_Query(array(
                'post_type' => 'equipment',
                'post_status' => 'publish',
                'meta_key' => 'featured',
                'meta_value' => '1',
                'posts_per_page' => 1,
            ));
            $featured_total = (int) $featured_count_query->found_posts;
            $recent_count_query = new WP_Query(array(
                'post_type' => 'equipment',
                'post_status' => 'publish',
                'date_query' => array(
                    array('after' => '30 days ago'),
                ),
                'posts_per_page' => 1,
            ));
            $recent_total = (int) $recent_count_query->found_posts;
            $instock_query = new WP_Query(array(
                'post_type' => 'equipment',
                'post_status' => 'publish',
                'meta_key' => 'stock_status',
                'meta_value' => 'In Stock',
                'posts_per_page' => 1,
            ));
            $instock_total = (int) $instock_query->found_posts;
        ?>
            <div class="mb-12">
                <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Live Inventory Ledger</div>
                <div class="flex flex-wrap gap-3 items-center bg-white border border-slate-200 rounded-2xl shadow-sm px-5 py-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.35em] text-slate-400">Live Inventory Pulse</span>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 text-green-700 text-[11px] font-black uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <?php echo esc_html( $instock_total ); ?> In Stock
                    </span>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-[11px] font-black uppercase tracking-widest">
                        ★ <?php echo esc_html( $featured_total ); ?> Featured
                    </span>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[11px] font-black uppercase tracking-widest">
                        +<?php echo esc_html( $recent_total ); ?> New (30d)
                    </span>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-black uppercase tracking-widest">
                        Total <?php echo esc_html( $total_units ); ?> Units
                    </span>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase">Featured Inventory</h2>
                </div>
                <a href="<?php echo esc_url( home_url( '/inventory' ) ); ?>" class="bg-slate-100 px-6 py-3 rounded-xl text-slate-500 font-black uppercase text-[10px] tracking-[0.2em] hover:bg-slate-200 hover:text-red-600 transition-all shadow-sm">See All Inventory</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $inventory_args = array(
                    'post_type'      => 'equipment',
                    'posts_per_page' => 6,
                    'post_status'    => 'publish',
                    'meta_query'     => array(
                        array( 'key' => 'featured', 'value' => '1' ),
                    ),
                );
                $inventory_query = new WP_Query( $inventory_args );

                if ( $inventory_query->have_posts() ) :
                    while ( $inventory_query->have_posts() ) : $inventory_query->the_post();
                        varner_include_equipment_card();
                    endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p class="text-slate-500 font-bold col-span-3 text-center py-20">Inventory is currently being updated. Please check back soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- YOUTUBE / VIDEO SECTION -->
    <section class="py-16 md:py-24 bg-slate-950 border-y border-slate-900 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col md:flex-row gap-10 lg:gap-20 items-center">
                <div class="w-full md:w-1/2 space-y-6 text-white text-center md:text-left">
                    <div class="text-red-500 font-black text-[10px] uppercase tracking-[0.4em]">Varner Equipment Media</div>
                    <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1] md:leading-[0.9] tracking-tighter uppercase break-words">See Our Machines<br class="hidden sm:block"/><span class="text-red-500 sm:inline block">In Action</span></h2>
                    <p class="text-base sm:text-lg text-slate-400 font-bold max-w-md mx-auto md:mx-0 leading-relaxed">Subscribe to our YouTube channel for walkthroughs, reviews, and heavy-duty demonstrations right here in Colorado.</p>
                    <a href="https://www.youtube.com/@varnerequipment" target="_blank" rel="noopener" class="inline-block bg-red-600 text-white px-8 py-4 sm:px-10 sm:py-5 rounded-3xl font-black uppercase tracking-widest text-[10px] shadow-xl hover:bg-white hover:text-red-600 transition-all mt-4 border border-red-500">
                        Visit Our Channel
                    </a>
                </div>
                <div class="w-full md:w-1/2">
                    <div id="yt-facade" class="aspect-video bg-slate-900 rounded-2xl md:rounded-[2rem] overflow-hidden border border-slate-800 md:border-2 shadow-2xl relative group cursor-pointer w-full" onclick="(function(el){var iframe=document.createElement('iframe');iframe.src='https://www.youtube.com/embed/goF_3TspZ6k?autoplay=1&rel=0';iframe.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';iframe.allowFullscreen=true;iframe.className='w-full h-full absolute inset-0';el.innerHTML='';el.appendChild(iframe);})(this)">
                        <div class="absolute inset-0 flex items-center justify-center z-10">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-red-600 rounded-full flex items-center justify-center pl-1 sm:pl-2 shadow-[0_0_30px_rgba(220,38,38,0.5)] group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                        <img src="https://img.youtube.com/vi/goF_3TspZ6k/maxresdefault.jpg" alt="Varner Equipment Video" class="w-full h-full object-cover opacity-60 group-hover:opacity-80 transition-opacity duration-500 scale-105 group-hover:scale-100">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TO-DO LIST CTA SECTION -->
    <section class="py-16 md:py-24 bg-white border-b border-slate-200 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col md:flex-row gap-10 lg:gap-16 items-center">
                <div class="w-full md:w-7/12 text-center md:text-left">
                    <h2 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-slate-900 leading-[1] md:leading-[0.9] tracking-tighter uppercase break-words">
                        What's Next On<br class="hidden sm:block" />
                        Your To-Do List?
                    </h2>
                </div>
                <div class="w-full md:w-5/12 flex flex-col items-center md:items-start gap-6 sm:gap-8 text-center md:text-left">
                    <p class="text-lg sm:text-xl text-slate-600 font-bold leading-relaxed">
                        Varner Equipment is a family owned and operated tractor and trailer dealership. We are your one stop for equipment that you can rely on.
                    </p>
                    <a href="#" class="inline-block bg-slate-900 text-white px-10 py-5 sm:px-12 sm:py-6 rounded-3xl font-black uppercase tracking-widest text-[10px] sm:text-sm shadow-xl hover:bg-red-600 hover:text-white transition-all w-full sm:w-auto">
                        Learn more
                    </a>
                </div>
            </div>
        </div>
    </section>
    <div class="h-2 bg-red-600"></div>

<?php get_footer(); ?>
