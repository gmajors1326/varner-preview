<?php 
/* Template Name: About Us */
get_header(); 
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-12">
                <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Varner Equipment</div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php the_title(); ?></h1>
                <div class="w-24 h-2 bg-red-600 mb-10"></div>
            </div>
            
            <div class="flex flex-col md:flex-row gap-16 items-start">
                <div class="w-full md:w-1/2 prose prose-lg prose-slate max-w-none font-bold text-slate-600 leading-relaxed space-y-6">
                    <?php
                    if ( have_posts() ) {
                        while ( have_posts() ) {
                            the_post();
                            the_content();
                        }
                    }
                    ?>
                    <p>Varner Equipment is a family-run dealership in Delta, Colorado, focused on keeping farmers, ranchers, and property owners productive. We pair proven brands like Big Tex, TYM, Mahindra, Krone, MacDon, and Triton with local know-how, so you get the right tractor, trailer, or implement for your land and budget. Our team listens first, recommends what truly fits, and stands behind every sale with responsive support.</p>
                    <p>We stock new and pre-owned equipment—from compact and utility tractors to hay tools, flatbed and dump trailers, and work-ready attachments. Every unit is inspected, clearly priced, and ready to work, whether you need horsepower for acreage, hauling capacity for business, or a dependable setup for the season. If you don’t see it on the lot, we’ll help source it.</p>
                    <p>Service is the heart of Varner Equipment. Our technicians handle maintenance, repairs, and parts requests quickly to keep downtime low and your operation profitable. Call, visit the yard, or browse online—when you’re ready to work, we’re here to help.</p>
                </div>
                
                <div class="w-full md:w-1/2">
                    <div class="bg-white p-8 rounded-[2rem] shadow-xl border-4 border-slate-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-bl-full -mr-16 -mt-16 z-0"></div>
                        <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter mb-6 relative z-10">Why Choose Us?</h3>
                        <ul class="space-y-4 relative z-10">
                            <li class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-950 text-white rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="font-bold text-slate-700">Family Owned & Operated</span>
                            </li>
                            <li class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-950 text-white rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="font-bold text-slate-700">Expert Service Department</span>
                            </li>
                            <li class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-950 text-white rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="font-bold text-slate-700">Extensive Parts Inventory</span>
                            </li>
                            <li class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-950 text-white rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="font-bold text-slate-700">Top-Tier Equipment Brands</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
