<?php
/**
 * Equipment inventory card partial.
 *
 * Expected variables (set by the caller before get_template_part / include):
 *   $post_id, $year, $make, $model, $category, $condition,
 *   $price, $formatted_price, $stock_number, $length,
 *   $images   — array of full image URLs (at least one entry)
 *
 * Monthly payment: 10 % APR, 60 months
 */

$monthly_payment = '';
if ( is_numeric( $price ) && $price > 0 ) {
    $r               = 0.10 / 12;
    $n               = 60;
    $monthly_payment = $price * ( $r * pow( 1 + $r, $n ) ) / ( pow( 1 + $r, $n ) - 1 );
}

// Final check for Call For Price
if ( ! strpos($formatted_price, 'Call') && get_field('call_for_price', $post_id) ) {
    $formatted_price = 'Call For Price';
}

$permalink  = get_permalink( $post_id );
$title_text = trim( "$year $make $model" ) ?: 'View Unit';
$uid        = 'vne-' . $post_id;
?>

<div class="vne-card bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-md flex flex-col hover:shadow-xl hover:border-red-200 transition-all duration-300">

    <!-- ── IMAGE CAROUSEL ─────────────────────────────────── -->
    <div class="vne-carousel-wrap relative group/carousel" id="<?php echo esc_attr( $uid ); ?>">
        <div class="aspect-[16/11] relative overflow-hidden bg-slate-100">
            <?php foreach ( $images as $i => $img_url ) : ?>
            <img src="<?php echo esc_url( $img_url ); ?>"
                 alt="<?php echo esc_attr( $title_text ); ?>"
                 loading="lazy"
                 class="vne-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
                 style="opacity:<?php echo $i === 0 ? '1' : '0'; ?>">
            <?php endforeach; ?>

            <!-- Condition badge -->
            <?php if ( $condition ) : ?>
            <span class="absolute top-3 left-3 bg-slate-950/80 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md z-10">
                <?php echo esc_html( $condition ); ?>
            </span>
            <?php endif; ?>


            <?php if ( count( $images ) > 1 ) : ?>
            <!-- Prev arrow -->
            <button class="vne-prev absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-200 z-10 hover:bg-black/70"
                    aria-label="Previous image">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <!-- Next arrow -->
            <button class="vne-next absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-200 z-10 hover:bg-black/70"
                    aria-label="Next image">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <?php endif; ?>
        </div>

        <!-- Carousel dots -->
        <?php if ( count( $images ) > 1 ) : ?>
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
            <?php foreach ( $images as $i => $img_url ) : ?>
            <button class="vne-dot w-2.5 h-2.5 rounded-full border-2 border-white shadow transition-all"
                    style="opacity:<?php echo $i === 0 ? '1' : '0.4'; ?>; background:white;"
                    aria-label="Image <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── CARD BODY ──────────────────────────────────────── -->
    <div class="p-5 flex-1 flex flex-col gap-3">

        <!-- Brand / Manufacturer + Title + Category -->
        <div>
            <?php if ( $make ) : ?>
            <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-0.5"><?php echo esc_html( strtoupper( $make ) ); ?></div>
            <?php endif; ?>
            <h3 class="font-black text-slate-900 text-[15px] leading-snug"><?php echo esc_html( $title_text ); ?></h3>
            <div class="text-red-600 text-[10px] font-black uppercase tracking-widest mt-0.5"><?php echo esc_html( $category ); ?></div>
        </div>

        <!-- Price -->
        <div class="border-t border-slate-100 pt-3">
            <div class="text-xl font-black text-slate-900 tracking-tight">
                <?php if ( strpos($formatted_price, 'Call') !== false ) : ?>
                    <?php echo esc_html( $formatted_price ); ?>
                <?php else : ?>
                    USD $<?php echo esc_html( $formatted_price ); ?>
                <?php endif; ?>
            </div>
            <?php if ( $monthly_payment && strpos($formatted_price, 'Call') === false ) : ?>
            <div class="flex items-center gap-1 text-[11px] text-slate-500 font-medium mt-0.5">
                Payments as low as USD $<?php echo number_format( $monthly_payment, 2 ); ?>*
                <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="text-red-500 hover:text-red-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Financing button -->
        <div class="flex gap-2">
            <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"
               class="flex-1 text-center text-[9px] font-black uppercase tracking-wide border-2 border-slate-700 text-slate-700 py-2 px-1 rounded-lg hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-all leading-tight">
                *Apply for<br>Financing
            </a>
        </div>

        <!-- View Details -->
        <a href="<?php echo esc_url( $permalink ); ?>"
           class="flex items-center justify-center gap-2 bg-red-600 text-white py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-700 transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            View Details
        </a>

        <!-- Specs -->
        <div class="border-t border-slate-100 pt-3 space-y-2 text-xs text-slate-700">
            <?php if ( $stock_number ) : ?>
            <div class="flex gap-2">
                <span class="text-slate-400 font-bold w-24 shrink-0">Stock Number:</span>
                <span class="font-bold"><?php echo esc_html( $stock_number ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $length ) : ?>
            <div class="flex gap-2">
                <span class="text-slate-400 font-bold w-24 shrink-0">Length:</span>
                <span class="font-bold"><?php echo esc_html( $length ); ?></span>
            </div>
            <?php endif; ?>

            <!-- Expandable location -->
            <details class="group">
                <summary class="flex items-center gap-2 cursor-pointer list-none select-none">
                    <span class="text-slate-400 font-bold w-24 shrink-0">Location:</span>
                    <span class="font-bold">Delta, Colorado</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-open:rotate-180 transition-transform ml-auto shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="mt-2 pl-1 text-slate-500 font-medium leading-relaxed">
                    Varner Equipment<br>
                    1375 Hwy 50<br>
                    Delta, CO 81416
                </div>
            </details>
        </div>

        <!-- Contact buttons -->
        <div class="border-t border-slate-100 pt-3 flex gap-2">
            <a href="mailto:contact@varnerequipment.com"
               class="flex-1 flex items-center justify-center gap-1.5 text-[9px] font-black uppercase tracking-wide border border-slate-200 text-slate-600 py-2.5 rounded-lg hover:bg-slate-50 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Seller
            </a>
            <a href="tel:9708740612"
               class="flex-1 flex items-center justify-center gap-1.5 text-[9px] font-black uppercase tracking-wide border border-slate-200 text-slate-600 py-2.5 rounded-lg hover:bg-slate-50 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.72a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                (970) 874-0612
            </a>
        </div>
        <div class="text-center text-[9px] text-slate-400 font-bold tracking-wide">Seller: Varner Equipment</div>

    </div><!-- /.card body -->
</div><!-- /.vne-card -->
