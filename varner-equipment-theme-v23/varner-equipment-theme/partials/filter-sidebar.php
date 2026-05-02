<?php
/**
 * Inventory filter sidebar partial.
 * Expected variable: $filter_data — from varner_get_filter_data()
 */

$fs       = sanitize_text_field( $_GET['s']            ?? '' );
$f_cats   = array_map( 'sanitize_text_field', (array) ( $_GET['category']  ?? [] ) );
$f_mks    = array_map( 'sanitize_text_field', (array) ( $_GET['make']      ?? [] ) );
$f_cds    = array_map( 'sanitize_text_field', (array) ( $_GET['condition'] ?? [] ) );
$f_ymin   = intval( $_GET['year_min']    ?? 0 );
$f_ymax   = intval( $_GET['year_max']    ?? 0 );
$f_pmin   = intval( $_GET['price_min']   ?? 0 );
$f_pmax   = intval( $_GET['price_max']   ?? 0 );
$f_stock  = sanitize_text_field( $_GET['stock_number'] ?? '' );
$f_vin    = sanitize_text_field( $_GET['vin']          ?? '' );

$has = $fs || $f_cats || $f_mks || $f_cds || $f_ymin || $f_ymax || $f_pmin || $f_pmax || $f_stock || $f_vin;
$page_url = get_permalink();

$makes_top  = array_slice( $filter_data['makes'],  0, 5, true );
$makes_more = array_slice( $filter_data['makes'],  5, null, true );
$has_checked_in_more = ! empty( array_intersect( $f_mks, array_keys( $makes_more ) ) );
?>

<div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm sticky top-28">
<form method="GET" action="<?php echo esc_url( $page_url ); ?>" id="varner-filter-form">

    <!-- ── APPLIED FILTERS ──────────────────────────────────── -->
    <?php if ( $has ) : ?>
    <div class="p-4 border-b border-slate-100">
        <div class="flex justify-between items-center mb-3">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Applied Filters</span>
            <a href="<?php echo esc_url( $page_url ); ?>" class="text-[10px] font-black uppercase tracking-widest text-red-600 hover:text-red-700">Clear All</a>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach ( $f_cats as $v ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 'category', $v ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × <?php echo esc_html( $v ); ?>
            </a>
            <?php endforeach; ?>
            <?php foreach ( $f_mks as $v ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 'make', $v ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × <?php echo esc_html( $v ); ?>
            </a>
            <?php endforeach; ?>
            <?php foreach ( $f_cds as $v ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 'condition', $v ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × <?php echo esc_html( $v ); ?>
            </a>
            <?php endforeach; ?>
            <?php if ( $fs ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 's' ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × "<?php echo esc_html( $fs ); ?>"
            </a>
            <?php endif; ?>
            <?php if ( $f_stock ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 'stock_number' ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × Stock #<?php echo esc_html( $f_stock ); ?>
            </a>
            <?php endif; ?>
            <?php if ( $f_vin ) : ?>
            <a href="<?php echo esc_url( varner_remove_filter( 'vin' ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × VIN: <?php echo esc_html( $f_vin ); ?>
            </a>
            <?php endif; ?>
            <?php if ( $f_ymin || $f_ymax ) : ?>
            <a href="<?php echo esc_url( varner_remove_range_filter( 'year_min', 'year_max' ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × Year <?php echo esc_html( ( $f_ymin ?: '…' ) . '–' . ( $f_ymax ?: '…' ) ); ?>
            </a>
            <?php endif; ?>
            <?php if ( $f_pmin || $f_pmax ) : ?>
            <a href="<?php echo esc_url( varner_remove_range_filter( 'price_min', 'price_max' ) ); ?>"
               class="flex items-center gap-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full hover:bg-red-700 transition-colors">
                × $<?php echo esc_html( ( $f_pmin ? number_format( $f_pmin ) : '0' ) . '–' . ( $f_pmax ? '$' . number_format( $f_pmax ) : '…' ) ); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── QUICK SEARCH ──────────────────────────────────────── -->
    <details open class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Quick Search</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">—</span>
        </summary>
        <div class="px-4 pb-4">
            <div class="flex gap-2">
                <input type="text" name="s" value="<?php echo esc_attr( $fs ); ?>" placeholder="Enter keyword(s)"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition-colors min-w-0">
                <button type="submit"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">
                    Search
                </button>
            </div>
        </div>
    </details>

    <!-- ── CATEGORY ──────────────────────────────────────────── -->
    <?php if ( ! empty( $filter_data['categories'] ) ) : ?>
    <details open class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Category</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">—</span>
        </summary>
        <div class="px-4 pb-4 space-y-2.5">
            <?php foreach ( $filter_data['categories'] as $key => $obj ) : ?>
            <label class="flex items-center justify-between gap-2 cursor-pointer group">
                <span class="flex items-center gap-2.5">
                    <input type="checkbox" name="category[]" value="<?php echo esc_attr( $key ); ?>"
                           <?php checked( in_array( $key, $f_cats, true ) ); ?>
                           class="vf-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                    <span class="text-sm font-bold text-slate-700 group-hover:text-slate-900 leading-tight"><?php echo esc_html( $key ); ?></span>
                </span>
                <span class="text-[10px] text-slate-400 font-bold shrink-0">(<?php echo intval( $obj->cnt ); ?>)</span>
            </label>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>

    <!-- ── MANUFACTURER ──────────────────────────────────────── -->
    <?php if ( ! empty( $filter_data['makes'] ) ) : ?>
    <details open class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Manufacturer</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">—</span>
        </summary>
        <div class="px-4 pb-4">
            <?php if ( ! empty( $makes_top ) ) : ?>
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-3">Popular</p>
            <div class="space-y-2.5">
                <?php foreach ( $makes_top as $key => $obj ) : ?>
                <label class="flex items-center justify-between gap-2 cursor-pointer group">
                    <span class="flex items-center gap-2.5">
                        <input type="checkbox" name="make[]" value="<?php echo esc_attr( $key ); ?>"
                               <?php checked( in_array( $key, $f_mks, true ) ); ?>
                               class="vf-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                        <span class="text-sm font-bold text-slate-700 group-hover:text-slate-900 uppercase leading-tight"><?php echo esc_html( $key ); ?></span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold shrink-0">(<?php echo intval( $obj->cnt ); ?>)</span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $makes_more ) ) : ?>
            <div id="vf-makes-more" class="space-y-2.5 mt-2.5 <?php echo $has_checked_in_more ? '' : 'hidden'; ?>">
                <?php foreach ( $makes_more as $key => $obj ) : ?>
                <label class="flex items-center justify-between gap-2 cursor-pointer group">
                    <span class="flex items-center gap-2.5">
                        <input type="checkbox" name="make[]" value="<?php echo esc_attr( $key ); ?>"
                               <?php checked( in_array( $key, $f_mks, true ) ); ?>
                               class="vf-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                        <span class="text-sm font-bold text-slate-700 group-hover:text-slate-900 uppercase leading-tight"><?php echo esc_html( $key ); ?></span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold shrink-0">(<?php echo intval( $obj->cnt ); ?>)</span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="button" id="vf-makes-toggle"
                    class="mt-3 w-full flex items-center justify-center gap-2 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest px-4 py-2.5 rounded-lg hover:bg-red-600 transition-colors">
                <?php echo $has_checked_in_more ? '− Show Less' : '+ Show All'; ?>
            </button>
            <?php endif; ?>
        </div>
    </details>
    <?php endif; ?>

    <!-- ── YEAR ──────────────────────────────────────────────── -->
    <details open class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Year</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">—</span>
        </summary>
        <div class="px-4 pb-4">
            <div class="flex items-center gap-2">
                <input type="number" name="year_min" value="<?php echo $f_ymin ?: ''; ?>" placeholder="Min"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                <span class="text-slate-400 font-bold shrink-0 text-sm">–</span>
                <input type="number" name="year_max" value="<?php echo $f_ymax ?: ''; ?>" placeholder="Max"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                <button type="submit"
                        class="bg-slate-900 text-white px-3 py-2.5 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">
                    Go
                </button>
            </div>
        </div>
    </details>

    <!-- ── PRICE ─────────────────────────────────────────────── -->
    <details open class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Price</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">—</span>
        </summary>
        <div class="px-4 pb-4">
            <div class="flex items-center gap-2">
                <input type="number" name="price_min" value="<?php echo $f_pmin ?: ''; ?>" placeholder="$Min"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                <span class="text-slate-400 font-bold shrink-0 text-sm">–</span>
                <input type="number" name="price_max" value="<?php echo $f_pmax ?: ''; ?>" placeholder="$Max"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 w-0 min-w-0">
                <button type="submit"
                        class="bg-slate-900 text-white px-3 py-2.5 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">
                    Go
                </button>
            </div>
        </div>
    </details>

    <!-- ── STOCK NUMBER ─────────────────────────────────────── -->
    <details class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Stock Number</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm"><?php echo $f_stock ? '—' : '›'; ?></span>
        </summary>
        <div class="px-4 pb-4">
            <div class="flex gap-2">
                <input type="text" name="stock_number" value="<?php echo esc_attr( $f_stock ); ?>" placeholder="e.g. VE-1042"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition-colors min-w-0">
                <button type="submit"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">
                    Go
                </button>
            </div>
        </div>
    </details>

    <!-- ── VIN / SERIAL ──────────────────────────────────────── -->
    <details class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">VIN / Serial</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm"><?php echo $f_vin ? '—' : '›'; ?></span>
        </summary>
        <div class="px-4 pb-4">
            <div class="flex gap-2">
                <input type="text" name="vin" value="<?php echo esc_attr( $f_vin ); ?>" placeholder="Full or partial VIN"
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition-colors min-w-0">
                <button type="submit"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-colors shrink-0">
                    Go
                </button>
            </div>
        </div>
    </details>

    <!-- ── CONDITION ─────────────────────────────────────────── -->
    <?php if ( ! empty( $filter_data['conditions'] ) ) : ?>
    <details class="border-b border-slate-100">
        <summary class="vf-summary flex justify-between items-center p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition-colors">
            <span class="font-black text-xs uppercase tracking-widest text-slate-900">Condition</span>
            <span class="vf-toggle text-slate-400 font-bold text-sm">›</span>
        </summary>
        <div class="px-4 pb-4 space-y-2.5">
            <?php foreach ( $filter_data['conditions'] as $key => $obj ) : ?>
            <label class="flex items-center justify-between gap-2 cursor-pointer group">
                <span class="flex items-center gap-2.5">
                    <input type="checkbox" name="condition[]" value="<?php echo esc_attr( $key ); ?>"
                           <?php checked( in_array( $key, $f_cds, true ) ); ?>
                           class="vf-cb w-4 h-4 rounded border-slate-300 accent-red-600 cursor-pointer shrink-0">
                    <span class="text-sm font-bold text-slate-700 group-hover:text-slate-900 leading-tight"><?php echo esc_html( $key ); ?></span>
                </span>
                <span class="text-[10px] text-slate-400 font-bold shrink-0">(<?php echo intval( $obj->cnt ); ?>)</span>
            </label>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>

</form>
</div>

<script>
(function () {
    // Auto-submit on checkbox change
    document.querySelectorAll('.vf-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            document.getElementById('varner-filter-form').submit();
        });
    });

    // Show All / Show Less for manufacturers
    var moreDiv   = document.getElementById('vf-makes-more');
    var toggleBtn = document.getElementById('vf-makes-toggle');
    if (moreDiv && toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var showing = !moreDiv.classList.contains('hidden');
            moreDiv.classList.toggle('hidden', showing);
            this.textContent = showing ? '+ Show All' : '− Show Less';
        });
    }

    // Update <details> toggle icon on open/close
    document.querySelectorAll('details').forEach(function (d) {
        d.addEventListener('toggle', function () {
            var icon = this.querySelector('.vf-toggle');
            if (icon) icon.textContent = this.open ? '—' : '›';
        });
    });
})();
</script>