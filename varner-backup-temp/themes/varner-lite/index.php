<?php 
    get_header();
    $h_headline_main   = get_field('hero_headline_main') ?: 'Beyond the';
    $h_headline_accent = get_field('hero_headline_accent') ?: 'Standard.';
    $h_subline         = get_field('hero_subheadline') ?: "Colorado's premier destination for high-performance new and used farm equipment. Authorized dealer for Mahindra, Big Tex, Deutz-Fahr, and dozens of other world-class agricultural and construction brands.";
    $h_btn_1           = get_field('hero_button_1_text') ?: 'Shop Inventory';
    $h_btn_2           = get_field('hero_button_2_text') ?: 'Book Service';
    $h_btn_3           = get_field('hero_button_3_text') ?: 'Order Parts';
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

        <div class="relative z-40 max-w-7xl mx-auto px-4 w-full pb-12">
            <div class="max-w-3xl space-y-8">
                <h1 class="text-5xl md:text-8xl font-black text-white leading-[0.9] tracking-tighter uppercase drop-shadow-2xl reveal-on-scroll">
                    <?php echo $h_headline_main; ?> <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-red-600 reveal-on-scroll delay-200 inline-block"><?php echo $h_headline_accent; ?></span>
                </h1>
                <p class="text-xl text-slate-200 font-bold max-w-xl leading-relaxed drop-shadow-md reveal-on-scroll delay-300">
                    <?php echo esc_html($h_subline); ?>
                </p>
                <div class="flex flex-col sm:flex-row flex-wrap gap-4 pt-6 items-start">
                    <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="relative z-50 bg-white text-slate-900 px-12 py-6 rounded-3xl font-black uppercase tracking-widest text-sm shadow-2xl hover:bg-red-600 hover:text-white transition-all reveal-on-scroll delay-400">
                        <?php echo esc_html($h_btn_1); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="relative z-50 bg-white/10 backdrop-blur-md border-2 border-white/20 text-white px-12 py-6 rounded-3xl font-black uppercase tracking-widest text-sm hover:bg-white/20 transition-all reveal-on-scroll delay-500">
                        <?php echo esc_html($h_btn_2); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="relative z-50 bg-white/10 backdrop-blur-md border-2 border-white/20 text-white px-12 py-6 rounded-3xl font-black uppercase tracking-widest text-sm hover:bg-white/20 transition-all reveal-on-scroll delay-700">
                        <?php echo esc_html($h_btn_3); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- QUICK SEARCH UTILITY (Bottom of Hero) -->
        <div class="absolute bottom-12 left-0 right-0 z-30 reveal-on-scroll delay-1000">
            <div class="max-w-7xl mx-auto px-4">
                <form id="hero-quick-search" action="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" method="get" class="bg-white/80 backdrop-blur-xl p-4 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] border border-white/30 flex flex-col lg:flex-row gap-3 items-center">
                    <div class="flex-1 w-full">
                        <input id="hero-search-input" type="text" name="s" placeholder="Search Model, VIN, or Type..." class="w-full h-12 lg:h-[30px] px-4 bg-slate-50 rounded-lg font-bold text-xs border border-transparent focus:border-red-500 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div class="w-full lg:w-40">
                        <select id="hero-search-category" name="category[]" class="w-full h-12 lg:h-[30px] px-3 bg-slate-50 rounded-lg font-black uppercase text-[10px] tracking-widest border border-transparent outline-none cursor-pointer">
                            <option value="" disabled selected hidden>Select Type</option>
                            <option value="all">All Types</option>
                            <option value="__new__">New</option>
                            <option value="__used__">Used</option>
                            <option value="Tractors">Tractors</option>
                            <option value="Trailers">Trailers</option>
                            <option value="Attachments">Attachments</option>
                            <option value="Hay Equipment">Hay Equipment</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full lg:w-auto h-12 lg:h-[30px] bg-slate-900 text-white px-8 rounded-lg font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">
                        Find Machines
                    </button>
                </form>
                <p id="hero-search-error" class="mt-2 text-xs font-bold uppercase tracking-widest text-red-600 hidden"></p>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('hero-quick-search');
            const queryInput = document.getElementById('hero-search-input');
            const categorySelect = document.getElementById('hero-search-category');
            const errorEl = document.getElementById('hero-search-error');

            if (!form || !queryInput || !categorySelect || !errorEl) return;

            form.addEventListener('submit', function(e) {
                const query = queryInput.value.trim();
                const categoryRaw = categorySelect.value.trim();
                const hasCategory = categoryRaw !== '';
                const categoryIsAll = categoryRaw === 'all';
                const categoryIsNew = categoryRaw === '__new__';
                const categoryIsUsed = categoryRaw === '__used__';
                const categoryForFilter = (categoryIsAll || categoryIsNew || categoryIsUsed) ? '' : categoryRaw;
                let message = '';

                if (!query && !hasCategory) {
                    message = 'Please enter a model, brand, VIN, or type and select a type.';
                } else if (query && !hasCategory) {
                    message = 'Please select a type.';
                } else if (!query && hasCategory) {
                    message = 'Please enter a model, brand, VIN, or type.';
                }

                if (message) {
                    e.preventDefault();
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Reset dynamic fields
                    categorySelect.name = 'category[]';
                    const existingCondition = form.querySelector('input[name="condition[]"]');
                    if (existingCondition) existingCondition.remove();

                    if (categoryIsAll) {
                        categorySelect.name = '';
                        categorySelect.value = '';
                    } else if (categoryIsNew || categoryIsUsed) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'condition[]';
                        hidden.value = categoryIsNew ? 'New' : 'Used';
                        form.appendChild(hidden);
                        categorySelect.name = '';
                    } else {
                        categorySelect.value = categoryForFilter;
                    }

                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }
            });
        });
        </script>
    </section>

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

                    $logos = array(
                        'BigTex_white.png' => 'big-tex',
                        'CMTruckbeds_white.png' => 'cm-truck-beds',
                        'DuetzFahr_white.png' => 'deutz-fahr', 
                        'KRONE_white.png' => 'krone',
                        'MacDon_white.png' => 'macdon',
                        'Mahindra_white.png' => 'mahindra', 
                        'McHALE_white.png' => 'mchale',
                        'ROXR_white.png' => 'roxr',
                        'TitanTrailersMFG_white.png' => 'titan-mfg', 
                        'Triton_white.png' => 'triton',
                        'TYM_white.png' => 'tym',
                        'Zetor_white.png' => 'zetor'
                    );
                    
                    foreach ($logos as $logo => $slug) {
                        $extraClasses = ($logo === 'CMTruckbeds_white.png') ? ' scale-90 ' : ' ';
                        $logo_path = $theme_path_base . $logo;
                        $logo_url = file_exists($logo_path) ? ($theme_base . $logo) : ($upload_base . '/' . $logo);
                        $logo_version = file_exists($logo_path) ? filemtime($logo_path) : time();
                        $brand_name = str_replace(['_white.png', 'MFG_white.png'], '', $logo);
                        $brand_name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $brand_name);

                        echo '<a href="' . esc_url( home_url( '/brands/' . $slug ) ) . '" class="flex items-center justify-center shrink-0 w-32 sm:w-36 md:w-40 lg:w-44 h-12 sm:h-14 md:h-16 lg:h-18 mx-4 sm:mx-6 md:mx-8 hover:scale-110 transition-transform">'
                            . '<img src="' . esc_url($logo_url) . '?v=' . esc_attr($logo_version) . '" alt="' . esc_attr($brand_name) . ' Authorized Dealer" class="w-full h-full object-contain drop-shadow-xl opacity-90 hover:opacity-100 transition-all duration-300' . $extraClasses . '">'
                            . '</a>';
                    }
                    ?>
                </div>
                <div class="flex items-center shrink-0">
                    <?php 
                    foreach ($logos as $logo => $slug) {
                        $extraClasses = ($logo === 'CMTruckbeds_white.png') ? ' scale-90 ' : ' ';
                        $logo_path = $theme_path_base . $logo;
                        $logo_url = file_exists($logo_path) ? ($theme_base . $logo) : ($upload_base . '/' . $logo);
                        $logo_version = file_exists($logo_path) ? filemtime($logo_path) : time();
                        $brand_name = str_replace(['_white.png', 'MFG_white.png'], '', $logo);
                        $brand_name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $brand_name);

                        echo '<a href="' . esc_url( home_url( '/brands/' . $slug ) ) . '" class="flex items-center justify-center shrink-0 w-32 sm:w-36 md:w-40 lg:w-44 h-12 sm:h-14 md:h-16 lg:h-18 mx-4 sm:mx-6 md:mx-8 hover:scale-110 transition-transform">'
                            . '<img src="' . esc_url($logo_url) . '?v=' . esc_attr($logo_version) . '" alt="' . esc_attr($brand_name) . ' Authorized Dealer" class="w-full h-full object-contain drop-shadow-xl opacity-90 hover:opacity-100 transition-all duration-300' . $extraClasses . '">'
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
    <section class="py-12 bg-white relative z-20 overflow-hidden reveal-on-scroll">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 p-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                <a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-50 hover:bg-red-50 hover:translate-y-[-2px] transition-all group">
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-900 group-hover:text-red-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.14.15a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.15.14a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a2 2 0 0 1 2.82 0l.14.15a2 2 0 0 1 0 2.82l-3.77 3.77a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0"/></svg>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-tighter text-lg leading-none mb-1">Request Service</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Certified Techs</div>
                    </div>
                </a>

                <a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-50 hover:bg-red-50 hover:translate-y-[-2px] transition-all group">
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-900 group-hover:text-red-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-tighter text-lg leading-none mb-1">Order Parts</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">OEM Components</div>
                    </div>
                </a>

                <a href="<?php echo esc_url( home_url( '/finance' ) ); ?>" class="flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-900 text-white hover:bg-red-600 hover:translate-y-[-2px] transition-all group shadow-xl shadow-slate-200">
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
    <section class="pt-32 pb-24 bg-slate-100 reveal-on-scroll">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Operations Segments</div>
                    <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase">Browse by Category</h2>
                </div>
                <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="bg-slate-100 px-6 py-3 rounded-xl text-slate-500 font-black uppercase text-[10px] tracking-[0.2em] hover:bg-slate-200 hover:text-red-600 transition-all shadow-sm">See All Inventory</a>
            </div>
            <?php
            $segment_filters = array(
                'tractors'      => varner_get_segment_seo('tractors'),
                'trailers'      => varner_get_segment_seo('trailers'),
                'attachments'   => varner_get_segment_seo('attachments'),
                'hay-equipment' => varner_get_segment_seo('hay-equipment'),
            );

            $count_categories = function( $categories ) {
                if ( empty( $categories ) ) {
                    return 0;
                }

                $query = new WP_Query(array(
                    'post_type'      => 'equipment',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'     => 'category',
                            'value'   => $categories,
                            'compare' => 'IN',
                        ),
                    ),
                ));

                return intval( $query->found_posts );
            };

            $tractor_count     = $count_categories( $segment_filters['tractors']['filter']['category'] ?? array() );
            $trailer_count     = $count_categories( $segment_filters['trailers']['filter']['category'] ?? array() );
            $attachment_count  = $count_categories( $segment_filters['attachments']['filter']['category'] ?? array() );
            $hay_count         = $count_categories( $segment_filters['hay-equipment']['filter']['category'] ?? array() );

            $used_count = new WP_Query(array('post_type' => 'equipment', 'meta_key' => 'condition', 'meta_value' => 'Used', 'post_status' => 'publish'));
            $new_count  = new WP_Query(array('post_type' => 'equipment', 'meta_key' => 'condition', 'meta_value' => 'New',  'post_status' => 'publish'));

            $browse_cards = array(
                array('label' => 'New', 'icon' => 'VE_New_Icon.png', 'meta' => $new_count->found_posts . ' Units', 'url' => home_url('/inventory/new')),
                array('label' => 'Used', 'icon' => 'VE_Used_Icon.png', 'meta' => $used_count->found_posts . ' Units', 'url' => home_url('/inventory/used')),
                array('label' => 'Tractors', 'icon' => 'VE_Tractor_Icon.png', 'meta' => $tractor_count . ' Units', 'url' => home_url('/inventory/tractors')),
                array('label' => 'Trailers', 'icon' => 'VE_Trailer_Icon.png', 'meta' => $trailer_count . ' Units', 'url' => home_url('/inventory/trailers')),
                array('label' => 'Attachments', 'icon' => 'VE_Attachment_Icon-300x300.png', 'meta' => $attachment_count . ' Units', 'url' => home_url('/inventory/attachments')),
                array('label' => 'Hay Equipment', 'icon' => 'VE_Hay_Icon.png', 'meta' => $hay_count . ' Units', 'url' => home_url('/inventory/hay-equipment')),
            );
            ?>
            <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory lg:grid lg:grid-cols-6 lg:gap-6 lg:overflow-visible lg:pb-0">
                <?php foreach ( $browse_cards as $card ) : ?>
                    <a href="<?php echo esc_url( $card['url'] ); ?>" class="flex flex-col items-center justify-start gap-3 text-slate-900 snap-start shrink-0 lg:shrink group">
                        <div class="w-[200px] h-[200px] rounded-2xl bg-white border border-slate-200 shadow-md group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all flex items-center justify-center overflow-hidden">
                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/' . $card['icon'] ); ?>" alt="<?php echo esc_attr( $card['label'] ); ?> icon" class="w-[180px] h-[180px] object-contain" loading="lazy" decoding="async" />
                        </div>
                        <div class="text-center flex flex-col items-center">
                            <div class="font-black text-2xl uppercase tracking-tighter leading-tight group-hover:text-red-600 transition-colors"><?php echo esc_html( $card['label'] ); ?></div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1"><?php echo esc_html( $card['meta'] ); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- INVENTORY SECTION -->
    <section id="inventory" class="py-24 bg-slate-50 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4">
            <?php 
            // Removed Live Inventory Pulse block per request
            ?>

            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Live Inventory Ledger</div>
                    <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase">Featured Inventory</h2>
                </div>
                <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="bg-slate-100 px-6 py-3 rounded-xl text-slate-500 font-black uppercase text-[10px] tracking-[0.2em] hover:bg-slate-200 hover:text-red-600 transition-all shadow-sm">See All Inventory</a>
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
                        $post_id         = get_the_ID();
                        $year            = get_field( 'year',         $post_id );
                        $make            = get_field( 'make',         $post_id );
                        $model           = get_field( 'model',        $post_id );
                        $price           = get_field( 'price',        $post_id );
                        $category        = get_field( 'category',     $post_id );
                        $condition       = get_field( 'condition',    $post_id );
                        $stock_status    = get_field( 'stock_status', $post_id );
                        $stock_number    = get_field( 'stock_number', $post_id );
                        $length          = get_field( 'length',       $post_id );
                        $call_for_price  = get_field( 'call_for_price', $post_id );
                        $formatted_price = $call_for_price ? 'Call For Price' : (is_numeric( $price ) ? number_format( $price ) : (string) $price);
                        $images          = varner_get_card_images( $post_id );
                        include get_template_directory() . '/partials/equipment-card.php';
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
                    <a href="https://www.youtube.com/@VarnerEquipment" target="_blank" class="inline-block bg-red-600 text-white px-8 py-4 sm:px-10 sm:py-5 rounded-3xl font-black uppercase tracking-widest text-[10px] shadow-xl hover:bg-white hover:text-red-600 transition-all mt-4 border border-red-500">
                        Visit Our Channel
                    </a>
                </div>
                <div class="w-full md:w-1/2">
                    <div class="aspect-video bg-slate-900 rounded-2xl md:rounded-[2rem] overflow-hidden border border-slate-800 md:border-2 shadow-2xl relative group w-full">
                        <iframe 
                            class="w-full h-full"
                            src="https://www.youtube.com/embed/goF_3TspZ6k?autoplay=0&rel=0" 
                            title="Varner Equipment - See Our Machines In Action" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            allowfullscreen>
                        </iframe>
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
                    <a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="inline-block bg-slate-900 text-white px-10 py-5 sm:px-12 sm:py-6 rounded-3xl font-black uppercase tracking-widest text-[10px] sm:text-sm shadow-xl hover:bg-red-600 hover:text-white transition-all w-full sm:w-auto">
                        Learn more
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
