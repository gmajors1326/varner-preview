<?php
/* Template Name: About Us */
get_header();

// JSON-LD: LocalBusiness Schema for SEO
$schema = array(
    '@context'         => 'https://schema.org',
    '@type'            => 'AutoDealer',
    'name'             => 'Varner Equipment',
    'description'      => 'Family-owned tractor, trailer, and agricultural equipment dealership in Delta, Colorado. Authorized dealer for Mahindra, Big Tex, Deutz-Fahr, Krone, and more.',
    'url'              => home_url('/'),
    'telephone'        => '+19708740612',
    'address'          => array(
        '@type'           => 'PostalAddress',
        'streetAddress'   => '1375 US Highway 50',
        'addressLocality' => 'Delta',
        'addressRegion'   => 'CO',
        'postalCode'      => '81416',
        'addressCountry'  => 'US',
    ),
    'geo' => array(
        '@type'     => 'GeoCoordinates',
        'latitude'  => '38.7388',
        'longitude' => '-108.0702',
    ),
    'openingHoursSpecification' => array(
        array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array('Monday','Tuesday','Wednesday','Thursday','Friday'), 'opens' => '08:00', 'closes' => '17:00' ),
        array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '09:00', 'closes' => '12:00' ),
    ),
    'brand' => array(
        array( '@type' => 'Brand', 'name' => 'Mahindra' ),
        array( '@type' => 'Brand', 'name' => 'Big Tex' ),
        array( '@type' => 'Brand', 'name' => 'Deutz-Fahr' ),
        array( '@type' => 'Brand', 'name' => 'Krone' ),
        array( '@type' => 'Brand', 'name' => 'MacDon' ),
    ),
    'areaServed' => array(
        array( '@type' => 'City', 'name' => 'Delta, CO' ),
        array( '@type' => 'City', 'name' => 'Grand Junction, CO' ),
        array( '@type' => 'City', 'name' => 'Montrose, CO' ),
        array( '@type' => 'City', 'name' => 'Gunnison, CO' ),
        array( '@type' => 'State', 'name' => 'Western Colorado' ),
    ),
    'sameAs' => array(
        'https://www.youtube.com/@VarnerEquipment',
    ),
);
echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
?>

    <!-- HERO HEADER -->
    <section class="pt-36 pb-12 bg-slate-950 text-white relative overflow-hidden">
        <div class="absolute -right-24 -top-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-red-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Varner Equipment — Delta, Colorado</div>
            <h1 class="text-5xl md:text-7xl font-black tracking-tight uppercase mb-4 leading-[1.1]">About Us</h1>
            <p class="text-xl text-slate-400 font-bold max-w-2xl leading-relaxed">Western Colorado's most trusted family-owned tractor, trailer, and agricultural equipment dealership.</p>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-16 items-start">

                <!-- LEFT: About Copy -->
                <div class="flex-1 space-y-8">

                    <!-- Paragraph 1: Who We Are -->
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">Rooted in Western Colorado</h2>
                        <p class="text-lg text-slate-600 font-bold leading-relaxed">
                            Varner Equipment is a <strong class="text-slate-900">family-owned and operated tractor, trailer, and agricultural equipment dealership</strong> located at 1375 US Highway 50 in Delta, Colorado. Serving farmers, ranchers, contractors, and landowners across the Western Slope — including Delta, Montrose, Grand Junction, Gunnison, and the surrounding communities — we have built our reputation on one simple principle: sell quality equipment, stand behind it completely, and treat every customer like a neighbor. Because out here, they are.
                        </p>
                    </div>

                    <!-- Paragraph 2: What We Offer -->
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">Premium Brands. Full-Service Support.</h2>
                        <p class="text-lg text-slate-600 font-bold leading-relaxed">
                            We are an authorized dealer for some of the most respected names in the industry — <strong class="text-slate-900">Mahindra tractors, Big Tex trailers, Deutz-Fahr, Krone, MacDon, McHale, TYM</strong>, and many more. Whether you need a compact utility tractor for your hobby farm, a commercial flatbed trailer for your business, or precision hay equipment for a high-yield harvest, our inventory spans new and pre-owned units across every major category. And when your machine needs attention, our <strong class="text-slate-900">certified service department</strong> and fully stocked parts counter are ready to keep you running — not waiting.
                        </p>
                    </div>

                    <!-- Paragraph 3: Why Varner -->
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">More Than a Dealership</h2>
                        <p class="text-lg text-slate-600 font-bold leading-relaxed">
                            What sets Varner Equipment apart isn't just our inventory — it's the experience we deliver from the first handshake to long after the sale. Our team takes the time to understand your operation, match you with the right equipment, and connect you with <strong class="text-slate-900">flexible financing options</strong> from trusted lenders including Wells Fargo, AgDirect, DLL, and Sheffield Financial. We don't believe in high-pressure sales. We believe in honest advice, straight answers, and long-term relationships built on trust. When you buy from Varner, you're not just buying a machine — you're getting a partner in your operation.
                        </p>
                    </div>

                    <!-- CTA -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <a href="<?php echo esc_url( home_url( '/all-inventory' ) ); ?>" class="bg-red-600 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-red-700 transition-all shadow-lg text-center">
                            Browse Inventory
                        </a>
                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-slate-800 transition-all shadow-lg text-center">
                            Contact Us
                        </a>
                    </div>
                </div>

                <!-- RIGHT: Why Choose Us Card -->
                <div class="w-full lg:w-96 shrink-0 space-y-6">

                    <!-- Why Choose Us -->
                    <div class="bg-white p-8 rounded-3xl shadow-xl border border-slate-200 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-bl-full -mr-8 -mt-8 z-0"></div>
                        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-6 relative z-10">Why Choose Varner?</h3>
                        <ul class="space-y-4 relative z-10">
                            <?php
                            $reasons = array(
                                'Family Owned & Operated Since Day One',
                                'Authorized Multi-Brand Dealer',
                                'Certified Service Department',
                                'Fully Stocked OEM Parts Counter',
                                'Flexible Financing Available',
                                'New & Pre-Owned Inventory',
                                'Serving Western Colorado',
                            );
                            foreach ( $reasons as $reason ) : ?>
                            <li class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="font-bold text-slate-700 text-sm"><?php echo esc_html( $reason ); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Hours & Location -->
                    <div class="bg-slate-900 text-white p-8 rounded-3xl border border-slate-800">
                        <h3 class="text-lg font-black uppercase tracking-tight mb-6">Hours & Location</h3>
                        <ul class="space-y-3 text-sm font-bold text-slate-300">
                            <li class="flex justify-between"><span>Mon – Fri</span><span class="text-white">8:00am – 5:00pm</span></li>
                            <li class="flex justify-between"><span>Saturday</span><span class="text-white">9:00am – Noon</span></li>
                            <li class="flex justify-between"><span>Sunday</span><span class="text-red-500">Closed</span></li>
                        </ul>
                        <div class="mt-6 pt-6 border-t border-white/10">
                            <p class="text-slate-400 text-xs font-bold mb-1">1375 US Highway 50</p>
                            <p class="text-white font-black text-sm">Delta, Colorado 81416</p>
                            <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 mt-4 text-red-500 hover:text-white transition-colors text-[10px] font-black uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Get Directions
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
