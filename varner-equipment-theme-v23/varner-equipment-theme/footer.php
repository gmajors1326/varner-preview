    <!-- LOCATION / GOOGLE MAPS SECTION -->
    <section class="border-b border-slate-200 overflow-hidden bg-white">
        <div class="w-full h-[400px] sm:h-[500px] bg-slate-200 relative">
            <iframe 
                src="https://www.google.com/maps?q=Varner%20Equipment%2C%201375%20US-50%2C%20Delta%2C%20CO%2081416&z=8&output=embed" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                class="absolute inset-0 grayscale hover:grayscale-0 transition-all duration-700"
            ></iframe>

            <!-- OVERLAY INFO CARD -->
            <div class="absolute bottom-4 left-4 right-4 sm:right-auto sm:bottom-10 sm:left-10 z-10">
                <div class="bg-white p-6 sm:p-8 rounded-2xl sm:rounded-3xl shadow-2xl border-2 border-slate-100 max-w-sm w-full mx-auto text-slate-900">
                    <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-3 sm:mb-4">Our Dealership</div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-3 sm:mb-4 uppercase tracking-tight">Varner Equipment</h3>
                    <div class="space-y-1 sm:space-y-2 text-slate-600 font-bold text-xs sm:text-sm mb-5 sm:mb-6">
                        <p>1375 US-50</p>
                        <p>Delta, CO 81416</p>
                    </div>
                    <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener" class="block text-center w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:bg-red-600 transition-all">
                        Get Directions
                    </a>
                </div>
            </div>
        </div>
    </section>
    <div class="h-2 bg-red-600"></div>

    <!-- FOOTER -->
    <footer class="bg-slate-950 pt-24 pb-12 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 xl:gap-10 mb-16 items-start">
            <div class="lg:col-span-2 flex flex-col items-center">
                <div class="w-fit text-left">
                <h4 class="text-red-500 font-black uppercase text-sm tracking-[0.25em] mb-6">Inventory</h4>
                <ul class="space-y-4 text-white text-sm font-bold text-left w-fit">
                    <li><a href="<?php echo esc_url( home_url( '/inventory' ) ); ?>" class="hover:text-red-500 transition-colors">All Inventory</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/inventory/in-stock-inventory' ) ); ?>" class="hover:text-red-500 transition-colors">In Stock Inventory</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/inventory/showroom-inventory' ) ); ?>" class="hover:text-red-500 transition-colors">Showroom Inventory</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/brands' ) ); ?>" class="hover:text-red-500 transition-colors">Brands</a></li>
                </ul>
                </div>
            </div>
            <div class="lg:col-span-2 flex flex-col items-center">
                <div class="w-fit text-left">
                <h4 class="text-red-500 font-black uppercase text-sm tracking-[0.25em] mb-6 whitespace-nowrap">Quick Links</h4>
                <ul class="space-y-4 text-white text-sm font-bold text-left w-fit">
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-red-500 transition-colors">Home</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/product-videos' ) ); ?>" class="hover:text-red-500 transition-colors">Product Videos</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/services/parts-request' ) ); ?>" class="hover:text-red-500 transition-colors">Parts Request</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/services/service-request' ) ); ?>" class="hover:text-red-500 transition-colors">Service Request</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dealer-info/about-us' ) ); ?>" class="hover:text-red-500 transition-colors">About</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dealer-info/employment' ) ); ?>" class="hover:text-red-500 transition-colors">Careers</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="hover:text-red-500 transition-colors">Contact</a></li>
                </ul>
                </div>
            </div>
            <div class="lg:col-span-2 flex flex-col items-center">
                <div class="w-fit text-left">
                <h4 class="text-red-500 font-black uppercase text-sm tracking-[0.25em] mb-6">Hours</h4>
                <ul class="space-y-4 text-white text-sm font-bold text-left w-fit">
                    <li>Mon-Fri 8am-5pm</li>
                    <li>Sat 9-Noon</li>
                    <li>Sun Closed</li>
                </ul>
                </div>
            </div>
            <div class="lg:col-span-3 flex flex-col items-center text-center space-y-6">
                <div class="block w-full max-w-[250px] sm:max-w-[280px] lg:max-w-[240px] xl:max-w-[280px]">
                    <?php 
                    $brand_logo_url = function_exists('varner_get_brand_logo_url') ? varner_get_brand_logo_url('white') : '';
                    ?>
                    <img src="<?php echo esc_url($brand_logo_url); ?>" alt="Varner Equipment" class="w-full h-auto object-contain mx-auto">
                </div>
                <div class="flex items-center justify-center gap-5 text-white pt-2">
                    <a href="https://www.facebook.com/varnerequipment" target="_blank" rel="noopener" class="hover:text-red-500 transition-colors" aria-label="Facebook">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H7.1V12h3.4V9.7c0-3.3 2-5.1 5-5.1 1.4 0 2.8.2 2.8.2v3.1h-1.6c-1.6 0-2.1 1-2.1 2V12h3.6l-.6 2.9h-3v7A10 10 0 0 0 22 12z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener" class="hover:text-red-500 transition-colors" aria-label="YouTube">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M19.6 7.2a2.6 2.6 0 0 0-1.8-1.8C16.2 5 12 5 12 5s-4.2 0-5.8.4a2.6 2.6 0 0 0-1.8 1.8A27.4 27.4 0 0 0 4 12a27.4 27.4 0 0 0 .4 4.8 2.6 2.6 0 0 0 1.8 1.8C7.8 19 12 19 12 19s4.2 0 5.8-.4a2.6 2.6 0 0 0 1.8-1.8A27.4 27.4 0 0 0 20 12a27.4 27.4 0 0 0-.4-4.8zM10 15.5V8.5L16 12l-6 3.5z"/></svg>
                    </a>
                    <a href="mailto:info@varnerequipment.com" class="hover:text-red-500 transition-colors" aria-label="Email">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm0 2 8 5 8-5V8l-8 5-8-5v0z"/></svg>
                    </a>
                    <a href="https://maps.app.goo.gl/bM7LKVmX8K2T7LpK9" target="_blank" rel="noopener" class="hover:text-red-500 transition-colors" aria-label="Location">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 1 7 7c0 5.3-7 13-7 13S5 14.3 5 9a7 7 0 0 1 7-7zm0 9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                    </a>
                </div>
            </div>

            <!-- CHATBOX COLUMN -->
            <div class="lg:col-span-3">
                <div class="w-full max-w-md mx-auto bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl">
                    <div id="varner-chat-step-choose" class="space-y-6">
                        <div class="space-y-2 text-center">
                            <h3 class="text-white font-black uppercase text-sm tracking-[0.25em]">Questions or Inquiries</h3>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.2em]">Choose a Department</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button data-department="Trailer Sales" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Trailer Sales</button>
                            <button data-department="Equipment Sales" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Equipment Sales</button>
                            <button data-department="Service" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Service</button>
                            <button data-department="Parts" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Parts</button>
                            <button data-department="General" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">General</button>
                            <button data-department="Tech" class="varner-chat-dept bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-4 font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-all">Tech</button>
                        </div>
                    </div>

                    <div id="varner-chat-step-form" class="hidden space-y-6">
                        <div class="space-y-2">
                            <h3 class="text-white font-black uppercase text-sm tracking-[0.25em]">Add your details and a short message, we’ll respond with a text.</h3>
                            <p class="text-slate-400 text-xs font-bold">Department: <span id="varner-chat-dept-label" class="text-white">—</span></p>
                        </div>

                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
                            <?php wp_nonce_field( 'varner_chatbox_submit', 'varner_chatbox_nonce' ); ?>
                            <input type="hidden" name="action" value="varner_chatbox_submit">
                            <input type="hidden" name="department" id="varner-chat-dept-input" value="">

                            <input type="text" name="name" placeholder="Name" required class="w-full bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-3 font-bold text-sm focus:border-red-600 outline-none">
                            <input type="tel" name="mobile" placeholder="Mobile" required class="w-full bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-3 font-bold text-sm focus:border-red-600 outline-none">
                            <textarea name="message" rows="4" placeholder="Message" required class="w-full bg-slate-950 text-white border border-slate-800 rounded-2xl px-4 py-3 font-bold text-sm focus:border-red-600 outline-none"></textarea>

                            <div class="flex flex-col sm:flex-row gap-4">
                                <button type="button" id="varner-chat-back" class="w-full sm:w-auto bg-slate-900 text-white border border-slate-800 px-6 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-800 transition-all">Back</button>
                                <button type="submit" class="w-full sm:flex-1 bg-red-600 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-red-700 transition-all">Send</button>
                            </div>

                            <p class="text-[10px] text-slate-500 leading-relaxed">
                                By hitting "Send" you authorize Varner Equipment, Inc. to send text messages and marketing content to the mobile number provided, sometimes using automated technology. Consent is not a condition of purchase. Message & data rates apply. Message frequency may vary. Text HELP for support or more information. Text STOP to opt out at any time.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- COPYRIGHT BAR -->
    <div class="bg-slate-950 border-t border-slate-900 py-6 px-4">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-500 text-[11px] font-bold uppercase tracking-widest">
            <span>&copy; <?php echo date('Y'); ?> Varner Equipment. All Rights Reserved.</span>
            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>" class="hover:text-red-500 transition-colors">Privacy Policy</a>
                <span class="text-slate-700">|</span>
                <a href="<?php echo esc_url( home_url( '/terms-and-conditions' ) ); ?>" class="hover:text-red-500 transition-colors">Terms &amp; Conditions</a>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var buttons = document.querySelectorAll('.varner-chat-dept');
            var chooseStep = document.getElementById('varner-chat-step-choose');
            var formStep = document.getElementById('varner-chat-step-form');
            var deptLabel = document.getElementById('varner-chat-dept-label');
            var deptInput = document.getElementById('varner-chat-dept-input');
            var backBtn = document.getElementById('varner-chat-back');

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var dept = btn.getAttribute('data-department');
                    deptLabel.textContent = dept;
                    deptInput.value = dept;
                    chooseStep.classList.add('hidden');
                    formStep.classList.remove('hidden');
                });
            });

            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    formStep.classList.add('hidden');
                    chooseStep.classList.remove('hidden');
                });
            }
        })();
    </script>

    <script>
        (function() {
            var hero = document.getElementById('hero-parallax');
            var media = document.getElementById('hero-parallax-media');
            if (!hero || !media) return;

            var ticking = false;
            var speed = 0.25;

            function updateParallax() {
                var rect = hero.getBoundingClientRect();
                var scrollProgress = Math.min(Math.max((window.innerHeight - rect.top) / (window.innerHeight + rect.height), 0), 1);
                var translateY = (scrollProgress * 60 * speed);
                media.style.transform = 'translate3d(0,' + translateY + 'px,0)';
                ticking = false;
            }

            function onScroll() {
                if (!ticking) {
                    window.requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            }

            updateParallax();
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll);
        })();
    </script>

    <!-- LIGHTBOX MODAL -->
    <div id="vne-lightbox" class="fixed inset-0 z-[9999] bg-black/95 flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <button id="vne-lightbox-close" class="absolute top-6 right-6 text-white hover:text-red-500 transition-colors z-[100]" aria-label="Close Lightbox">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        
        <div class="relative w-full h-full flex items-center justify-center p-4 sm:p-8 lg:p-12">
            <button id="vne-lightbox-prev" class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-red-500 transition-colors z-[100] bg-black/20 p-2 rounded-full" aria-label="Previous">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            
            <img id="vne-lightbox-img" src="" alt="Full Screen Image" class="max-w-full max-h-full object-contain shadow-2xl select-none">
            
            <button id="vne-lightbox-next" class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-red-500 transition-colors z-[100] bg-black/20 p-2 rounded-full" aria-label="Next">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        
        <!-- Lightbox Counter / Caption -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/60 text-xs font-black uppercase tracking-[0.3em]">
            <span id="vne-lightbox-index">1</span> / <span id="vne-lightbox-total">1</span>
        </div>
    </div>

    <script>
        (function() {
            var lightbox = document.getElementById('vne-lightbox');
            var lbImg = document.getElementById('vne-lightbox-img');
            var lbClose = document.getElementById('vne-lightbox-close');
            var lbPrev = document.getElementById('vne-lightbox-prev');
            var lbNext = document.getElementById('vne-lightbox-next');
            var lbIndex = document.getElementById('vne-lightbox-index');
            var lbTotal = document.getElementById('vne-lightbox-total');
            
            var currentImages = [];
            var currentIndex = 0;

            function updateLightbox() {
                if (!currentImages.length) return;
                lbImg.src = currentImages[currentIndex];
                lbIndex.textContent = currentIndex + 1;
                lbTotal.textContent = currentImages.length;
                
                // Hide arrows if only one image
                lbPrev.style.display = currentImages.length > 1 ? 'block' : 'none';
                lbNext.style.display = currentImages.length > 1 ? 'block' : 'none';
            }

            function openLightbox(images, startIndex) {
                currentImages = images;
                currentIndex = startIndex || 0;
                updateLightbox();
                lightbox.classList.remove('opacity-0', 'pointer-events-none');
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                lightbox.classList.add('opacity-0', 'pointer-events-none');
                document.body.style.overflow = '';
            }

            function nextImage() {
                currentIndex = (currentIndex + 1) % currentImages.length;
                updateLightbox();
            }

            function prevImage() {
                currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
                updateLightbox();
            }

            // Global click listener for lightbox triggers
            document.addEventListener('click', function(e) {
                var trigger = e.target.closest('.vne-lightbox-trigger');
                if (trigger) {
                    e.preventDefault();
                    var imagesStr = trigger.getAttribute('data-images');
                    var startUrl = trigger.getAttribute('data-start');
                    if (imagesStr) {
                        var images = JSON.parse(imagesStr);
                        var index = images.indexOf(startUrl);
                        openLightbox(images, index >= 0 ? index : 0);
                    }
                }
            });

            lbClose.addEventListener('click', closeLightbox);
            lbNext.addEventListener('click', nextImage);
            lbPrev.addEventListener('click', prevImage);
            
            // Close on background click
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox || e.target.closest('.relative') === null) {
                    closeLightbox();
                }
            });

            // Keyboard support
            document.addEventListener('keydown', function(e) {
                if (lightbox.classList.contains('opacity-0')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowRight') nextImage();
                if (e.key === 'ArrowLeft') prevImage();
            });
        })();
    </script>

    <?php wp_footer(); ?>
</body>
</html>
