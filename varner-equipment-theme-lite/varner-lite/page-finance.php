<?php
/**
 * Template Name: FINANCE CALCULATOR
 * Description: In-site finance calculator for equipment payments.
 */

get_header();

$prefill_price = isset( $_GET['price'] ) ? floatval( wp_unslash( $_GET['price'] ) ) : '';
$prefill_apr   = isset( $_GET['apr'] )   ? floatval( wp_unslash( $_GET['apr'] ) )   : 9.49;
$prefill_term  = isset( $_GET['term'] )  ? intval( wp_unslash( $_GET['term'] ) )    : 60;
$prefill_down  = isset( $_GET['down'] )  ? floatval( wp_unslash( $_GET['down'] ) )  : 10; // percent

$theme_settings = get_option( 'varner_theme_settings', array() );
$defaults       = function_exists( 'varner_get_theme_settings_defaults' ) ? varner_get_theme_settings_defaults() : array();
$finance_cards  = isset( $theme_settings['finance_cards'] ) ? $theme_settings['finance_cards'] : ( $defaults['finance_cards'] ?? array() );

?>

<section id="applications">
    <div class="bg-slate-950 text-white pt-20 pb-10">
        <div class="max-w-6xl mx-auto px-4 flex flex-col gap-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between md:gap-6">
                <div class="space-y-3">
            <div class="text-[10px] font-black uppercase tracking-[0.3em] text-red-500">Apply</div>
                    <h2 class="text-4xl md:text-6xl font-black tracking-tight">FINANCIAL APPLICATIONS</h2>
                    <p class="text-slate-200 font-bold max-w-3xl">Start your application online or let our team guide you. We tailor terms to the machine, usage, and your preferred structure.</p>
                </div>
                <div class="flex-shrink-0">
                    <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full text-[11px] font-black uppercase tracking-[0.3em] hover:bg-red-700 transition-all">Shop Inventory</a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-100 text-slate-900 py-12">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <?php if ( ! empty( $finance_cards ) ) : ?>
                    <?php foreach ( $finance_cards as $card ) : ?>
                        <?php
                            $logo = $card['logo'] ?? '';
                            $pdf  = $card['application_pdf'] ?? '';
                            if ( $logo && ! preg_match( '/^https?:\/\//', $logo ) ) {
                                $logo_file = get_template_directory() . '/assets/' . $logo;
                                $logo_url  = file_exists( $logo_file )
                                    ? get_template_directory_uri() . '/assets/' . $logo
                                    : '';
                            } else {
                                $logo_url = $logo;
                            }
                            if ( $pdf && ! preg_match( '/^https?:\/\//', $pdf ) ) {
                                $pdf_file = get_template_directory() . '/assets/' . $pdf;
                                $pdf_url  = file_exists( $pdf_file )
                                    ? get_template_directory_uri() . '/assets/' . $pdf
                                    : '';
                            } else {
                                $pdf_url = $pdf;
                            }
                        ?>
                        <div class="p-5 rounded-2xl bg-white shadow-lg border border-slate-200 flex flex-col gap-4 items-center text-center">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $card['alt'] ?? $card['name'] ?? '' ); ?>" class="h-48 w-48 object-contain">
                            <?php else : ?>
                                <div class="h-48 w-48 flex items-center justify-center bg-slate-100 rounded-xl text-slate-400 text-[10px] font-black uppercase tracking-widest">No Logo</div>
                            <?php endif; ?>
                            <div class="text-base font-black text-slate-900"><?php echo esc_html( $card['name'] ?? '' ); ?></div>
                            <?php if ( $card['description'] ?? '' ) : ?>
                                <p class="text-xs text-slate-500 font-bold"><?php echo esc_html( $card['description'] ); ?></p>
                            <?php endif; ?>
                            <?php if ( $pdf_url ) : ?>
                                <a href="<?php echo esc_url( $pdf_url ); ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 text-white text-[11px] font-black uppercase tracking-[0.25em] hover:bg-red-700 transition-all" target="_blank" rel="noopener noreferrer">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-span-full text-center py-16 text-slate-400 text-[11px] font-black uppercase tracking-widest">No finance partners configured yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>


<section class="pt-16 pb-10 bg-slate-950 text-white">
    <div class="max-w-6xl mx-auto px-4 flex flex-col gap-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between md:gap-6">
            <div class="space-y-3">
                <div class="text-[10px] font-black uppercase tracking-[0.3em] text-red-500">Financing</div>
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter">FINANCE CALCULATOR</h1>
                <p class="text-slate-300 max-w-3xl font-bold">Estimate payments with flexible terms, down payment, taxes, fees, and trade-in adjustments—all on one page.</p>
            </div>
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url( home_url( '/inventory/all-units' ) ); ?>" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full text-[11px] font-black uppercase tracking-[0.3em] hover:bg-red-700 transition-all">Shop Inventory</a>
            </div>
        </div>
    </div>
</section>

<section id="finance-calculator" class="py-12 bg-slate-100">
    <div class="max-w-6xl mx-auto px-4 grid gap-10 lg:grid-cols-2">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">Inputs</div>
                    <h2 class="text-2xl font-black text-slate-900">Deal Structure</h2>
                </div>
                <button id="ve-finance-reset" class="text-[10px] font-black uppercase tracking-[0.2em] text-red-600 hover:text-red-700">Reset</button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Equipment Price</span>
                    <input id="ve-fin-price" type="number" step="0.01" min="0" value="<?php echo esc_attr( $prefill_price ); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Sales Tax %</span>
                    <input id="ve-fin-tax" type="number" step="0.01" min="0" value="0" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Down Payment %</span>
                    <input id="ve-fin-down-pct" type="number" step="0.1" min="0" value="<?php echo esc_attr( $prefill_down ); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Down Payment ($)</span>
                    <input id="ve-fin-down-amt" type="number" step="0.01" min="0" value="" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Trade-In / Net Credits ($)</span>
                    <input id="ve-fin-trade" type="number" step="0.01" min="0" value="0" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Fees (Doc, Delivery, Misc) ($)</span>
                    <input id="ve-fin-fees" type="number" step="0.01" min="0" value="0" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>APR %</span>
                    <input id="ve-fin-apr" type="number" step="0.01" min="0" value="<?php echo esc_attr( $prefill_apr ); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
                <label class="space-y-1 text-sm font-bold text-slate-700">
                    <span>Term (Months)</span>
                    <input id="ve-fin-term" type="number" step="1" min="1" value="<?php echo esc_attr( $prefill_term ); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none">
                </label>
            </div>

            <div class="text-[11px] text-slate-500 font-bold">Down payment amount and percent stay in sync as you type.</div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">Results</div>
                    <h2 class="text-2xl font-black text-slate-900">Payment Estimate</h2>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Estimates only</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Monthly Payment</div>
                    <div id="ve-fin-payment" class="text-3xl font-black text-red-600">$0</div>
                </div>
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Amount Financed</div>
                    <div id="ve-fin-financed" class="text-2xl font-black text-slate-900">$0</div>
                </div>
                <div class="p-4 rounded-xl border border-slate-100">
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Total Interest</div>
                    <div id="ve-fin-interest" class="text-xl font-black text-slate-800">$0</div>
                </div>
                <div class="p-4 rounded-xl border border-slate-100">
                    <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Taxes & Fees</div>
                    <div id="ve-fin-taxes" class="text-xl font-black text-slate-800">$0</div>
                </div>
            </div>

            <div class="bg-slate-50 rounded-xl border border-slate-100 p-4 text-sm text-slate-600 font-bold leading-relaxed">
                <p class="mb-2">This calculator provides an estimate only. Financing subject to credit approval and lender terms. Taxes are applied to the taxable amount (price minus trade-in). Down payment reduces the financed amount.</p>
                <p>For a tailored quote, contact our finance team.</p>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const priceEl = document.getElementById('ve-fin-price');
    const taxEl   = document.getElementById('ve-fin-tax');
    const downPct = document.getElementById('ve-fin-down-pct');
    const downAmt = document.getElementById('ve-fin-down-amt');
    const tradeEl = document.getElementById('ve-fin-trade');
    const feesEl  = document.getElementById('ve-fin-fees');
    const aprEl   = document.getElementById('ve-fin-apr');
    const termEl  = document.getElementById('ve-fin-term');
    const payEl   = document.getElementById('ve-fin-payment');
    const finEl   = document.getElementById('ve-fin-financed');
    const intEl   = document.getElementById('ve-fin-interest');
    const taxOut  = document.getElementById('ve-fin-taxes');
    const resetEl = document.getElementById('ve-finance-reset');

    function toNum(el) {
        const v = parseFloat(el.value);
        return isFinite(v) ? v : 0;
    }

    function format(n) {
        const num = isFinite(n) ? n : 0;
        return '$' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function syncDownFromPct() {
        const price = Math.max(0, toNum(priceEl));
        const pct   = Math.max(0, toNum(downPct));
        downAmt.value = price ? (price * pct / 100).toFixed(2) : '';
    }

    function syncDownFromAmt() {
        const price = Math.max(0, toNum(priceEl));
        const amt   = Math.max(0, toNum(downAmt));
        downPct.value = price ? ((amt / price) * 100).toFixed(2) : '';
    }

    function update() {
        const price = Math.max(0, toNum(priceEl));
        const tax   = Math.max(0, toNum(taxEl));
        const trade = Math.max(0, toNum(tradeEl));
        const fees  = Math.max(0, toNum(feesEl));
        const apr   = Math.max(0, toNum(aprEl));
        const term  = Math.max(1, Math.round(toNum(termEl)) || 1);

        const down = Math.max(0, toNum(downAmt));

        const taxableBase = Math.max(price - trade, 0);
        const taxAmount   = taxableBase * (tax / 100);
        const financed    = Math.max(taxableBase - down + fees + taxAmount, 0);

        let payment = 0;
        if (financed > 0 && term > 0) {
            if (apr > 0) {
                const r = apr / 1200;
                payment = financed * (r * Math.pow(1 + r, term)) / (Math.pow(1 + r, term) - 1);
            } else {
                payment = financed / term;
            }
        }
        const totalPaid = payment * term;
        const totalInterest = Math.max(totalPaid - financed, 0);

        payEl.textContent = format(payment);
        finEl.textContent = format(financed);
        intEl.textContent = format(totalInterest);
        taxOut.textContent = format(taxAmount + fees);
    }

    [priceEl, taxEl, tradeEl, feesEl, aprEl, termEl].forEach(function(el){ el.addEventListener('input', update); });
    downPct.addEventListener('input', function(){ syncDownFromPct(); update(); });
    downAmt.addEventListener('input', function(){ syncDownFromAmt(); update(); });

    if (resetEl) {
        resetEl.addEventListener('click', function(){
            priceEl.value = '';
            taxEl.value = '0';
            downPct.value = '<?php echo esc_js( $prefill_down ); ?>';
            downAmt.value = '';
            tradeEl.value = '0';
            feesEl.value = '0';
            aprEl.value = '<?php echo esc_js( $prefill_apr ); ?>';
            termEl.value = '<?php echo esc_js( $prefill_term ); ?>';
            update();
        });
    }

    syncDownFromPct();
    update();
})();
</script>

<?php get_footer(); ?>
