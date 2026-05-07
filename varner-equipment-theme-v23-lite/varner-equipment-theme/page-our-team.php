<?php 
/* Template Name: Our Team */
get_header(); 
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4">The People Behind The Machines</div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php the_title(); ?></h1>
                <div class="w-24 h-2 bg-red-600 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Team Member 1 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border-2 border-slate-100 hover:-translate-y-2 transition-transform duration-300">
                    <div class="aspect-square bg-slate-200">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-1">John Varner</h3>
                        <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-4">Owner / Founder</div>
                        <a href="mailto:contact@varnerequipment.com" class="text-xs font-bold text-slate-500 hover:text-slate-900">Contact</a>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border-2 border-slate-100 hover:-translate-y-2 transition-transform duration-300">
                    <div class="aspect-square bg-slate-200">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-1">Ashley Smith</h3>
                        <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-4">General Manager</div>
                        <a href="mailto:ashley@varnerequipment.com" class="text-xs font-bold text-slate-500 hover:text-slate-900">Contact</a>
                    </div>
                </div>

                <!-- Team Member 3 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border-2 border-slate-100 hover:-translate-y-2 transition-transform duration-300">
                    <div class="aspect-square bg-slate-200">
                        <img src="https://images.unsplash.com/photo-1531427186611-ecfd6d936c79?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-1">Mike Davis</h3>
                        <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-4">Service Manager</div>
                        <a href="mailto:service@varnerequipment.com" class="text-xs font-bold text-slate-500 hover:text-slate-900">Contact</a>
                    </div>
                </div>

                <!-- Team Member 4 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border-2 border-slate-100 hover:-translate-y-2 transition-transform duration-300">
                    <div class="aspect-square bg-slate-200">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-1">Sarah Jones</h3>
                        <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-4">Parts Department</div>
                        <a href="mailto:parts@varnerequipment.com" class="text-xs font-bold text-slate-500 hover:text-slate-900">Contact</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
