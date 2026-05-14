<?php
/**
 * Template Name: Contact
 */

get_header(); ?>

<section class="pt-32 pb-24 bg-slate-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4">
        <!-- HEADER -->
        <div class="mb-16 text-center">
            <h1 class="text-6xl font-black text-slate-900 tracking-tighter uppercase mb-4">Contact Us</h1>
            <div class="w-32 h-2 bg-red-600 mx-auto mb-6"></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-sm">We're here to help your operation succeed</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            <!-- CONTACT INFO SIDEBAR -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-slate-950 p-8 md:p-12 rounded-[2.5rem] text-white shadow-2xl border-b-4 border-red-600">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-red-500 mb-10">Main Office</h3>
                    
                    <div class="space-y-8">
                        <div class="flex gap-4">
                            <div class="bg-slate-800 p-3 rounded-xl h-fit">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Visit Us</p>
                                <p class="font-black text-lg leading-tight uppercase">1375 US-50<br>Delta, CO 81416</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="bg-slate-800 p-3 rounded-xl h-fit">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Call Us</p>
                                <a href="tel:9708740612" class="font-black text-2xl hover:text-red-500 transition-colors tracking-tighter">(970) 874-0612</a>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="bg-slate-800 p-3 rounded-xl h-fit">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Email Us</p>
                                <p class="font-black text-lg break-all uppercase tracking-tight">sales@varner equipment.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HOURS -->
                <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 mb-6 border-b border-slate-100 pb-4">Business Hours</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500">Mon - Fri</span>
                            <span class="text-sm font-black text-slate-900 uppercase">8am - 5pm</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500">Saturday</span>
                            <span class="text-sm font-black text-slate-900 uppercase">9am - Noon</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500">Sunday</span>
                            <span class="text-sm font-black text-red-600 uppercase">Closed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTACT FORM -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="p-8 md:p-12">
                        <?php if ( isset($_GET['request']) && $_GET['request'] === 'sent' ) : ?>
                            <div class="bg-green-50 border-2 border-green-100 p-8 rounded-3xl mb-12 text-center animate-in fade-in zoom-in duration-500">
                                <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <h3 class="text-xl font-black text-green-950 uppercase mb-2">Message Received</h3>
                                <p class="text-green-700 font-bold">Thank you for reaching out. Our team will contact you shortly.</p>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" class="space-y-8">
                            <input type="hidden" name="action" value="varner_contact_form_submit">
                            <?php wp_nonce_field( 'varner_contact_form_submit', 'varner_contact_nonce' ); ?>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Full Name <span class="text-red-600">(*)</span></label>
                                    <input type="text" name="full_name" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Email Address <span class="text-red-600">(*)</span></label>
                                    <input type="email" name="email" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Phone Number</label>
                                <input type="tel" name="phone" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Message <span class="text-red-600">(*)</span></label>
                                <textarea name="message" required rows="6" placeholder="How can we help you today?" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-6 py-5 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm"></textarea>
                            </div>

                            <!-- CAPTCHA -->
                            <div class="bg-slate-50 rounded-2xl p-6 border-2 border-slate-100 flex flex-col md:flex-row items-center gap-6">
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm min-w-[140px] text-center">
                                    <?php 
                                        if ( ! session_id() ) { session_start(); }
                                        $num1 = rand(1, 10);
                                        $num2 = rand(1, 10);
                                        $_SESSION['varner_contact_captcha'] = $num1 + $num2;
                                    ?>
                                    <span class="text-xl font-black text-slate-900 tracking-widest"><?php echo $num1; ?> + <?php echo $num2; ?> = ?</span>
                                </div>
                                <div class="flex-1 w-full">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 block">Security Sum <span class="text-red-600">(*)</span></label>
                                    <input type="number" name="captcha_answer" required placeholder="Result" class="w-full bg-white border-2 border-slate-200 rounded-xl px-5 py-3 font-black text-slate-900 focus:border-red-500 outline-none transition-all shadow-sm">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-red-600 text-white px-12 py-6 rounded-2xl font-black text-lg uppercase tracking-[0.2em] shadow-2xl shadow-red-900/40 hover:bg-red-700 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center justify-center gap-4">
                                Send Message
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
