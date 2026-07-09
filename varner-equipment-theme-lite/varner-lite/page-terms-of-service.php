<?php 
/* Template Name: Terms of Service */
get_header(); 
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-4xl mx-auto px-4">
            <div class="mb-12">
                <div class="text-red-600 font-black text-xs uppercase tracking-[0.4em] mb-4">Varner Equipment</div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php echo esc_html( get_the_title() ?: 'Terms of Service' ); ?></h1>
                <div class="w-24 h-2 bg-red-600 mb-10"></div>
                <p class="text-slate-500 font-bold text-sm uppercase tracking-widest">Last updated: <?php echo esc_html( date_i18n( 'F j, Y' ) ); ?></p>
            </div>

            <div class="prose prose-lg prose-slate max-w-none font-bold text-slate-600 leading-relaxed space-y-8">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">1. Acceptance of Terms</h2>
                    <p>By accessing or using the Varner Equipment website, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must not use our site or services.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">2. Equipment Listings &amp; Pricing</h2>
                    <p>All equipment listings are subject to prior sale. Prices are subject to change without notice. We make every effort to ensure accuracy, but errors may occur. We reserve the right to correct any errors and update listings at any time.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">3. Financing &amp; Credit Applications</h2>
                    <p>Financing is provided by third-party lenders. Approval is subject to credit review and lender terms. Any estimated payment calculations are for informational purposes only and do not constitute a binding offer.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">4. Service &amp; Parts Requests</h2>
                    <p>Service and parts estimates are approximate. Final pricing may vary based on inspection, parts availability, and labor required. Submitting a request does not create a binding agreement for service.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">5. SMS &amp; Communications</h2>
                    <p>By providing your mobile number, you consent to receive text messages from Varner Equipment regarding your inquiry, request, or transaction. Message and data rates may apply. You may opt out at any time by replying STOP.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">6. Intellectual Property</h2>
                    <p>All content on this website—including text, images, logos, and branding—is the property of Varner Equipment unless otherwise stated. Unauthorized use, reproduction, or distribution is prohibited.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">7. Limitation of Liability</h2>
                    <p>Varner Equipment shall not be held liable for any direct, indirect, incidental, or consequential damages arising from your use of this website or the purchase, use, or failure of any equipment or service obtained through us.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">8. Changes to Terms</h2>
                    <p>We reserve the right to modify these terms at any time. Changes will be posted on this page with an updated revision date. Continued use of the site after changes constitutes acceptance of the revised terms.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">9. Contact</h2>
                    <p>For questions about these Terms of Service, contact us at:</p>
                    <p class="mt-2">
                        Varner Equipment<br>
                        1375 US-50<br>
                        Delta, CO 81416
                    </p>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
