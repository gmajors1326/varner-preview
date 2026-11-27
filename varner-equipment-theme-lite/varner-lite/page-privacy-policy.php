<?php 
/* Template Name: Privacy Policy */
get_header(); 
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-4xl mx-auto px-4">
            <div class="mb-12">
                <div class="text-red-600 font-black text-xs uppercase tracking-[0.4em] mb-4">Varner Equipment</div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php echo esc_html( get_the_title() ?: 'Privacy Policy' ); ?></h1>
                <div class="w-24 h-2 bg-red-600 mb-10"></div>
                <p class="text-slate-500 font-bold text-sm uppercase tracking-widest">Last updated: <?php echo esc_html( date_i18n( 'F j, Y' ) ); ?></p>
            </div>

            <div class="prose prose-lg prose-slate max-w-none font-bold text-slate-600 leading-relaxed space-y-8">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">1. Information We Collect</h2>
                    <p>We collect personal information you provide directly, such as your name, email address, phone number, and mailing address when you submit a contact form, parts or service request, financing application, or otherwise communicate with us. We also automatically collect certain technical data including IP address, browser type, device information, and pages visited.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">2. How We Use Your Information</h2>
                    <p>We use your information to respond to your inquiries, process parts and service requests, provide financing estimates, send relevant equipment listings and promotions (with your consent), improve our website and customer experience, and comply with legal obligations.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">3. SMS &amp; Communications Consent</h2>
                    <p>By providing your mobile number, you consent to receive text messages from Varner Equipment regarding your inquiry, service updates, and marketing communications. Message and data rates may apply. You may opt out at any time by replying STOP. We do not share your mobile number with third parties for their marketing purposes.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">4. Information Sharing</h2>
                    <p>We do not sell your personal information. We may share your data with trusted third-party service providers who assist in operating our website and business (e.g., payment processors, financing partners, SMS delivery services), provided they agree to keep your information confidential.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">5. Data Security</h2>
                    <p>We implement reasonable administrative, technical, and physical security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction. However, no method of electronic storage or transmission is 100% secure.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">6. Cookies &amp; Tracking</h2>
                    <p>Our website may use cookies and similar tracking technologies to enhance your browsing experience, analyze site traffic, and serve relevant content. You can control cookie preferences through your browser settings. Disabling cookies may affect site functionality.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">7. Your Rights</h2>
                    <p>Depending on your jurisdiction, you may have the right to access, correct, delete, or port your personal data, as well as the right to withdraw consent or opt out of certain processing activities. To exercise these rights, contact us using the information below.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">8. Third-Party Links</h2>
                    <p>Our website may contain links to third-party sites. We are not responsible for the privacy practices or content of those sites. We encourage you to review their privacy policies before providing any personal information.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-4">9. Contact</h2>
                    <p>For questions about this Privacy Policy or to exercise your data rights, contact us at:</p>
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
