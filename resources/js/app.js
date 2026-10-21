import Alpine from 'alpinejs';

const store = {
    get(key, fallback) { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; } },
    set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* storage unavailable */ } },
};

Alpine.store('compare', {
    items: store.get('mg_compare', []),
    has(slug) { return this.items.includes(slug); },
    toggle(slug) {
        this.items = this.has(slug) ? this.items.filter((s) => s !== slug) : [...this.items, slug].slice(-4);
        store.set('mg_compare', this.items);
    },
    clear() { this.items = []; store.set('mg_compare', []); },
    url(base) { return base + '?' + this.items.map((s) => 'p[]=' + encodeURIComponent(s)).join('&'); },
});

Alpine.data('suggest', () => ({
    q: '', items: [], open: false,
    async fetch(url) {
        if (this.q.trim().length < 2) { this.items = []; return; }
        try {
            const res = await window.fetch(url + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
            this.items = res.ok ? await res.json() : [];
            this.open = true;
        } catch { this.items = []; }
    },
}));

// Analytics and ads load only after the visitor accepts all cookies.
function loadOptional() {
    const ga = document.querySelector('meta[name="mg-ga"]')?.content;
    if (ga && /^[A-Za-z0-9-]+$/.test(ga)) {
        const s = document.createElement('script');
        s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ga;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date()); window.gtag('config', ga, { anonymize_ip: true });
    }
    const ad = document.querySelector('meta[name="mg-adsense"]')?.content;
    if (ad && /^ca-pub-[0-9]+$/.test(ad)) {
        const s = document.createElement('script');
        s.async = true; s.crossOrigin = 'anonymous';
        s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + ad;
        document.head.appendChild(s);
        document.querySelectorAll('ins.adsbygoogle').forEach(() => (window.adsbygoogle = window.adsbygoogle || []).push({}));
    }
}

Alpine.data('consent', () => ({
    show: false,
    init() {
        const choice = store.get('mg_consent', null);
        if (choice === null) { this.show = true; } else if (choice === 'all') { loadOptional(); }
    },
    choose(choice) { store.set('mg_consent', choice); this.show = false; if (choice === 'all') loadOptional(); },
}));

Alpine.data('share', (url, title) => ({
    copied: false,
    async copy() { try { await navigator.clipboard.writeText(url); this.copied = true; setTimeout(() => (this.copied = false), 2000); } catch { window.prompt('Copy link', url); } },
    native() { return navigator.share ? navigator.share({ url, title }).catch(() => {}) : this.copy(); },
}));

Alpine.data('productForm', (init) => ({ features: init.features, packaging: init.packaging, specs: init.specs, approvals: init.approvals }));

window.Alpine = Alpine;
Alpine.start();
