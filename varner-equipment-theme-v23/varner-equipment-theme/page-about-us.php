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
                <div class="w-full md:w-1/2 prose prose-lg prose-slate max-w-none font-bold text-slate-600 leading-relaxed">
                    <?php
                    if ( have_posts() ) :
                        while ( have_posts() ) : the_post();
                            the_content();
                        endwhile;
                    endif;
                    ?>
                    <!-- Placeholder Content if page is empty -->
                    <?php if (empty(get_the_content())): ?>
                        <p>Varner Equipment is a family owned and operated tractor and trailer dealership located in Delta, Colorado. We are your one-stop shop for high-performance equipment that you can rely on to get the job done right.</p>
                        <p>Since we opened our doors, we've committed ourselves to offering the best products from top brands like Mahindra, Big Tex Trailers, Deutz-Fahr, and more. But we don't just sell equipment; we back it up with a dedicated service team and a fully stocked parts department to keep you running season after season.</p>
                        <p>Whether you're managing a large agricultural operation, running a commercial fleet, or simply maintaining your property, the Varner family and our knowledgeable staff are here to ensure you get exactly what you need.</p>
                    <?php endif; ?>
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
