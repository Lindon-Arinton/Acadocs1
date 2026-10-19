<?php
/**
 * Display & accessibility preferences (Settings page, topbar theme toggle).
 * Per-device, in localStorage — included inline in every app <head> so the
 * page never paints first with the wrong theme / text size and then jumps.
 *
 * Theme stays under its original 'theme' key ('light' | 'dark' | 'system');
 * everything else is one JSON object under 'display_prefs'. The resolved
 * values land on <html> as attributes that app.css keys off of.
 *
 * Fires on document:
 *   acadocs:themechange  — the resolved light/dark theme actually changed
 *   acadocs:prefschange  — any preference was set or reset
 */
?>
<script>
(function () {
    var THEME_KEY = 'theme';
    var PREFS_KEY = 'display_prefs';
    var DEFAULTS  = {
        theme: 'light',
        textSize: 100,         // % of the browser's base font size
        reduceMotion: false,
        highContrast: false,
        underlineLinks: false,
        focusRing: false,
        textSpacing: false
    };
    var TEXT_SIZES = [90, 100, 112.5, 125];

    var root       = document.documentElement;
    var darkQuery  = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function read(key) {
        try { return localStorage.getItem(key); } catch (e) { return null; }
    }
    function write(key, value) {
        try { value === null ? localStorage.removeItem(key) : localStorage.setItem(key, value); } catch (e) {}
    }

    function get() {
        var prefs = {}, stored = {}, k;
        try { stored = JSON.parse(read(PREFS_KEY) || '{}') || {}; } catch (e) { stored = {}; }
        for (k in DEFAULTS) prefs[k] = (k in stored) ? stored[k] : DEFAULTS[k];
        var theme = read(THEME_KEY);
        prefs.theme = (theme === 'dark' || theme === 'system') ? theme : 'light';
        if (TEXT_SIZES.indexOf(Number(prefs.textSize)) === -1) prefs.textSize = DEFAULTS.textSize;
        return prefs;
    }

    function resolvedTheme(prefs) {
        if (prefs.theme === 'system') return darkQuery && darkQuery.matches ? 'dark' : 'light';
        return prefs.theme;
    }

    function flag(name, on) {
        if (on) root.setAttribute(name, ''); else root.removeAttribute(name);
    }

    function apply() {
        var prefs = get();
        var before = root.getAttribute('data-bs-theme');
        var theme = resolvedTheme(prefs);

        root.setAttribute('data-bs-theme', theme);
        root.style.fontSize = prefs.textSize === 100 ? '' : prefs.textSize + '%';
        // The OS-level "reduce motion" request is always honored; the setting
        // turns it on for devices that don't expose one.
        flag('data-reduce-motion', prefs.reduceMotion || (motionQuery && motionQuery.matches));
        flag('data-high-contrast', prefs.highContrast);
        flag('data-underline-links', prefs.underlineLinks);
        flag('data-focus-ring', prefs.focusRing);
        flag('data-text-spacing', prefs.textSpacing);

        if (before && before !== theme) document.dispatchEvent(new CustomEvent('acadocs:themechange', { detail: { theme: theme } }));
        return prefs;
    }

    function changed() {
        var prefs = apply();
        document.dispatchEvent(new CustomEvent('acadocs:prefschange', { detail: prefs }));
        return prefs;
    }

    /*
     * Runs changed() with an animation when the light/dark theme flips:
     * a circle growing out of opts.origin ({x, y} viewport px — the button
     * that was clicked), a crossfade without an origin, or a CSS color fade
     * where the View Transitions API is missing. Instant under reduce motion.
     */
    function changedWithThemeTransition(opts) {
        var origin = opts && opts.origin;
        if (resolvedTheme(get()) === root.getAttribute('data-bs-theme') || root.hasAttribute('data-reduce-motion')) {
            changed();
            return;
        }

        if (!document.startViewTransition) {
            root.classList.add('theme-fade');
            changed();
            setTimeout(function () { root.classList.remove('theme-fade'); }, 450);
            return;
        }

        if (origin) root.classList.add('theme-reveal');
        var transition = document.startViewTransition(changed);
        if (origin) {
            transition.ready.then(function () {
                var radius = Math.hypot(Math.max(origin.x, innerWidth - origin.x), Math.max(origin.y, innerHeight - origin.y));
                root.animate(
                    { clipPath: ['circle(0px at ' + origin.x + 'px ' + origin.y + 'px)', 'circle(' + radius + 'px at ' + origin.x + 'px ' + origin.y + 'px)'] },
                    { duration: 550, easing: 'cubic-bezier(.4, 0, .2, 1)', pseudoElement: '::view-transition-new(root)' }
                );
            }).catch(function () {});
        }
        transition.finished.finally(function () { root.classList.remove('theme-reveal'); });
    }

    // The new values are saved right away (so get() reflects them), but the
    // page itself may update a frame later when the theme change animates —
    // listen for acadocs:prefschange rather than using the return value.
    function set(key, value, opts) {
        if (!(key in DEFAULTS)) return get();
        if (key === 'theme') {
            write(THEME_KEY, value === 'dark' || value === 'system' ? value : 'light');
            changedWithThemeTransition(opts);
            return get();
        }
        var prefs = get(), stored = {}, k;
        prefs[key] = value;
        for (k in DEFAULTS) if (k !== 'theme' && prefs[k] !== DEFAULTS[k]) stored[k] = prefs[k];
        write(PREFS_KEY, Object.keys(stored).length ? JSON.stringify(stored) : null);
        return changed();
    }

    function reset(opts) {
        write(THEME_KEY, null);
        write(PREFS_KEY, null);
        changedWithThemeTransition(opts);
        return get();
    }

    function onMediaChange(query, handler) {
        if (!query) return;
        if (query.addEventListener) query.addEventListener('change', handler);
        else if (query.addListener) query.addListener(handler);
    }
    onMediaChange(darkQuery, function () { changedWithThemeTransition(); });
    onMediaChange(motionQuery, changed);

    // Another tab changed a setting — follow it here too.
    window.addEventListener('storage', function (e) {
        if (e.key === THEME_KEY || e.key === PREFS_KEY || e.key === null) changed();
    });

    window.AcadocsPrefs = { DEFAULTS: DEFAULTS, TEXT_SIZES: TEXT_SIZES, get: get, set: set, reset: reset, apply: apply };
    apply();
})();
</script>
