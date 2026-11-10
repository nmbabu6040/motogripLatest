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

// Sale and purchase line editor (items, quantities, prices, live totals).
Alpine.data('erpLines', (cfg) => ({
    items: cfg.items, mode: cfg.mode, discount: cfg.discount ?? 0, tax: cfg.tax ?? 0,
    rows: (cfg.rows && cfg.rows.length ? cfg.rows : [{ stock_item_id: '', quantity: 1, price: '' }]),
    find(id) { return this.items.find((i) => String(i.id) === String(id)); },
    pick(row) { const it = this.find(row.stock_item_id); if (it && (row.price === '' || row.price === null)) row.price = this.mode === 'sale' ? it.price : it.cost; },
    over(row) { const it = this.find(row.stock_item_id); return !!it && (Number(row.quantity) || 0) > it.stock; },
    add() { this.rows.push({ stock_item_id: '', quantity: 1, price: '' }); },
    remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.add(); },
    get subtotal() { return this.rows.reduce((s, r) => s + (Number(r.quantity) || 0) * (Number(r.price) || 0), 0); },
    get discountValue() { return Math.min(Math.max(Number(this.discount) || 0, 0), this.subtotal); },
    get taxValue() { return (this.subtotal - this.discountValue) * Math.min(Math.max(Number(this.tax) || 0, 0), 100) / 100; },
    get total() { return this.subtotal - this.discountValue + this.taxValue; },
    money(n) { return cfg.symbol + ' ' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
}));

// Counts a click on a recommended (affiliate) link. The visitor still goes straight to the store.
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-partner-click]');
    if (!a) return;
    try {
        fetch(a.dataset.partnerClick, { method: 'POST', keepalive: true, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '', Accept: 'application/json' } });
    } catch { /* counting is best-effort */ }
});

window.Alpine = Alpine;
Alpine.start();
