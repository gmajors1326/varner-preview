<?php
/**
 * Partial: Native Inventory Sidebar (Consolidated)
 * Replaces both facet-sidebar.php and filter-sidebar.php.
 */
$facet_search_label    = $facet_search_label    ?? 'Search Inventory';
$facet_show_condition  = $facet_show_condition  ?? true;
$uid = uniqid('vfilter_');

$reset_path = wp_unslash( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) );
$reset_url  = $reset_path ? home_url( $reset_path ) : get_permalink();

$selected_categories = array_map( 'sanitize_text_field', (array) ( $_GET['category'] ?? array() ) );
$selected_makes      = isset( $selected_makes_override )
    ? array_map( 'sanitize_text_field', (array) $selected_makes_override )
    : array_map( 'sanitize_text_field', (array) ( $_GET['make'] ?? array() ) );
$selected_condition  = array_map( 'sanitize_text_field', (array) ( $_GET['condition'] ?? array() ) );
$search_value        = sanitize_text_field( $_GET['s'] ?? '' );
$stock_number_value  = sanitize_text_field( $_GET['stock_number'] ?? '' );
$vin_value           = sanitize_text_field( $_GET['vin'] ?? '' );
$price_min_value     = sanitize_text_field( $_GET['price_min'] ?? '' );
$price_max_value     = sanitize_text_field( $_GET['price_max'] ?? '' );
$year_min_value      = sanitize_text_field( $_GET['year_min'] ?? '' );
$year_max_value      = sanitize_text_field( $_GET['year_max'] ?? '' );

$has_filters = $search_value || $stock_number_value || $vin_value || $selected_categories || $selected_makes || $selected_condition || $price_min_value || $price_max_value || $year_min_value || $year_max_value;

$year_min_bound  = isset( $filter_data['year_range']->min_year ) ? intval( $filter_data['year_range']->min_year ) : 1980;
$year_max_bound  = isset( $filter_data['year_range']->max_year ) ? intval( $filter_data['year_range']->max_year ) : intval( date('Y') );
$price_min_bound = isset( $filter_data['price_range']->min_price ) ? floatval( $filter_data['price_range']->min_price ) : 0;
$price_max_bound = isset( $filter_data['price_range']->max_price ) ? floatval( $filter_data['price_range']->max_price ) : 250000;

$fallback_categories = isset( $filter_data['categories'] ) ? $filter_data['categories'] : array();
$fallback_makes      = isset( $filter_data['makes'] ) ? $filter_data['makes'] : array();
$fallback_conditions = isset( $filter_data['conditions'] ) ? $filter_data['conditions'] : array();
?>
<aside class="w-full lg:w-72 shrink-0">
    <div class="sticky top-28 space-y-6">
        
        <!-- APPLIED FILTERS PILLS -->
        <?php if ( $has_filters ) : ?>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <span class="font-black text-[10px] uppercase tracking-widest text-slate-900">Applied Filters</span>
                <a href="<?php echo esc_url( $reset_url ); ?>" class="text-[9px] font-black uppercase tracking-widest text-red-600 hover:text-red-700">Clear All</a>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ( $selected_categories as $v ) : ?>
                    <a href="<?php echo esc_url( varner_remove_filter( 'category', $v ) ); ?>" class="bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">× <?php echo esc_html( $v ); ?></a>
                <?php endforeach; ?>
                <?php foreach ( $selected_makes as $v ) : ?>
                    <a href="<?php echo esc_url( varner_remove_filter( 'make', $v ) ); ?>" class="bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">× <?php echo esc_html( $v ); ?></a>
                <?php endforeach; ?>
                <?php foreach ( $selected_condition as $v ) : ?>
                    <a href="<?php echo esc_url( varner_remove_filter( 'condition', $v ) ); ?>" class="bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">× <?php echo esc_html( $v ); ?></a>
                <?php endforeach; ?>
                <?php if ( $search_value ) : ?>
                    <a href="<?php echo esc_url( varner_remove_filter( 's' ) ); ?>" class="bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">× "<?php echo esc_html( $search_value ); ?>"</a>
                <?php endif; ?>
                <?php if ( $year_min_value || $year_max_value ) : ?>
                    <a href="<?php echo esc_url( varner_remove_range_filter( 'year_min', 'year_max' ) ); ?>" class="bg-red-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">× Year: <?php echo esc_html( ($year_min_value ?: '...') . '-' . ($year_max_value ?: '...') ); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Mobile Toggle -->
            <button type="button" id="vne-mobile-filter-toggle" class="w-full lg:hidden flex justify-between items-center p-5 bg-slate-50 border-b border-slate-200 text-slate-900 font-black uppercase tracking-widest text-[10px]">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Search & Filter Inventory
                </span>
                <svg id="vne-mobile-filter-icon" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <!-- Filter Content -->
            <div id="vne-mobile-filter-content" class="hidden lg:block p-6 space-y-8">
                <form method="get" action="" id="varner-inventory-filter-form" class="space-y-8">
                    
                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4"><?php echo esc_html( $facet_search_label ); ?></h3>
                        <div class="space-y-2">
                            <input type="text" name="s" value="<?php echo esc_attr( $search_value ); ?>" placeholder="Keyword..." class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:border-red-500 outline-none" />
                            <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-600 transition-colors">Search</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Stock #</h3>
                            <input type="text" name="stock_number" value="<?php echo esc_attr( $stock_number_value ); ?>" placeholder="1234" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">VIN / Serial</h3>
                            <input type="text" name="vin" value="<?php echo esc_attr( $vin_value ); ?>" placeholder="VIN" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm" />
                        </div>
                    </div>

                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Category</h3>
                        <div class="space-y-2 max-h-48 overflow-auto border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                            <?php foreach ( $fallback_categories as $cat_key => $cat_obj ) : ?>
                                <label class="flex items-center justify-between gap-2 text-xs text-slate-700 cursor-pointer group">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" name="category[]" value="<?php echo esc_attr( $cat_key ); ?>" <?php checked( in_array( $cat_key, $selected_categories, true ) ); ?> class="accent-red-600" />
                                        <span class="group-hover:text-red-600 transition-colors font-bold"><?php echo esc_html( $cat_key ); ?></span>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-400">(<?php echo intval($cat_obj->cnt); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Manufacturer</h3>
                        <div class="space-y-2 max-h-48 overflow-auto border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                            <?php foreach ( $fallback_makes as $make_key => $make_obj ) : ?>
                                <label class="flex items-center justify-between gap-2 text-xs text-slate-700 cursor-pointer group">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" name="make[]" value="<?php echo esc_attr( $make_key ); ?>" <?php checked( in_array( $make_key, $selected_makes, true ) ); ?> class="accent-red-600" />
                                        <span class="group-hover:text-red-600 transition-colors font-bold uppercase"><?php echo esc_html( $make_key ); ?></span>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-400">(<?php echo intval($make_obj->cnt); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ( $facet_show_condition ) : ?>
                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Condition</h3>
                        <div class="flex gap-2">
                            <?php 
                            $conditions_to_show = array('New', 'Used');
                            foreach ( $conditions_to_show as $cond_key ) : 
                                $is_checked = in_array( $cond_key, $selected_condition, true );
                                $exists = isset($fallback_conditions[$cond_key]);
                            ?>
                                <label class="flex-1 text-center cursor-pointer group">
                                    <input type="checkbox" name="condition[]" value="<?php echo esc_attr( $cond_key ); ?>" <?php checked( $is_checked ); ?> class="hidden v-cond-input" />
                                    <div class="v-cond-btn py-2 rounded-xl border <?php echo $is_checked ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-500'; ?> text-[9px] font-black uppercase tracking-widest transition-all <?php echo !$exists ? 'opacity-50 grayscale' : ''; ?>">
                                        <?php echo esc_html( $cond_key ); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( empty($fallback_conditions) ) : ?>
                            <p class="mt-2 text-[9px] font-bold text-red-600">No condition data found in inventory.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Price Range</h3>
                            <span class="text-[9px] font-black text-red-600" id="<?php echo $uid; ?>_price_display"></span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex gap-2 items-center">
                                <input id="<?php echo $uid; ?>_price_min" type="number" name="price_min" placeholder="Min" value="<?php echo esc_attr( $price_min_value ); ?>" class="w-full border border-slate-300 rounded-xl px-2 py-1.5 text-xs" />
                                <span class="text-slate-300">-</span>
                                <input id="<?php echo $uid; ?>_price_max" type="number" name="price_max" placeholder="Max" value="<?php echo esc_attr( $price_max_value ); ?>" class="w-full border border-slate-300 rounded-xl px-2 py-1.5 text-xs" />
                            </div>
                            <div class="relative h-1 bg-slate-100 rounded-full">
                                <input id="<?php echo $uid; ?>_price_range_min" type="range" min="<?php echo esc_attr( $price_min_bound ); ?>" max="<?php echo esc_attr( $price_max_bound ); ?>" step="500" value="<?php echo esc_attr( $price_min_value !== '' ? $price_min_value : $price_min_bound ); ?>" class="absolute w-full h-1 appearance-none bg-transparent pointer-events-none accent-red-600" />
                                <input id="<?php echo $uid; ?>_price_range_max" type="range" min="<?php echo esc_attr( $price_min_bound ); ?>" max="<?php echo esc_attr( $price_max_bound ); ?>" step="500" value="<?php echo esc_attr( $price_max_value !== '' ? $price_max_value : $price_max_bound ); ?>" class="absolute w-full h-1 appearance-none bg-transparent pointer-events-none accent-red-600" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Year</h3>
                            <span class="text-[9px] font-black text-red-600" id="<?php echo $uid; ?>_year_display"></span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex gap-2 items-center">
                                <input id="<?php echo $uid; ?>_year_min" type="number" name="year_min" placeholder="Min" value="<?php echo esc_attr( $year_min_value ); ?>" class="w-full border border-slate-300 rounded-xl px-2 py-1.5 text-xs" />
                                <span class="text-slate-300">-</span>
                                <input id="<?php echo $uid; ?>_year_max" type="number" name="year_max" placeholder="Max" value="<?php echo esc_attr( $year_max_value ); ?>" class="w-full border border-slate-300 rounded-xl px-2 py-1.5 text-xs" />
                            </div>
                            <div class="relative h-1 bg-slate-100 rounded-full">
                                <input id="<?php echo $uid; ?>_year_range_min" type="range" min="<?php echo esc_attr( $year_min_bound ); ?>" max="<?php echo esc_attr( $year_max_bound ); ?>" step="1" value="<?php echo esc_attr( $year_min_value !== '' ? $year_min_value : $year_min_bound ); ?>" class="absolute w-full h-1 appearance-none bg-transparent pointer-events-none accent-red-600" />
                                <input id="<?php echo $uid; ?>_year_range_max" type="range" min="<?php echo esc_attr( $year_min_bound ); ?>" max="<?php echo esc_attr( $year_max_bound ); ?>" step="1" value="<?php echo esc_attr( $year_max_value !== '' ? $year_max_value : $year_max_bound ); ?>" class="absolute w-full h-1 appearance-none bg-transparent pointer-events-none accent-red-600" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all shadow-lg active:scale-95">Apply Filters</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</aside>

<style>
input[type='range']::-webkit-slider-thumb { pointer-events: auto; }
input[type='range']::-moz-range-thumb { pointer-events: auto; }
</style>

<script>
(function(){
  function bindDual(minId, maxId, rangeMinId, rangeMaxId, displayId, isPrice) {
    var minInput = document.getElementById(minId);
    var maxInput = document.getElementById(maxId);
    var rangeMin = document.getElementById(rangeMinId);
    var rangeMax = document.getElementById(rangeMaxId);
    var display  = document.getElementById(displayId);
    if (!minInput || !maxInput || !rangeMin || !rangeMax) return;

    function update() {
      var minVal = parseFloat(rangeMin.value);
      var maxVal = parseFloat(rangeMax.value);
      if (minVal > maxVal) { [minVal, maxVal] = [maxVal, minVal]; }
      minInput.value = minVal;
      maxInput.value = maxVal;
      if (display) {
        display.textContent = isPrice ? '$' + minVal.toLocaleString() + ' - $' + maxVal.toLocaleString() : minVal + ' - ' + maxVal;
      }
    }

    rangeMin.addEventListener('input', update);
    rangeMax.addEventListener('input', update);
    minInput.addEventListener('change', function(){ rangeMin.value = this.value; update(); });
    maxInput.addEventListener('change', function(){ rangeMax.value = this.value; update(); });
    update();
  }

  document.addEventListener('DOMContentLoaded', function(){
    bindDual('<?php echo $uid; ?>_price_min','<?php echo $uid; ?>_price_max','<?php echo $uid; ?>_price_range_min','<?php echo $uid; ?>_price_range_max', '<?php echo $uid; ?>_price_display', true);
    bindDual('<?php echo $uid; ?>_year_min','<?php echo $uid; ?>_year_max','<?php echo $uid; ?>_year_range_min','<?php echo $uid; ?>_year_range_max', '<?php echo $uid; ?>_year_display', false);

    // Condition button toggle logic
    document.querySelectorAll('.v-cond-input').forEach(input => {
      input.addEventListener('change', function() {
        const btn = this.parentElement.querySelector('.v-cond-btn');
        if (this.checked) {
          btn.classList.remove('bg-white', 'border-slate-200', 'text-slate-500');
          btn.classList.add('bg-slate-900', 'border-slate-900', 'text-white');
        } else {
          btn.classList.remove('bg-slate-900', 'border-slate-900', 'text-white');
          btn.classList.add('bg-white', 'border-slate-200', 'text-slate-500');
        }
      });
    });

    // Mobile filter accordion toggle
    var filterToggle = document.getElementById('vne-mobile-filter-toggle');
    var filterContent = document.getElementById('vne-mobile-filter-content');
    var filterIcon = document.getElementById('vne-mobile-filter-icon');
    if (filterToggle && filterContent && filterIcon) {
      filterToggle.addEventListener('click', function() {
        filterContent.classList.toggle('hidden');
        if (filterContent.classList.contains('hidden')) {
          filterIcon.style.transform = 'rotate(0deg)';
        } else {
          filterIcon.style.transform = 'rotate(180deg)';
        }
      });
    }
  });
})();
</script>