<?php
/**
 * Template Name: 404 Page Not Found
 * Description: Custom branded 404 page template for Varner Equipment.
 */
get_header();
?>

<!-- 404 HERO -->
<section class="pt-36 pb-20 bg-slate-950 text-white relative overflow-hidden min-h-[70vh] flex items-center">
    <div class="absolute -right-24 -top-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    <div class="absolute -left-24 -bottom-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10 w-full">
        <div class="inline-flex items-center gap-2 bg-red-600/20 border border-red-500/30 px-4 py-1.5 rounded-full mb-6">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-xs font-black uppercase tracking-widest text-red-400">404 Error • Page Not Found</span>
        </div>
        
        <h1 class="text-6xl md:text-8xl font-black tracking-tight uppercase mb-6 leading-none">
            Equipment <span class="text-red-600">Moved</span> Or <span class="text-red-600">Sold</span>
        </h1>
        
        <p class="text-lg md:text-xl text-slate-400 font-bold leading-relaxed mb-10 max-w-2xl mx-auto">
            The page or equipment listing you are looking for is no longer available. It may have been sold or moved to a new address.
        </p>

        <!-- SEARCH FORM -->
        <form action="<?php echo esc_url( home_url( '/inventory/all-units/' ) ); ?>" method="get" class="max-w-xl mx-auto mb-10">
            <div class="flex items-center bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-2 focus-within:border-red-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400 ml-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="s" placeholder="Search inventory (e.g. Mahindra, Big Tex, Tractor)..." class="w-full bg-transparent text-white placeholder-slate-400 px-4 py-3 text-sm font-bold focus:outline-none" required>
                <button type="submit" class="bg-red-600 text-white font-black text-xs uppercase tracking-widest px-6 py-3.5 rounded-xl hover:bg-red-700 transition-colors shrink-0">
                    Search
                </button>
            </div>
        </form>

        <!-- CATEGORY QUICK LINKS -->
        <div class="mb-10">
            <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 mb-4">Popular Categories</div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="<?php echo esc_url( home_url( '/inventory/tractors/' ) ); ?>" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-colors">
                    Tractors
                </a>
                <a href="<?php echo esc_url( home_url( '/inventory/trailers/' ) ); ?>" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-colors">
                    Trailers
                </a>
                <a href="<?php echo esc_url( home_url( '/inventory/attachments/' ) ); ?>" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-colors">
                    Attachments
                </a>
                <a href="<?php echo esc_url( home_url( '/inventory/hay-equipment/' ) ); ?>" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-colors">
                    Hay Equipment
                </a>
            </div>
        </div>

        <!-- MAIN CTAS -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?php echo esc_url( home_url( '/inventory/all-units/' ) ); ?>" class="w-full sm:w-auto bg-red-600 text-white font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl hover:bg-red-700 transition-colors text-center shadow-lg shadow-red-600/30">
                Browse Full Inventory
            </a>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="w-full sm:w-auto bg-slate-800 text-white font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl hover:bg-slate-700 border border-slate-700 transition-colors text-center">
                Contact Sales Team
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>