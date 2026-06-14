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

// Format meter label and value to support singular/plural
$meter_label = 'Hours';
$meter_val   = '';
if ( $meter ) {
    $display_meter_type = $meter_type;
    if ( (float) $meter === 1.0 ) {
        if ( strcasecmp( $meter_type, 'hours' ) === 0 ) {
            $display_meter_type = 'Hour';
        } elseif ( strcasecmp( $meter_type, 'miles' ) === 0 ) {
            $display_meter_type = 'Mile';
        } elseif ( strcasecmp( $meter_type, 'acres' ) === 0 ) {
            $display_meter_type = 'Acre';
        }
    }
    $meter_label = $display_meter_type;
    $meter_val   = $meter . ' ' . $display_meter_type;
}

$description     = get_field( 'description',  $post_id );
$stock_status    = get_field( 'stock_status', $post_id );
$has_attachments = get_field( 'has_attachments', $post_id );
$attachment_details = get_field( 'attachment_details', $post_id );
$drive           = get_field( 'drive', $post_id );
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

$finance_url    = add_query_arg( array(
    'price' => is_numeric( $price ) ? $price : '',
    'term'  => 60,
    'apr'   => 10,
    'down'  => 10,
), home_url( '/finance' ) );
?>

<section class="pt-32 pb-24 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4">


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
                <div class="relative bg-slate-100 rounded-2xl overflow-hidden aspect-[4/3] border border-slate-200 shadow-lg mb-3 cursor-zoom-in" id="vne-detail-carousel">
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
                        <a href="<?php echo esc_url( $finance_url ); ?>" class="hover:text-red-600 transition-colors font-black">Financial Calculator</a>
                        <span class="text-slate-200">|</span>
                        <span>Payments as low as <strong class="text-slate-700">USD $<?php echo number_format( $monthly_payment, 2 ); ?>*</strong></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- E-mail Us -->
                <a href="mailto:jacob@varnerequipment.com?subject=<?php echo rawurlencode( 'Inquiry: ' . $title_text ); ?>&body=<?php echo rawurlencode( 'I am interested in Stock #' . $stock_number . '. Please contact me.' ); ?>"
                   class="flex items-center justify-center gap-3 bg-slate-900 text-white py-4 rounded-xl font-black uppercase tracking-widest text-[11px] hover:bg-red-600 transition-all shadow-md w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    E-mail Us
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
                            <a href="mailto:jacob@varnerequipment.com"
                               class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-slate-700 hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                jacob@varnerequipment.com
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Financing CTA -->
                <div class="flex flex-col gap-3">
                    <a href="<?php echo esc_url( $finance_url ); ?>"
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
                            $meter_label   => array( 'text', $meter_val ),
                            'Drive'        => array( 'text', $drive ),
                            'Attachments'  => array( 'text', $has_attachments ? ('Yes' . ($attachment_details ? ' — ' . $attachment_details : '')) : 'No' ),
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
            <p class="text-xs text-slate-500 leading-relaxed font-medium max-w-5xl">
                *Monthly payment stated above assumes a secured commercial use loan transaction available for highly qualified commercial loan applicants. Actual loan payment amount and terms may vary. Consumer financing not available for consumers residing in Nevada. Additional state restrictions may apply. Equal opportunity lender. Click here for more state licenses and disclosures. NMLS ID: 1857954. VERMONT RESIDENTS: THIS IS A LOAN SOLICITATION ONLY. CurrencyFinance IS NOT THE LENDER. INFORMATION RECEIVED WILL BE SHARED WITH ONE OR MORE THIRD PARTIES IN CONNECTION WITH YOUR LOAN INQUIRY. THE LENDER MAY NOT BE SUBJECT TO ALL VERMONT LENDING LAWS. THE LENDER MAY BE SUBJECT TO FEDERAL LENDING LAWS. CALIFORNIA RESIDENTS: Financing provided or arranged by Express Tech-Financing, LLC dba Currency pursuant to California Finance Lender License #60DBO54873.
            </p>
        </div>

    </div><!-- /max-w-7xl -->
</section>

<!-- Lightbox Modal Overlay -->
<div id="vne-lightbox" class="fixed inset-0 z-[1000] bg-slate-950/95 backdrop-blur-lg flex flex-col justify-between items-center opacity-0 pointer-events-none transition-opacity duration-300 py-6">
    <!-- Close Button -->
    <button id="lightbox-close" class="absolute top-6 right-6 text-white/70 hover:text-white hover:scale-110 active:scale-95 transition-all p-3 z-50 rounded-full bg-slate-900/60" aria-label="Close Lightbox">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- Navigation Buttons -->
    <?php if ( count( $images ) > 1 ) : ?>
    <button id="lightbox-prev" class="absolute left-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-slate-900/60 text-white/70 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center shadow-2xl z-40 border border-white/10" aria-label="Previous">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button id="lightbox-next" class="absolute right-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-slate-900/60 text-white/70 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center shadow-2xl z-40 border border-white/10" aria-label="Next">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
    <?php endif; ?>

    <!-- Empty Spacer at top to keep layout balanced -->
    <div class="h-10"></div>

    <!-- Image Wrapper -->
    <div id="lightbox-img-wrapper" class="relative max-w-5xl w-full px-12 flex-1 flex items-center justify-center">
        <img id="lightbox-img" src="" alt="Detail view" class="max-h-[75vh] max-w-full object-contain rounded-lg shadow-2xl border border-white/5 transition-all duration-300 transform scale-95 opacity-0 select-none">
    </div>

    <!-- Bottom Caption & Thumbnail Strip -->
    <div class="w-full max-w-4xl px-6 flex flex-col items-center gap-4">
        <div id="lightbox-caption" class="text-white font-black uppercase tracking-widest text-xs sm:text-sm text-center"></div>
        
        <?php if ( count( $images ) > 1 ) : ?>
        <div class="flex gap-2.5 overflow-x-auto max-w-full pb-2 px-4 justify-center" style="scrollbar-width: none;">
            <?php foreach ( $images as $idx => $img_url ) : ?>
            <button class="lightbox-thumb shrink-0 w-16 h-12 rounded-lg overflow-hidden border-2 transition-all duration-200 opacity-60 hover:opacity-100"
                    style="border-color: transparent;"
                    data-index="<?php echo $idx; ?>"
                    aria-label="Lightbox Photo <?php echo $idx + 1; ?>">
                <img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy" class="w-full h-full object-cover">
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var wrap   = document.getElementById('vne-detail-carousel');
    if (!wrap) return;
    var slides = wrap.querySelectorAll('.vne-slide');
    var thumbs = document.querySelectorAll('.vne-thumb');
    var prev   = wrap.querySelector('.vne-prev');
    var next   = wrap.querySelector('.vne-next');
    var cur = 0;

    function go(i) {
        if (slides.length <= 1) return;
        slides[cur].style.opacity = '0';
        if (thumbs[cur]) thumbs[cur].style.borderColor = 'rgb(226,232,240)';
        cur = ((i % slides.length) + slides.length) % slides.length;
        slides[cur].style.opacity = '1';
        if (thumbs[cur]) thumbs[cur].style.borderColor = 'rgb(220,38,38)';
        // Scroll thumbnail into view
        if (thumbs[cur]) thumbs[cur].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    if (slides.length > 1) {
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
    }

    // Lightbox Functionality
    var lightbox = document.getElementById('vne-lightbox');
    if (lightbox) {
        var lightboxImg = document.getElementById('lightbox-img');
        var lightboxCaption = document.getElementById('lightbox-caption');
        var lightboxClose = document.getElementById('lightbox-close');
        var lightboxPrev = document.getElementById('lightbox-prev');
        var lightboxNext = document.getElementById('lightbox-next');
        var lightboxThumbs = lightbox.querySelectorAll('.lightbox-thumb');
        
        var imagesArray = <?php echo json_encode( $images ); ?>;
        var titleText = <?php echo json_encode( $title_text ); ?>;
        
        function openLightbox(index) {
            document.body.style.overflow = 'hidden'; // Prevent page scroll
            lightbox.classList.remove('pointer-events-none', 'opacity-0');
            lightbox.classList.add('opacity-100');
            updateLightboxImage(index);
        }
        
        function closeLightbox() {
            document.body.style.overflow = '';
            lightbox.classList.add('pointer-events-none', 'opacity-0');
            lightbox.classList.remove('opacity-100');
            lightboxImg.classList.add('scale-95', 'opacity-0');
        }
        
        function updateLightboxImage(index) {
            // Fade out current image
            lightboxImg.classList.add('opacity-0', 'scale-95');
            
            setTimeout(function() {
                var targetIdx = ((index % imagesArray.length) + imagesArray.length) % imagesArray.length;
                cur = targetIdx; // sync main gallery carousel with lightbox
                
                // Sync the main desktop carousel page as well
                if (slides.length > 1) {
                    slides.forEach(function(s, sIdx) {
                        s.style.opacity = sIdx === targetIdx ? '1' : '0';
                    });
                    thumbs.forEach(function(t, tIdx) {
                        t.style.borderColor = tIdx === targetIdx ? 'rgb(220,38,38)' : 'rgb(226,232,240)';
                    });
                }
                
                lightboxImg.src = imagesArray[targetIdx];
                lightboxCaption.textContent = titleText + ' (Photo ' + (targetIdx + 1) + ' of ' + imagesArray.length + ')';
                
                // Update active thumbnail border in lightbox
                lightboxThumbs.forEach(function(th, idx) {
                    if (idx === targetIdx) {
                        th.style.borderColor = 'rgb(220,38,38)';
                        th.classList.remove('opacity-60');
                        th.classList.add('opacity-100');
                        th.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    } else {
                        th.style.borderColor = 'transparent';
                        th.classList.remove('opacity-100');
                        th.classList.add('opacity-60');
                    }
                });
                
                // Fade in new image
                lightboxImg.onload = function() {
                    lightboxImg.classList.remove('opacity-0', 'scale-95');
                };
            }, 150);
        }
        
        // Open lightbox when clicking main image carousel (but not on nav buttons)
        wrap.addEventListener('click', function(e) {
            if (e.target.closest('.vne-prev') || e.target.closest('.vne-next')) {
                return;
            }
            openLightbox(cur);
        });
        
        // Lightbox controls (stopPropagation prevents backdrop click handler from closing lightbox)
        if (lightboxClose) {
            lightboxClose.addEventListener('click', function(e) {
                e.stopPropagation();
                closeLightbox();
            });
        }
        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', function(e) {
                e.stopPropagation();
                updateLightboxImage(cur - 1);
            });
        }
        if (lightboxNext) {
            lightboxNext.addEventListener('click', function(e) {
                e.stopPropagation();
                updateLightboxImage(cur + 1);
            });
        }
        
        lightboxThumbs.forEach(function(thumb, idx) {
            thumb.addEventListener('click', function(e) {
                e.stopPropagation();
                updateLightboxImage(idx);
            });
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (lightbox.classList.contains('opacity-0')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft' && imagesArray.length > 1) updateLightboxImage(cur - 1);
            if (e.key === 'ArrowRight' && imagesArray.length > 1) updateLightboxImage(cur + 1);
        });

        // Close when clicking empty space on backdrop or image wrapper margins only
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target.id === 'lightbox-img-wrapper') {
                closeLightbox();
            }
        });
    }
})();
</script>

<?php get_footer(); ?>
