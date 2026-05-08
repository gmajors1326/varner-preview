<?php
/**
 * FacetWP SEO: Map URL segments and query params to FacetWP filters.
 * This ensures the homepage search and segment pages work seamlessly with FacetWP.
 */
add_filter( 'facetwp_preload_url_vars', function( $url_vars ) {
    // 1. Handle segment-based filtering (e.g. /inventory/tractors)
    $seg = get_query_var('inventory_segment');
    if ( $seg ) {
        $seo = varner_get_segment_seo($seg);
        if ( $seo && ! empty($seo['filter']) ) {
            foreach ( $seo['filter'] as $key => $vals ) {
                // Map DB keys to FacetWP facet names (assuming inventory_ prefix)
                $facet_name = 'inventory_' . $key;
                if ( empty($url_vars[$facet_name]) ) {
                    $url_vars[$facet_name] = (array) $vals;
                }
            }
        }
    }

    // 2. Handle manual query params (homepage search)
    if ( ! empty($_GET['fwp_inventory_search']) ) {
        $url_vars['inventory_search'] = sanitize_text_field($_GET['fwp_inventory_search']);
    }

    return $url_vars;
});

/**
 * Register Rewrite Rules

 * Handles /inventory/new, /inventory/used, etc.
 */
/**
 * Register Rewrite Rules
 * Handles /inventory/new, /inventory/used, etc.
 */
add_action( 'init', function() {
    add_rewrite_rule(
        '^inventory/(all-units|new|used|tractors|trailers|attachments|hay-equipment|misc)/?$',
        'index.php?inventory_segment=$matches[1]',
        'top'
    );
});

/**
 * SEO & Filter Mapping for Inventory Segments
 */
function varner_get_segment_seo($slug) {
    $segments = array(
        'all-units' => array(
            'title'  => 'All Inventory',
            'h1'     => 'Complete Collection',
            'sub'    => 'Western Colorado\'s most diverse selection of heavy-duty equipment.',
            'blurb'  => 'From tractors to trailers, we keep you moving with the best brands in the business.',
            'filter' => array() // No filter = all inventory
        ),
        'new' => array(
            'title'  => 'New Inventory',
            'h1'     => 'New Equipment',
            'sub'    => 'Explore the latest high-performance machines from Mahindra, Big Tex, and more.',
            'blurb'  => 'Quality you can trust, straight from the factory. Full manufacturer warranties available on all new units.',
            'filter' => array('condition' => array('New'))
        ),
        'used' => array(
            'title'  => 'Used Inventory',
            'h1'     => 'Proven Performance',
            'sub'    => 'Inspected and certified pre-owned units ready for work.',
            'blurb'  => 'Rugged equipment with years of life left, at a fraction of the cost of new.',
            'filter' => array('condition' => array('Used'))
        ),
        'tractors' => array(
            'title'  => 'Tractors',
            'h1'     => 'Heavy-Duty Tractors',
            'sub'    => 'The backbone of any operation. Power, precision, and durability.',
            'blurb'  => 'From compact utility tractors to commercial-grade powerhouses, we have the right horse for your stable.',
            'filter' => array('category' => array('Compact Tractors', 'Tractors', 'Utility Tractors'))
        ),
        'trailers' => array(
            'title'  => 'Trailers',
            'h1'     => 'Commercial Trailers',
            'sub'    => 'Haul with confidence. Built to carry the load, every time.',
            'blurb'  => 'Big Tex, Triton, and Titan – the best names in the business for hauling equipment, livestock, or cargo.',
            'filter' => array('category' => array('Commercial Trailers', 'Trailers', 'Dump Trailers', 'Flatbed Trailers', 'Utility Trailers', 'Horse Trailers', 'Livestock Trailers'))
        ),
        'attachments' => array(
            'title'  => 'Attachments',
            'h1'     => 'Attachments & Implements',
            'sub'    => 'Maximize your machine’s versatility with precision-engineered tools.',
            'blurb'  => 'Loaders, mowers, post-hole diggers, and more – get more done in less time.',
            'filter' => array('category' => array('Implements', 'Attachments', 'Loaders', 'Mowers', 'Box Blades'))
        ),
        'hay-equipment' => array(
            'title'  => 'Hay Equipment',
            'h1'     => 'Hay & Harvest',
            'sub'    => 'Precision baling and mowing technology for a perfect harvest.',
            'blurb'  => 'Krone and MacDon equipment designed for maximum yield and quality in the field.',
            'filter' => array('category' => array('Hay Equipment', 'Balers', 'Rakes', 'Tedders'))
        ),

        'misc' => array(
            'title'  => 'Miscellaneous',
            'h1'     => 'Misc. Equipment',
            'sub'    => 'Various tools, accessories, and unique inventory items.',
            'blurb'  => 'Find those specific tools and utility vehicles that round out your fleet.',
            'filter' => array('category' => array('Misc', 'Other', 'Utility Vehicles', 'Golf Carts', 'Snow Removal'))
        )
    );

    return $segments[$slug] ?? null;
}

/**
 * Template Redirect: Force segments to use the dynamic template
 */
add_filter( 'template_include', function( $template ) {
    if ( get_query_var('inventory_segment') ) {
        $listing_template = locate_template('page-equipment-listing.php');
        if ( $listing_template ) {
            return $listing_template;
        }
    }
    return $template;
}, 30);

/**
 * Load all ACF fields for a single equipment post and include the card partial.
 * Call this inside any have_posts() loop instead of repeating the get_field() block.
 */
function varner_include_equipment_card( $post_id = null ) {
    if ( ! $post_id ) $post_id = get_the_ID();
    $year            = get_field( 'year',           $post_id );
    $make            = get_field( 'make',           $post_id );
    $model           = get_field( 'model',          $post_id );
    $price           = get_field( 'price',          $post_id );
    $call_for_price  = get_field( 'call_for_price', $post_id );
    $category        = get_field( 'category',       $post_id );
    $condition       = get_field( 'condition',      $post_id );
    $stock_number    = get_field( 'stock_number',   $post_id );
    $length          = get_field( 'length',         $post_id );
    $formatted_price = $call_for_price ? 'Call For Price' : ( is_numeric( $price ) ? number_format( $price ) : (string) $price );
    $images          = varner_get_card_images( $post_id );
    include get_template_directory() . '/partials/equipment-card.php';
}

// Carousel JS — outputs once in the footer on every front-end page.
add_action( 'wp_footer', function () {
    ?>
<script>
(function () {
    function initVarnerCarousels() {
        document.querySelectorAll('.vne-carousel-wrap').forEach(function (wrap) {
            var slides = wrap.querySelectorAll('.vne-slide');
            var dots   = wrap.querySelectorAll('.vne-dot');
            var prev   = wrap.querySelector('.vne-prev');
            var next   = wrap.querySelector('.vne-next');
            if (slides.length <= 1) return;
            var cur = 0;
            function go(i) {
                slides[cur].style.opacity = '0';
                slides[cur].style.zIndex  = '1';
                if (dots[cur]) dots[cur].style.opacity = '0.4';
                cur = ((i % slides.length) + slides.length) % slides.length;
                slides[cur].style.opacity = '1';
                slides[cur].style.zIndex  = '5';
                if (dots[cur]) dots[cur].style.opacity = '1';
            }
            dots.forEach(function (dot, i) {
                dot.addEventListener('click', function () { go(i); });
            });
            if (prev) prev.addEventListener('click', function (e) { e.preventDefault(); go(cur - 1); });
            if (next) next.addEventListener('click', function (e) { e.preventDefault(); go(cur + 1); });
            // Swipe support for mobile
            var startX = 0;
            wrap.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
            wrap.addEventListener('touchend',   function (e) {
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 40) go(dx < 0 ? cur + 1 : cur - 1);
            }, { passive: true });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVarnerCarousels);
    } else {
        initVarnerCarousels();
    }
    // Re-init carousels after FacetWP AJAX reloads
    document.addEventListener('facetwp-loaded', initVarnerCarousels);
})();
</script>
    <?php
} );

/**
 * SEO DOMINANCE: JSON-LD Schema Markup
 * Injects structured data into the head for Product and LocalBusiness.
 */
add_action( 'wp_head', function() {
    // 1. GLOBAL LOCAL BUSINESS SCHEMA (Authority for the dealership)
    $local_business = array(
        "@context" => "https://schema.org",
        "@type" => "AutomotiveBusiness", // Best fit for equipment/trailer dealers
        "name" => "Varner Equipment",
        "image" => varner_get_brand_logo_url('red'),
        "@id" => home_url('/'),
        "url" => home_url('/'),
        "telephone" => "+19708740612",
        "address" => array(
            "@type" => "PostalAddress",
            "streetAddress" => "1375 US-50",
            "addressLocality" => "Delta",
            "addressRegion" => "CO",
            "postalCode" => "81416",
            "addressCountry" => "US"
        ),
        "geo" => array(
            "@type" => "GeoCoordinates",
            "latitude" => 38.7441, // Approximate for Delta US-50
            "longitude" => -108.0690
        ),
        "openingHoursSpecification" => array(
            array(
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday"),
                "opens" => "08:00",
                "closes" => "17:00"
            ),
            array(
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => "Saturday",
                "opens" => "09:00",
                "closes" => "12:00"
            )
        ),
        "sameAs" => array(
            "https://www.facebook.com/varnerequipment",
            "https://www.youtube.com/@VarnerEquipment"
        )
    );

    echo '<script type="application/ld+json">' . json_encode($local_business) . '</script>' . "\n";

    // 2. PRODUCT SCHEMA (Specific for equipment listings)
    if ( is_singular('equipment') ) {
        $post_id = get_the_ID();
        $year    = get_field('year', $post_id);
        $make    = get_field('make', $post_id);
        $model   = get_field('model', $post_id);
        $price   = get_field('price', $post_id);
        $stock   = get_field('stock_number', $post_id);
        $cond    = get_field('condition', $post_id);
        $desc    = wp_strip_all_tags(get_field('description', $post_id));
        $images  = varner_get_card_images($post_id);
        
        $title = trim("$year $make $model");
        if (!$title) $title = get_the_title();

        $product_schema = array(
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $title,
            "image" => $images,
            "description" => $desc ?: "High-quality equipment for sale at Varner Equipment in Delta, CO.",
            "sku" => $stock,
            "brand" => array(
                "@type" => "Brand",
                "name" => $make
            ),
            "offers" => array(
                "@type" => "Offer",
                "url" => get_permalink($post_id),
                "priceCurrency" => "USD",
                "price" => $price ?: "0",
                "availability" => (get_field('stock_status', $post_id) === 'Sold') ? "https://schema.org/OutOfStock" : "https://schema.org/InStock",
                "itemCondition" => ($cond === 'New') ? "https://schema.org/NewCondition" : "https://schema.org/UsedCondition",
                "seller" => array(
                    "@type" => "Organization",
                    "name" => "Varner Equipment"
                )
            )
        );

        echo '<script type="application/ld+json">' . json_encode($product_schema) . '</script>' . "\n";
    }
}, 5 );
