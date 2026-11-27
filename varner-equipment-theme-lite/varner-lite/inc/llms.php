<?php
/**
 * Varner Equipment — /llms.txt and /llms-full.txt Plain-Text Endpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render /llms.txt or /llms-full.txt as UTF-8 plain text.
 * Honors static override files in ABSPATH if present.
 */
function varner_serve_llms_txt( string $variant = 'summary' ): void {
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'Cache-Control: public, max-age=3600' );

    $filename    = ( $variant === 'full' ) ? 'llms-full.txt' : 'llms.txt';
    $static_path = ABSPATH . $filename;
    if ( file_exists( $static_path ) && is_readable( $static_path ) ) {
        readfile( $static_path );
        exit;
    }

    if ( $variant === 'full' ) {
        echo varner_get_llms_full_content();
    } else {
        echo varner_get_llms_summary_content();
    }
    exit;
}

function varner_get_llms_summary_content(): string {
    $home = untrailingslashit( home_url() );
    return <<<TXT
# Varner Equipment

> Varner Equipment is a family-owned farm equipment, tractor, and trailer dealership in Delta, Colorado, serving Montrose, Grand Junction, and Colorado's Western Slope.

Varner Equipment sells new and used tractors, trailers, hay equipment, implements, and attachments from trusted brands including Mahindra, TYM, Deutz-Fahr, Big Tex, Triton, CM Truck Beds, Krone, MacDon, and McHale. Contact the dealership for equipment service, replacement parts, and financing options. Financing is subject to credit approval and lender terms.

Location: 1375 US-50, Delta, CO 81416, USA
Phone: 970-874-0612
Hours: Monday-Friday 8 AM-5 PM, Saturday 9 AM-12 PM Mountain Time (Closed Sunday); contact the dealership for holiday hours.

Equipment and trailer categories vary by inventory. Consult individual listings for live specifications, pricing, and availability, and confirm details with the dealership before purchase.

## Official Resources

- [Varner Equipment]({$home}/): Dealership homepage and overview.
- [All Equipment & Trailer Inventory]({$home}/inventory/all-units/): Live new and used inventory, specifications, and advertised prices.
- [Trailers for Sale]({$home}/inventory/trailers/): All utility, dump, gooseneck, flatbed, enclosed cargo, car hauler, livestock, tilt, and snowmobile trailers.
- [Dump Trailers]({$home}/inventory/dump-trailers/): Heavy-duty hydraulic dump and gooseneck dump trailers.
- [Utility Trailers]({$home}/inventory/utility-trailers/): Single-axle, tandem-axle, and landscape utility trailers.
- [Tractors for Sale]({$home}/inventory/tractors/): Compact, utility, and agricultural tractors.
- [Hay & Harvest Equipment]({$home}/inventory/hay-equipment/): Mowers, rakes, balers, and forage equipment.
- [Tractor Attachments & Implements]({$home}/inventory/attachments/): Loaders, cutters, blades, and implements.
- [Shop by Brand]({$home}/brands/): Authorized manufacturer lineup and brand inventory pages.
- [Equipment Service]({$home}/services/service-request): Service requests and maintenance scheduling.
- [Parts Request]({$home}/services/parts-request): OEM tractor, trailer, and implement parts inquiries.
- [Financing]({$home}/finance): Equipment and trailer financing information, applications, and payment calculator.
- [Contact Varner Equipment]({$home}/contact): Directions, department inquiries, and dealership hours.
- [Varner Parts](https://varnerparts.com/): Online agricultural equipment and tractor replacement parts store.

## Extended Guide

- [Full Business & Equipment Guide]({$home}/llms-full.txt): Detailed overview of sales categories, brands, service, parts, and financing.
TXT . "\n";
}

function varner_get_llms_full_content(): string {
    $home = untrailingslashit( home_url() );
    return <<<TXT
# Varner Equipment - Business and Equipment Guide

## Business Overview

Varner Equipment is a family-owned agricultural equipment, tractor, and trailer dealership based in Delta, Colorado, serving Delta, Montrose, Olathe, Cedaredge, Hotchkiss, Paonia, Grand Junction, and Colorado's Western Slope. Customers include farmers, ranchers, contractors, and landowners.

## Contact Information

Business: Varner Equipment
Address: 1375 US-50, Delta, CO 81416, USA
Telephone: 970-874-0612
Business Hours: Monday-Friday 8 AM-5 PM, Saturday 9 AM-12 PM Mountain Time (Closed Sunday); contact the dealership for holiday hours.
Website: {$home}/
Online Parts Store: https://varnerparts.com/

## Equipment and Trailer Sales

Varner Equipment offers new and used tractors, trailers, hay and forage machinery, implements, and attachments. Authorized and stocked brands include Mahindra, TYM, Deutz-Fahr, Big Tex, Triton, CM Truck Beds, Krone, MacDon, and McHale. Product selection varies with live inventory.

Trailer categories include dump trailers, gooseneck dump trailers, utility trailers, bumper pull trailers, gooseneck trailers, flatbed / tag trailers, cargo / enclosed trailers, car hauler trailers, livestock trailers, pipe trailers, tilt trailers, and snowmobile trailers. A category in this guide does not confirm that a particular trailer configuration is currently in stock.

Consult each individual equipment or trailer listing for its year, make, model, stock number, configuration, specifications, dimensions, GVWR, advertised price, and availability. Confirm fit, suitability, and out-the-door details with the dealership before purchase.

## Parts Department

Contact the dealership for replacement parts and agricultural or trailer components. Its online parts store is https://varnerparts.com/, and custom parts inquiries can be submitted at {$home}/services/parts-request. Part availability and compatibility depend on the individual part and machine.

## Equipment Service

Contact Varner Equipment at {$home}/services/service-request or 970-874-0612 for factory-trained tractor, hay equipment, and trailer maintenance and repair, supported makes and models, service availability, and scheduling in Delta, Colorado.

## Financing

Contact the dealership or visit {$home}/finance for current tractor, trailer, and equipment financing options (including AgDirect, Sheffield Financial, DLL Finance, and Wells Fargo). Financing is subject to credit approval and lender terms. Any payment calculator provides estimates rather than an offer or credit approval.

## Current Inventory

Use the official inventory pages below and the XML inventory sitemap ({$home}/sitemap-inventory.xml) for current model numbers, stock numbers, specifications, advertised prices, and availability. Inventory and pricing change frequently; confirm details with the dealership.

## Official Resources

- [Varner Equipment Homepage]({$home}/)
- [All Equipment and Trailer Inventory]({$home}/inventory/all-units/)
- [All Trailers for Sale]({$home}/inventory/trailers/)
- [Dump Trailers for Sale]({$home}/inventory/dump-trailers/)
- [Utility Trailers for Sale]({$home}/inventory/utility-trailers/)
- [Big Tex Trailers Inventory]({$home}/inventory/trailers/big-tex/)
- [Triton Trailers Inventory]({$home}/inventory/trailers/triton/)
- [Tractors for Sale]({$home}/inventory/tractors/)
- [Mahindra Tractors Inventory]({$home}/inventory/tractors/mahindra/)
- [Hay & Harvest Equipment]({$home}/inventory/hay-equipment/)
- [Tractor Attachments & Implements]({$home}/inventory/attachments/)
- [Shop by Brand]({$home}/brands/)
- [Service Requests]({$home}/services/service-request)
- [Parts Requests]({$home}/services/parts-request)
- [Financing Information & Applications]({$home}/finance)
- [Contact & Directions]({$home}/contact)
- [Online Replacement Parts Store](https://varnerparts.com/)
TXT . "\n";
}
