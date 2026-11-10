import 'trix';
import 'trix/dist/trix.css';
import Chart from 'chart.js/auto';

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const PALETTE = ['#174A7E', '#F5B82E', '#2BB673', '#E4572E', '#7B61FF', '#17BEBB', '#FF8FA3', '#4E5D78'];

Chart.defaults.font.family = '"Manrope Variable", system-ui, sans-serif';
Chart.defaults.color = '#4E5D78';
Chart.defaults.animation = reduce ? false : { duration: 900, easing: 'easeOutQuart' };

const number = (v) => Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 });
const fmt = (v, money, symbol) => (money ? `${symbol} ${number(v)}` : number(v));

function build(canvas, data) {
    const symbol = canvas.dataset.symbol || '';
    const type = data.type || 'line';
    const colors = data.colors || PALETTE;
    let datasets;

    if (type === 'doughnut') {
        datasets = data.datasets.map((ds) => ({ ...ds, backgroundColor: data.labels.map((_, i) => colors[i % colors.length]), borderWidth: 3, borderColor: '#fff', hoverOffset: 10 }));
    } else if (type === 'line') {
        datasets = data.datasets.map((ds, i) => {
            const c = colors[i % colors.length];
            return {
                ...ds, borderColor: c, borderWidth: 3, fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: c,
                backgroundColor: (context) => {
                    const { chart } = context;
                    if (!chart.chartArea) return `${c}33`;
                    const g = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
                    g.addColorStop(0, `${c}66`); g.addColorStop(1, `${c}05`);
                    return g;
                },
            };
        });
    } else {
        datasets = data.datasets.map((ds, i) => ({ ...ds, backgroundColor: colors[i % colors.length], borderRadius: 6, maxBarThickness: 26 }));
    }

    new Chart(canvas, {
        type,
        data: { labels: data.labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: type === 'doughnut' ? '62%' : undefined,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: type === 'doughnut' || datasets.length > 1, position: type === 'doughnut' ? 'bottom' : 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label || c.label}: ${fmt(c.parsed.y ?? c.parsed, data.money, symbol)}` } },
            },
            scales: type === 'doughnut' ? {} : {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
                y: { beginAtZero: true, grid: { color: '#eef1f5' }, ticks: { precision: 0, callback: (v) => fmt(v, data.money, symbol) } },
            },
        },
    });
}

async function loadChart(canvas) {
    const box = canvas.closest('[data-chart-box]');
    const status = box?.querySelector('[data-chart-status]');
    const holder = canvas.parentElement;
    holder.classList.add('skeleton');
    try {
        const res = await fetch(canvas.dataset.chartUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('bad response');
        let data = await res.json();
        // Website-activity reports use {label, labels, values, total, source}.
        if (!data.datasets) data = { type: 'line', money: false, labels: data.labels, datasets: [{ label: data.label, data: data.values }], total: data.total, source: data.source, label: data.label };
        holder.classList.remove('skeleton');

        const sum = data.datasets.reduce((s, d) => s + d.data.reduce((a, b) => a + Number(b), 0), 0);
        if (sum === 0) {
            canvas.classList.add('hidden');
            if (status) status.textContent = 'No data recorded in this period yet.';
            return;
        }
        if (status) status.textContent = data.source ? `${data.label}: ${number(data.total)} in this period (source: ${data.source}).` : '';
        build(canvas, data);
    } catch {
        holder.classList.remove('skeleton');
        if (status) status.textContent = 'This chart could not be loaded.';
    }
}

// Charts load when they scroll into view, so the animation is seen and the page opens quickly.
const canvases = document.querySelectorAll('canvas[data-chart-url]');
if ('IntersectionObserver' in window && !reduce) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { io.unobserve(e.target); loadChart(e.target); } }), { rootMargin: '120px' });
    canvases.forEach((c) => io.observe(c));
} else {
    canvases.forEach(loadChart);
}

// Numbers count up once when a card comes into view.
function countUp(el) {
    const target = Number(el.dataset.value || 0);
    const money = el.dataset.format === 'money';
    const symbol = el.dataset.symbol || '';
    const decimals = money ? 2 : 0;
    const show = (v) => (money ? `${symbol} ` : '') + Number(v).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    if (reduce || target === 0) { el.textContent = show(target); return; }
    const start = performance.now();
    const duration = 1100;
    const step = (now) => {
        const t = Math.min((now - start) / duration, 1);
        el.textContent = show(target * (1 - Math.pow(1 - t, 3)));
        if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}
const counters = document.querySelectorAll('[data-countup]');
if ('IntersectionObserver' in window) {
    const io2 = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { io2.unobserve(e.target); countUp(e.target); } }), { threshold: 0.4 });
    counters.forEach((c) => io2.observe(c));
} else {
    counters.forEach(countUp);
}
