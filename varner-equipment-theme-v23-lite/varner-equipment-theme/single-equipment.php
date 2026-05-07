<?php
/**
 * Single Equipment Unit — Detail Page
 */
get_header();

$post_id         = get_the_ID();
$year            = get_field( 'year',         $post_id );
$make            = get_field( 'make',         $post_id );
$model           = get_field( 'model',        $post_id );
$price           = get_field( 'price',        $post_id );
$call_for_price  = get_field( 'call_for_price', $post_id );
$category        = get_field( 'category',     $post_id );
$condition       = get_field( 'condition',    $post_id );
$stock_number    = get_field( 'stock_number', $post_id );
$vin             = get_field( 'vin',          $post_id );
$length          = get_field( 'length',       $post_id );
$color           = get_field( 'color',        $post_id );
$meter           = get_field( 'meter',        $post_id );
$meter_type      = get_field( 'meter_type',   $post_id ) ?: 'Hours';
$description     = get_field( 'description',  $post_id );
$stock_status    = get_field( 'stock_status', $post_id );
$images          = varner_get_card_images( $post_id );

$formatted_price = $call_for_price ? 'Call For Price' : (is_numeric( $price ) ? number_format( $price ) : (string) $price);
$title_text      = trim( "$year $make $model" ) ?: get_the_title();

// Monthly payment — 10% APR, 60 months
$monthly_payment = '';
if ( ! $call_for_price && is_numeric( $price ) && $price > 0 ) {
    $r               = 0.10 / 12;
    $n               = 60;
    $monthly_payment = $price * ( $r * pow( 1 + $r, $n ) ) / ( pow( 1 + $r, $n ) - 1 );
}
?>

<section class="pt-32 pb-24 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Breadcrumb -->
        <nav class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2 flex-wrap">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-red-600 transition-colors">Home</a>
            <span>›</span>
            <a href="<?php echo esc_url( home_url( '/inventory' ) ); ?>" class="hover:text-red-600 transition-colors">Inventory</a>
            <?php if ( $category ) : ?>
            <span>›</span>
            <span class="text-slate-600"><?php echo esc_html( $category ); ?></span>
            <?php endif; ?>
            <?php if ( $make ) : ?>
            <span>›</span>
            <span class="text-slate-600"><?php echo esc_html( $make ); ?></span>
            <?php endif; ?>
            <span>›</span>
            <span class="text-slate-900"><?php echo esc_html( $title_text ); ?></span>
        </nav>

        <!-- Back link -->
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-red-600 transition-colors mb-10">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Search Results
        </a>

        <!-- ── MAIN TWO-COLUMN LAYOUT ─────────────────────────────── -->
        <div class="flex flex-col lg:flex-row gap-10 items-start">

            <!-- LEFT: Image Gallery -->
            <div class="w-full lg:w-[520px] shrink-0">

                <!-- Main image carousel -->
                <div class="relative bg-slate-100 rounded-2xl overflow-hidden aspect-[4/3] border border-slate-200 shadow-lg mb-3" id="vne-detail-carousel">
                    <?php foreach ( $images as $i => $img_url ) : ?>
                    <img src="<?php echo esc_url( $img_url ); ?>"
                         alt="<?php echo esc_attr( $title_text ); ?>"
                         loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
                         class="vne-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
                         style="opacity:<?php echo $i === 0 ? '1' : '0'; ?>">
                    <?php endforeach; ?>

                    <?php if ( count( $images ) > 1 ) : ?>
                    <button class="vne-prev absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/75 transition-all z-10 shadow-lg" aria-label="Previous">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="vne-next absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/75 transition-all z-10 shadow-lg" aria-label="Next">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <?php endif; ?>

                    <?php if ( $condition ) : ?>
                    <span class="absolute top-3 left-3 bg-slate-950/80 text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-md z-10">
                        <?php echo esc_html( $condition ); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Photos count -->
                <p class="text-center text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">
                    Photos (<?php echo count( $images ); ?>)
                </p>

                <!-- Thumbnail strip -->
                <?php if ( count( $images ) > 1 ) : ?>
                <div class="flex gap-2 overflow-x-auto pb-1" style="scrollbar-width:none;">
                    <?php foreach ( $images as $i => $img_url ) : ?>
                    <button class="vne-thumb shrink-0 w-20 h-16 rounded-xl overflow-hidden border-2 transition-all duration-200"
                            style="border-color:<?php echo $i === 0 ? 'rgb(220,38,38)' : 'rgb(226,232,240)'; ?>"
                            data-index="<?php echo $i; ?>"
                            aria-label="Photo <?php echo $i + 1; ?>">
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy"
                             class="w-full h-full object-cover">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Details panel -->
            <div class="flex-1 min-w-0 space-y-6">

                <!-- Brand + Title + Category -->
                <div>
                    <?php if ( $make ) : ?>
                    <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1"><?php echo esc_html( strtoupper( $make ) ); ?></div>
                    <?php endif; ?>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase leading-tight">
                        <?php echo esc_html( $title_text ); ?>
                    </h1>
                    <?php if ( $category ) : ?>
                    <p class="text-red-600 text-[11px] font-black uppercase tracking-widest mt-1"><?php echo esc_html( $category ); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="border-t border-slate-200 pt-5">
                    <div class="flex items-baseline gap-2">
                        <?php if ( strpos($formatted_price, 'Call') === false ) : ?>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">USD</span>
                        <span class="text-4xl font-black text-red-600 tracking-tight">$<?php echo esc_html( $formatted_price ); ?></span>
                        <?php else : ?>
                        <span class="text-4xl font-black text-red-600 tracking-tight"><?php echo esc_html( $formatted_price ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $monthly_payment && strpos($formatted_price, 'Call') === false ) : ?>
                    <div class="flex items-center gap-2 mt-2 text-[12px] text-slate-500 font-bold flex-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 shrink-0"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="hover:text-red-600 transition-colors font-black">Financial Calculator</a>
                        <span class="text-slate-200">|</span>
                        <span>Payments as low as <strong class="text-slate-700">USD $<?php echo number_format( $monthly_payment, 2 ); ?>*</strong></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Email Seller -->
                <a href="mailto:contact@varnerequipment.com?subject=<?php echo rawurlencode( 'Inquiry: ' . $title_text ); ?>&body=<?php echo rawurlencode( 'I am interested in Stock #' . $stock_number . '. Please contact me.' ); ?>"
                   class="flex items-center justify-center gap-3 bg-slate-900 text-white py-4 rounded-xl font-black uppercase tracking-widest text-[11px] hover:bg-red-600 transition-all shadow-md w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Email Seller
                </a>

                <!-- Machine Location -->
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600 shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <div class="text-sm">
                        <span class="font-black text-slate-900 text-[11px] uppercase tracking-widest">Machine Location: </span>
                        <span class="text-slate-600 font-bold">1375 Highway 50, Delta, Colorado 81416</span>
                        <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener"
                           class="inline-flex ml-1 text-slate-400 hover:text-red-600 transition-colors align-middle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Seller Information -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Seller Information</h3>
                    <div class="flex flex-col sm:flex-row gap-4 justify-between">
                        <div>
                            <p class="font-black text-slate-900 text-sm">Varner Equipment</p>
                            <p class="text-[11px] text-slate-500 font-bold mt-0.5">Delta, Colorado 81416</p>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <a href="tel:9708740612"
                               class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-slate-700 hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.72a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                (970) 874-0612
                            </a>
                            <a href="mailto:contact@varnerequipment.com"
                               class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-slate-700 hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                contact@varnerequipment.com
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Financing CTA -->
                <div class="flex flex-col gap-3">
                    <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"
                       class="flex items-center justify-center gap-2 bg-slate-800 text-white py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        *Apply for Financing
                    </a>
                </div>

            </div><!-- /right col -->
        </div><!-- /two-col -->

        <!-- ── GENERAL SPECS TABLE ────────────────────────────────── -->
        <div class="mt-16">
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">General</h2>
            <div class="w-16 h-1.5 bg-red-600 mb-6 rounded-full"></div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full">
                    <tbody class="divide-y divide-slate-100">
                        <?php
                        $specs = array(
                            'Year'         => array( 'text', $year ),
                            'Manufacturer' => array( 'text', $make ),
                            'Model'        => array( 'text', $model ),
                            'Stock Number' => array( 'text', $stock_number ),
                            'VIN / Serial' => array( 'text', $vin ),
                            'Condition'    => array( 'text', $condition ),
                            'Stock Status' => array( 'text', $stock_status ),
                            'Color'        => array( 'text', $color ),
                            'Length'       => array( 'text', $length ),
                            'Meter'        => array( 'text', $meter ? $meter . ' ' . $meter_type : '' ),
                            'Description'  => array( 'html', $description ),
                        );
                        foreach ( $specs as $label => $spec ) :
                            list( $type, $value ) = $spec;
                            if ( ! $value ) continue;
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4 w-44 text-[11px] font-black uppercase tracking-widest text-slate-400 align-top bg-slate-50/60 border-r border-slate-100"><?php echo esc_html( $label ); ?></td>
                            <td class="px-8 py-4 text-sm font-bold text-slate-700">
                                <?php if ( $type === 'html' ) : ?>
                                <div class="prose prose-sm max-w-none"><?php echo wp_kses_post( $value ); ?></div>
                                <?php else : ?>
                                <?php echo nl2br( esc_html( $value ) ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── FINANCING DISCLAIMER ──────────────────────────────── -->
        <div class="mt-10 border-t border-slate-200 pt-8">
            <p class="text-[10px] text-slate-400 leading-relaxed font-medium max-w-5xl">
                *Monthly payment stated above assumes a secured commercial use loan transaction available for highly qualified commercial loan applicants. Actual loan payment amount and terms may vary. Consumer financing not available for consumers residing in Nevada. Additional state restrictions may apply. Equal opportunity lender. Click here for more state licenses and disclosures. NMLS ID: 1857954. VERMONT RESIDENTS: THIS IS A LOAN SOLICITATION ONLY. CurrencyFinance IS NOT THE LENDER. INFORMATION RECEIVED WILL BE SHARED WITH ONE OR MORE THIRD PARTIES IN CONNECTION WITH YOUR LOAN INQUIRY. THE LENDER MAY NOT BE SUBJECT TO ALL VERMONT LENDING LAWS. THE LENDER MAY BE SUBJECT TO FEDERAL LENDING LAWS. CALIFORNIA RESIDENTS: Financing provided or arranged by Express Tech-Financing, LLC dba Currency pursuant to California Finance Lender License #60DBO54873.
            </p>
        </div>

    </div><!-- /max-w-7xl -->
</section>

<script>
(function () {
    var wrap   = document.getElementById('vne-detail-carousel');
    if (!wrap) return;
    var slides = wrap.querySelectorAll('.vne-slide');
    var thumbs = document.querySelectorAll('.vne-thumb');
    var prev   = wrap.querySelector('.vne-prev');
    var next   = wrap.querySelector('.vne-next');
    if (slides.length <= 1) return;
    var cur = 0;

    function go(i) {
        slides[cur].style.opacity = '0';
        if (thumbs[cur]) thumbs[cur].style.borderColor = 'rgb(226,232,240)';
        cur = ((i % slides.length) + slides.length) % slides.length;
        slides[cur].style.opacity = '1';
        if (thumbs[cur]) thumbs[cur].style.borderColor = 'rgb(220,38,38)';
        // Scroll thumbnail into view
        if (thumbs[cur]) thumbs[cur].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    thumbs.forEach(function (t, i) { t.addEventListener('click', function () { go(i); }); });
    if (prev) prev.addEventListener('click', function () { go(cur - 1); });
    if (next) next.addEventListener('click', function () { go(cur + 1); });

    // Swipe support
    var startX = 0;
    wrap.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    wrap.addEventListener('touchend',   function (e) {
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) go(dx < 0 ? cur + 1 : cur - 1);
    }, { passive: true });
})();
</script>

<?php get_footer(); ?>
