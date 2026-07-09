<?php
$privacy_url = home_url( '/legal/privacy-policy' );
$version     = function_exists( 'varner_cookie_get_effective_version' )
    ? varner_cookie_get_effective_version()
    : 1;
?>
<div id="varner-cookie-consent" class="fixed bottom-0 inset-x-0 z-[9999] bg-slate-950 border-t border-white/10 shadow-2xl translate-y-full transition-transform duration-500 ease-out" aria-label="Cookie Consent Banner" role="dialog" aria-modal="false" data-consent-version="<?php echo intval( $version ); ?>">
    <div class="max-w-7xl mx-auto px-4 py-5 sm:py-6">
        <div class="flex flex-col gap-6">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-black uppercase tracking-widest text-white mb-2">Cookie Notice</p>
                <p class="text-xs font-bold text-slate-400 leading-relaxed">
                    We use cookies to enhance your browsing experience, analyze site traffic, and serve relevant content.
                    By clicking "Accept All", you consent to our use of cookies.
                    See our <a href="<?php echo esc_url( $privacy_url ); ?>" class="text-red-500 hover:text-red-400 underline transition-colors">Privacy Policy</a> for details.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <label class="flex items-center gap-3 cursor-pointer opacity-60 pointer-events-none">
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-red-600">
                        <span class="inline-block h-3.5 w-3.5 translate-x-1 rounded-full bg-white"></span>
                    </span>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Necessary</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer" data-cookie-toggle="analytics">
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-slate-700 transition-colors" data-cookie-track="bg">
                        <span class="inline-block h-3.5 w-3.5 translate-x-1 rounded-full bg-white transition-transform" data-cookie-track="thumb"></span>
                    </span>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Analytics</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer" data-cookie-toggle="marketing">
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-slate-700 transition-colors" data-cookie-track="bg">
                        <span class="inline-block h-3.5 w-3.5 translate-x-1 rounded-full bg-white transition-transform" data-cookie-track="thumb"></span>
                    </span>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Marketing</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" data-cookie-action="save" class="bg-slate-900 text-white border border-slate-800 px-5 py-3 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-red-600 hover:border-red-600 transition-all">
                    Save Preferences
                </button>
                <button type="button" data-cookie-action="accept-all" class="bg-red-600 text-white px-5 py-3 rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg hover:bg-red-700 transition-all active:scale-95">
                    Accept All
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var KEY = 'varner_cookie_consent';
    var banner = document.getElementById('varner-cookie-consent');
    if (!banner) return;

    var version = parseInt(banner.getAttribute('data-consent-version'), 10) || 1;

    var stored = (function() {
        try { return JSON.parse(localStorage.getItem(KEY)); } catch(e) { return null; }
    })();

    if (stored && stored.version === version && stored.consent) {
        return;
    }

    requestAnimationFrame(function() {
        banner.classList.remove('translate-y-full');
    });

    var state = { analytics: false, marketing: false };

    function toggleSwitch(label) {
        var bg = label.querySelector('[data-cookie-track="bg"]');
        var thumb = label.querySelector('[data-cookie-track="thumb"]');
        var cat = label.getAttribute('data-cookie-toggle');
        state[cat] = !state[cat];
        if (state[cat]) {
            bg.classList.remove('bg-slate-700');
            bg.classList.add('bg-red-600');
            thumb.classList.remove('translate-x-1');
            thumb.classList.add('translate-x-4');
        } else {
            bg.classList.remove('bg-red-600');
            bg.classList.add('bg-slate-700');
            thumb.classList.remove('translate-x-4');
            thumb.classList.add('translate-x-1');
        }
    }

    banner.addEventListener('click', function(e) {
        var toggle = e.target.closest('[data-cookie-toggle]');
        var action = e.target.closest('[data-cookie-action]');
        if (toggle) {
            toggleSwitch(toggle);
            return;
        }
        if (!action) return;

        var analytics, marketing;
        if (action.getAttribute('data-cookie-action') === 'accept-all') {
            analytics = true;
            marketing = true;
        } else {
            analytics = state.analytics;
            marketing = state.marketing;
        }

        var payload = {
            version: version,
            consent: true,
            necessary: true,
            analytics: analytics,
            marketing: marketing,
            timestamp: new Date().toISOString()
        };
        try { localStorage.setItem(KEY, JSON.stringify(payload)); } catch(e) {}
        banner.classList.add('translate-y-full');
        setTimeout(function() {
            banner.style.display = 'none';
            document.dispatchEvent(new CustomEvent('varner-consent-updated', { detail: payload }));
        }, 500);
    });
})();
</script>
