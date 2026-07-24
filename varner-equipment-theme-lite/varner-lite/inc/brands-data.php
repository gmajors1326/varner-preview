<?php
/**
 * Brand registry for the silent /brands/ hub and /brands/[slug]/ landing pages.
 * Location: wp-content/themes/varner-equipment-theme-v23-lite-4/inc/brands-data.php
 * Include from functions.php:  require_once get_template_directory() . '/inc/brands-data.php';
 *
 * SOURCE: the 23 manufacturers with live inventory (3+ units) pulled from the production
 * inventory Manufacturer facet. `make` values are copied EXACTLY as the facet reports them
 * (case- and spacing-sensitive) so the equipment meta_query matches. If a units-in-stock
 * count reads 0 on a brand page, the `make` string here doesn't match the stored ACF value —
 * open a real unit of that brand and compare.
 *
 * FLAGS:
 *  - "Deutz Fahr" and "Macdon" are stored WITHOUT the hyphen / with lowercase d. Slugs stay
 *    pretty ("deutz-fahr"); the `make` filter uses the stored spelling.
 *  - "Valley" category is a best guess — confirm what Valley units actually are and adjust.
 *  - Optional logos: /assets/brands/<slug>.png. Missing logo falls back to a text badge.
 *  - Long-tail brands (1-2 units) were intentionally excluded to avoid thin-content pages.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * @return array<string,array> keyed by brand slug, ordered by inventory depth
 */
function varner_get_brands() {
    return array(

        'big-tex' => array(
            'name' => 'Big Tex', 'make' => 'Big Tex', 'category' => 'Trailers',
            'tagline' => 'America\'s most trusted name in professional-grade trailers.',
            'description' => 'Big Tex builds utility, dump, gooseneck, and equipment trailers engineered to work hard and last. Varner Equipment stocks Big Tex deep for ranchers, contractors, and haulers across Delta, Montrose, and Grand Junction.',
            'keywords' => 'Big Tex trailers Delta Colorado, Big Tex dealer western Colorado, dump trailers Montrose CO, utility trailers Delta CO',
            'logo' => 'big-tex.png',
        ),
        'worksaver' => array(
            'name' => 'Worksaver', 'make' => 'Worksaver', 'category' => 'Attachments',
            'tagline' => 'Work-ready skid steer and 3-point attachments.',
            'description' => 'Worksaver builds dependable post hole diggers, bale spears, blades, and grapples that bolt on and go. A shop favorite for Western Colorado operators who need implements built to earn their keep.',
            'keywords' => 'Worksaver attachments Colorado, post hole digger Delta CO, bale spear western Colorado, tractor attachments Delta County',
            'logo' => 'worksaver.png',
        ),
        'enorossi' => array(
            'name' => 'Enorossi', 'make' => 'Enorossi', 'category' => 'Hay Equipment',
            'tagline' => 'Italian-built hay tools for efficient forage work.',
            'description' => 'Enorossi specializes in rakes, tedders, mowers, and balers engineered for clean, efficient forage. A strong value for the small and mid-size hay operations across Delta County.',
            'keywords' => 'Enorossi hay equipment Colorado, hay rake Delta CO, tedder western Colorado, forage equipment Delta County',
            'logo' => 'enorossi.png',
        ),
        'john-deere' => array(
            'name' => 'John Deere', 'make' => 'John Deere', 'category' => 'Tractors',
            'tagline' => 'The benchmark for tractor and equipment reliability.',
            'description' => 'John Deere sets the standard for durability and resale value in tractors and farm equipment. Varner Equipment carries quality used John Deere units, inspected and ready for work on the Western Slope.',
            'keywords' => 'used John Deere Colorado, John Deere tractor Delta CO, used tractors western Colorado',
            'logo' => 'john-deere.png',
        ),
        'danuser' => array(
            'name' => 'Danuser', 'make' => 'Danuser', 'category' => 'Attachments',
            'tagline' => 'Rugged post hole diggers and augers, built for a century.',
            'description' => 'Danuser has built heavy-duty post hole diggers, augers, and hydraulic drivers for over 100 years. Built for the fence-builders, ranchers, and contractors who put attachments through real abuse.',
            'keywords' => 'Danuser auger Colorado, post hole digger Delta CO, post driver western Colorado',
            'logo' => 'danuser.png',
        ),
        'tar-river' => array(
            'name' => 'Tar River', 'make' => 'Tar River', 'category' => 'Hay Equipment',
            'tagline' => 'Affordable, dependable hay and farm implements.',
            'description' => 'Tar River delivers value-priced disc mowers, rakes, tillers, and spreaders for the small farm. A go-to for acreage growers who want quality tools without the premium price.',
            'keywords' => 'Tar River equipment Colorado, disc mower Delta CO, hay implements western Colorado',
            'logo' => 'tar-river.png',
        ),
        'triton' => array(
            'name' => 'Triton', 'make' => 'Triton', 'category' => 'Trailers',
            'tagline' => 'Aluminum and steel trailers engineered to last.',
            'description' => 'Triton builds corrosion-resistant aluminum and heavy-duty steel trailers for equipment, toys, and cargo. A great pick for Western Colorado owners who want a lighter, longer-lasting trailer.',
            'keywords' => 'Triton trailers Colorado, aluminum trailer Delta CO, Triton dealer western Colorado',
            'logo' => 'triton.png',
        ),
        'krone' => array(
            'name' => 'Krone', 'make' => 'Krone', 'category' => 'Hay Equipment',
            'tagline' => 'German hay tools trusted by serious forage operations.',
            'description' => 'Krone is a global leader in mowers, rakes, tedders, and balers built for high-volume, high-quality forage. For alfalfa and grass growers across the Western Slope, Krone means cleaner cuts and faster fieldwork.',
            'keywords' => 'Krone equipment dealer Colorado, Krone hay tools Delta, Krone mower rake western Colorado',
            'logo' => 'krone.png',
        ),
        'macdon' => array(
            'name' => 'Macdon', 'make' => 'Macdon', 'category' => 'Hay Equipment',
            'tagline' => 'Premium windrowers and headers for the hay professional.',
            'description' => 'Macdon sets the standard for self-propelled windrowers and mower-conditioners, prized for cut quality and reliability. A go-to for commercial hay operations in Delta County.',
            'keywords' => 'Macdon hay equipment Colorado, Macdon windrower Delta CO, Macdon dealer western Colorado',
            'logo' => 'macdon.png',
        ),
        'mahindra' => array(
            'name' => 'Mahindra', 'make' => 'Mahindra', 'category' => 'Tractors',
            'tagline' => 'The world\'s highest-selling tractor brand by volume.',
            'description' => 'Mahindra builds rugged compact and utility tractors backed by an industry-leading warranty and Varner\'s in-house service. A dependable, high-value choice for Delta County ranch and orchard work.',
            'keywords' => 'Mahindra tractors Delta CO, Mahindra dealer Colorado, compact tractor western Colorado',
            'logo' => 'mahindra.png',
        ),
        'bison' => array(
            'name' => 'Bison', 'make' => 'Bison', 'category' => 'Attachments',
            'tagline' => 'A full line of value-priced 3-point attachments.',
            'description' => 'Bison manufactures box blades, post hole diggers, mowers, and more for the everyday tractor. Affordable, work-ready implements for the small farm and acreage owner.',
            'keywords' => 'Bison attachments Colorado, box blade Delta CO, 3 point implements western Colorado',
            'logo' => 'bison.png',
        ),
        'tym' => array(
            'name' => 'TYM', 'make' => 'TYM', 'category' => 'Tractors',
            'tagline' => 'Premium features at a working price.',
            'description' => 'TYM packs cab comforts, strong hydraulics, and clean diesel power into an approachable price point. A smart fit for acreage owners around Delta and Montrose who want more tractor for the money.',
            'keywords' => 'TYM tractors Colorado, TYM dealer Delta CO, compact tractor western Colorado',
            'logo' => 'tym.png',
        ),
        'agknx' => array(
            'name' => 'AGKNX', 'make' => 'AGKNX', 'category' => 'Attachments',
            'tagline' => 'Value-priced diggers, augers, and tractor implements.',
            'description' => 'AGKNX builds affordable post hole diggers, augers, and implements for everyday farm and ranch work. Dependable tools that get the job done without breaking the budget.',
            'keywords' => 'AGKNX attachments Colorado, post hole digger Delta CO, auger western Colorado',
            'logo' => 'agknx.png',
        ),
        'kit-containers' => array(
            'name' => 'Kit Containers', 'make' => 'Kit Containers', 'category' => 'Storage Containers',
            'tagline' => 'Secure, weatherproof storage for farm and jobsite.',
            'description' => 'Kit Containers offers durable storage and shipping container solutions for the farm, ranch, and jobsite. Secure, weatherproof storage available through Varner Equipment in Delta, CO.',
            'keywords' => 'storage containers Delta CO, shipping container western Colorado, farm storage Delta County',
            'logo' => 'kit-containers.png',
        ),
        'brush-chief' => array(
            'name' => 'Brush Chief', 'make' => 'Brush Chief', 'category' => 'Attachments',
            'tagline' => 'Heavy-duty rotary cutters and brush mowers.',
            'description' => 'Brush Chief builds tough rotary cutters and brush mowers for clearing pasture, fence lines, and rough ground. Built for the demanding conditions of Western Colorado.',
            'keywords' => 'Brush Chief rotary cutter Colorado, brush mower Delta CO, pasture mower western Colorado',
            'logo' => 'brush-chief.png',
        ),
        'titan-mfg' => array(
            'name' => 'Titan MFG', 'make' => 'Titan MFG', 'category' => 'Attachments',
            'tagline' => 'Affordable trailers, attachments, and accessories.',
            'description' => 'Titan MFG produces a wide range of value-priced trailers, attachments, and equipment accessories. A practical source for the tools and add-ons that keep your operation moving.',
            'keywords' => 'Titan attachments Colorado, tractor accessories Delta CO, equipment attachments western Colorado',
            'logo' => 'titan-mfg.png',
        ),
        'deutz-fahr' => array(
            'name' => 'Deutz Fahr', 'make' => 'Deutz Fahr', 'category' => 'Tractors',
            'tagline' => 'German-engineered high-horsepower tractors.',
            'description' => 'Deutz Fahr brings German engineering and high-horsepower muscle to large-scale farming. Efficient diesels, refined cabs, and heavy-duty transmissions built for long days in Western Colorado fields.',
            'keywords' => 'Deutz Fahr tractors Colorado, high horsepower tractor Delta CO, Deutz Fahr dealer western Slope',
            'logo' => 'deutz-fahr.png',
        ),
        'rc-trailers' => array(
            'name' => 'RC Trailers', 'make' => 'RC Trailers', 'category' => 'Trailers',
            'tagline' => 'Quality enclosed cargo and utility trailers.',
            'description' => 'RC Trailers builds dependable enclosed cargo and utility trailers for hauling and secure transport. A solid choice for contractors and haulers across the Western Slope.',
            'keywords' => 'RC Trailers Colorado, enclosed cargo trailer Delta CO, utility trailer western Colorado',
            'logo' => 'rc-trailers.png',
        ),
        'mk-martin' => array(
            'name' => 'MK Martin', 'make' => 'MK Martin', 'category' => 'Hay & Snow Equipment',
            'tagline' => 'Snowblowers, spreaders, and hay tools built tough.',
            'description' => 'MK Martin manufactures snowblowers, manure spreaders, and hay equipment engineered for tough conditions. Built to handle Western Colorado winters and year-round farm work.',
            'keywords' => 'MK Martin snowblower Colorado, manure spreader Delta CO, snow equipment western Colorado',
            'logo' => 'mk-martin.png',
        ),
        'legend' => array(
            'name' => 'Legend', 'make' => 'Legend', 'category' => 'Trailers',
            'tagline' => 'Premium aluminum enclosed and open trailers.',
            'description' => 'Legend builds premium aluminum trailers known for durability and clean design. A great option for owners who want a lightweight, corrosion-resistant haul.',
            'keywords' => 'Legend trailers Colorado, aluminum enclosed trailer Delta CO, Legend dealer western Colorado',
            'logo' => 'legend.png',
        ),
        'ford' => array(
            'name' => 'Ford', 'make' => 'Ford', 'category' => 'Tractors',
            'tagline' => 'A timeless workhorse on farms across the country.',
            'description' => 'Ford tractors remain a dependable staple for farms and acreages everywhere. Varner Equipment carries quality used Ford units, inspected and ready for another season on the Western Slope.',
            'keywords' => 'used Ford tractor Colorado, Ford tractor Delta CO, used tractors western Colorado',
            'logo' => 'ford.png',
        ),
        'valley' => array(
            'name' => 'Valley', 'make' => 'Valley', 'category' => 'Trailers',
            'tagline' => 'Dependable trailers and farm equipment.',
            'description' => 'Valley delivers reliable trailers and equipment built for everyday agricultural use. Dependable hauling and work tools for Delta County operators.',
            'keywords' => 'Valley trailers Colorado, farm equipment Delta CO, trailer dealer western Colorado',
            'logo' => 'valley.png',
        ),
        'new-holland' => array(
            'name' => 'New Holland', 'make' => 'New Holland', 'category' => 'Tractors',
            'tagline' => 'A trusted name in tractors and hay equipment.',
            'description' => 'New Holland is respected worldwide for its tractors and hay tools. Varner Equipment stocks quality used New Holland units ready for work across Western Colorado.',
            'keywords' => 'used New Holland Colorado, New Holland tractor Delta CO, used hay equipment western Colorado',
            'logo' => 'new-holland.png',
        ),
        'brillion' => array(
            'name' => 'Brillion', 'make' => 'Brillion', 'category' => 'Tillage Equipment',
            'tagline' => 'Seeders and tillage tools that build better seedbeds.',
            'description' => 'Brillion is renowned for cultipackers, seeders, and tillage tools that create ideal seedbeds. A proven choice for growers establishing pasture and forage across the Western Slope.',
            'keywords' => 'Brillion seeder Colorado, cultipacker Delta CO, tillage equipment western Colorado',
            'logo' => 'brillion.png',
        ),

    );
}

/**
 * @param string $slug
 * @return array|null
 */
function varner_get_brand( $slug ) {
    $brands = varner_get_brands();
    return isset( $brands[ $slug ] ) ? $brands[ $slug ] : null;
}
