<?php
/**
 * Template Name: Service Request
 */

get_header(); ?>

<section class="pt-32 pb-24 bg-slate-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4">
        <!-- HEADER -->
        <div class="mb-16 text-center">
            <h1 class="text-6xl font-black text-slate-900 tracking-tighter uppercase mb-4">Service Request</h1>
            <div class="w-32 h-2 bg-red-600 mx-auto mb-6"></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-sm">Professional Equipment Maintenance & Repair</p>
            <p class="mt-6 text-slate-600 max-w-2xl mx-auto font-medium leading-relaxed">
                Keep your operation running at peak performance. Please fill out the form below with your equipment details and desired service window. Our certified technicians will review your request and contact you shortly to confirm your appointment and discuss your maintenance needs.
            </p>
        </div>

        <!-- FORM CARD -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-slate-950 p-8 text-white flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-red-600 p-3 rounded-2xl shadow-lg shadow-red-900/50">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">Service Department</h2>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em]">Authorized Service Center • Delta, CO</p>
                    </div>
                </div>
                <div class="hidden sm:block text-right">
                    <p class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Direct Line</p>
                    <a href="tel:9708740612" class="text-xl font-black text-red-500 hover:text-red-400 transition-colors tracking-tighter">(970) 874-0612</a>
                </div>
            </div>

            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" class="p-8 md:p-12 space-y-12">
                <input type="hidden" name="action" value="varner_service_request_submit">
                <?php wp_nonce_field( 'varner_service_request_submit', 'varner_service_nonce' ); ?>

                <!-- SECTION 1: CONTACT INFO -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="text-red-600 font-black text-xl">01</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Customer Contact Information</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">First Name <span class="text-red-600">(*)</span></label>
                            <input type="text" name="first_name" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Last Name <span class="text-red-600">(*)</span></label>
                            <input type="text" name="last_name" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Email Address <span class="text-red-600">(*)</span></label>
                            <input type="email" name="email" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Phone Number</label>
                            <input type="tel" name="phone" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Physical Address</label>
                            <input type="text" name="address" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:col-span-2">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">City</label>
                                <input type="text" name="city" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">State</label>
                                <select name="state" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm appearance-none cursor-pointer">
                                    <option value="CO">Colorado</option>
                                    <option value="UT">Utah</option>
                                    <option value="WY">Wyoming</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="ID">Idaho</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="KS">Kansas</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="TX">Texas</option>
                                    <option value="CA">California</option>
                                    <option value="NV">Nevada</option>
                                    <option value="OR">Oregon</option>
                                    <option value="WA">Washington</option>
                                    <option value="MT">Montana</option>
                                    <!-- Add more states if needed, but centering on the West for Varner -->
                                </select>
                            </div>
                            <div class="space-y-2 col-span-2 md:col-span-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Zip Code</label>
                                <input type="text" name="zip" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: EQUIPMENT IDENTITY -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="text-red-600 font-black text-xl">02</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Equipment Being Serviced</h3>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                        <div class="space-y-2 col-span-2 md:col-span-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Make <span class="text-red-600">(*)</span></label>
                            <input type="text" name="make" required placeholder="e.g. Mahindra" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2 col-span-2 md:col-span-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Model <span class="text-red-600">(*)</span></label>
                            <input type="text" name="model" required placeholder="e.g. 2638 HST" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Year <span class="text-red-600">(*)</span></label>
                            <input type="text" name="year" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                        <div class="space-y-2 col-span-2 md:col-span-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Serial Number #</label>
                            <input type="text" name="serial" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm uppercase">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Hours / Meter</label>
                            <input type="text" name="hours" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: DESCRIBE SERVICE NEEDS -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="text-red-600 font-black text-xl">03</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Describe Service Needs</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Service(s) Needed <span class="text-red-600">(*)</span></label>
                            <textarea name="services_needed" required rows="6" placeholder="Please describe the issues or services required in detail..." class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-6 py-5 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Preferred Appointment Date <span class="text-red-600">(*)</span></label>
                            <input type="date" name="appointment_date" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm cursor-pointer">
                            <p class="text-[9px] font-bold text-slate-400 mt-2 italic">* Our team will contact you to confirm availability.</p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: PRIOR SERVICE HISTORY -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="text-red-600 font-black text-xl">04</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Prior Service History</h3>
                    </div>
                    <div class="bg-slate-50 rounded-3xl p-8 border-2 border-slate-100 space-y-8">
                        <div class="flex flex-col md:flex-row md:items-center gap-6">
                            <p class="text-sm font-black text-slate-700 uppercase tracking-tight">Have we Serviced your Equipment Before?</p>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="prior_service" value="Yes" class="w-6 h-6 border-2 border-slate-300 text-red-600 focus:ring-red-500 rounded-lg">
                                    <span class="text-sm font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-900 transition-colors">Yes</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="prior_service" value="No" class="w-6 h-6 border-2 border-slate-300 text-red-600 focus:ring-red-500 rounded-lg">
                                    <span class="text-sm font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-900 transition-colors">No</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-200/50">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Approximate Date of Last Service</label>
                                <input type="text" name="last_service_date" placeholder="Month / Year" class="w-full bg-white border-2 border-slate-200 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 outline-none transition-all shadow-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Work Previously Done</label>
                                <input type="text" name="last_service_work" class="w-full bg-white border-2 border-slate-200 rounded-xl px-5 py-4 font-bold text-slate-900 focus:border-red-500 outline-none transition-all shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CAPTCHA SECTION -->
                <div class="space-y-6 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-red-600 font-black text-xl">05</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">Security Verification</h3>
                    </div>
                    <div class="bg-slate-900 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center gap-8 border-b-4 border-red-600 shadow-xl">
                        <div class="flex items-center gap-4">
                            <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                                <?php 
                                    $num1 = rand(1, 10);
                                    $num2 = rand(1, 10);
                                    $_SESSION['varner_captcha'] = $num1 + $num2;
                                ?>
                                <span class="text-2xl font-black text-white tracking-widest"><?php echo $num1; ?> + <?php echo $num2; ?> = ?</span>
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block ml-1">What is the sum? <span class="text-red-500">(*)</span></label>
                            <input type="number" name="captcha_answer" required placeholder="Type answer here..." class="w-full bg-slate-800 border-2 border-slate-700 rounded-xl px-6 py-4 font-black text-white focus:border-red-500 focus:bg-slate-950 outline-none transition-all shadow-inner">
                        </div>
                        <div class="hidden md:block">
                            <svg class="w-12 h-12 text-slate-700" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.47 4.34-3.1 8.16-7 9.3V12h-7V6.39l7-3.11v8.71z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-8 text-center sm:text-right">
                    <button type="submit" class="w-full sm:w-auto bg-red-600 text-white px-12 py-6 rounded-2xl font-black text-lg uppercase tracking-[0.2em] shadow-2xl shadow-red-900/40 hover:bg-red-700 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center justify-center gap-4">
                        Submit Service Request
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6">Secure Submission • Varner Equipment Internal Use Only</p>
                </div>
            </form>
        </div>
    </div>
</section>

<?php get_footer(); ?>
