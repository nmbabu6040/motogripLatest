import 'trix';
import 'trix/dist/trix.css';
import Chart from 'chart.js/auto';

// Any <canvas data-chart-url="..."> loads a first-party report series and draws it.
document.querySelectorAll('canvas[data-chart-url]').forEach(async (canvas) => {
    const box = canvas.closest('[data-chart-box]');
    try {
        const res = await fetch(canvas.dataset.chartUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('bad response');
        const data = await res.json();
        const status = box?.querySelector('[data-chart-status]');
        if (data.total === 0) {
            canvas.classList.add('hidden');
            if (status) status.textContent = 'No data recorded in this period yet.';
            return;
        }
        if (status) status.textContent = `${data.label}: ${data.total} in this period (source: ${data.source}).`;
        new Chart(canvas, {
            type: 'line',
            data: { labels: data.labels, datasets: [{ label: data.label, data: data.values, borderColor: '#174A7E', backgroundColor: 'rgba(23,74,126,.12)', fill: true, tension: 0.25, pointRadius: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    } catch {
        const status = box?.querySelector('[data-chart-status]');
        if (status) status.textContent = 'This chart could not be loaded.';
    }
});
