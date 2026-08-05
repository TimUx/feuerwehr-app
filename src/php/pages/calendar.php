<?php
/**
 * Calendar view – training & missions
 */
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';
Auth::requireOperator();
$month = date('Y-m');
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><span class="material-icons">calendar_month</span> Kalender</h1>
        <p class="page-subtitle">Übungen und Einsätze im Monatsüberblick</p>
    </div>

    <div class="card">
        <div class="card-content">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin-bottom:1rem; flex-wrap:wrap;">
                <button type="button" class="icon-btn" id="cal-prev" style="color:var(--text-primary); background:var(--bg-secondary);" aria-label="Vorheriger Monat">
                    <span class="material-icons">chevron_left</span>
                </button>
                <h2 id="cal-title" style="margin:0; font-size:1.15rem;"></h2>
                <button type="button" class="icon-btn" id="cal-next" style="color:var(--text-primary); background:var(--bg-secondary);" aria-label="Nächster Monat">
                    <span class="material-icons">chevron_right</span>
                </button>
            </div>
            <div class="calendar-grid" id="calendar-grid" aria-live="polite"></div>
            <div id="cal-day-detail" style="margin-top:1.25rem;"></div>
            <div style="margin-top:1rem; display:flex; gap:1rem; font-size:0.85rem; color:var(--text-secondary);">
                <span><span class="cal-dot attendance"></span> Übung</span>
                <span><span class="cal-dot mission"></span> Einsatz</span>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.cal-weekday {
    text-align: center;
    font-size: 0.75rem;
    color: var(--text-secondary);
    padding: 0.35rem 0;
    font-weight: 500;
}
.cal-cell {
    min-height: 64px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 4px;
    background: var(--bg-card);
    cursor: pointer;
}
.cal-cell.empty { background: transparent; border-color: transparent; cursor: default; }
.cal-cell.today { outline: 2px solid var(--primary-color); }
.cal-cell.selected { background: rgba(211, 47, 47, 0.08); }
.cal-daynum { font-size: 0.8rem; font-weight: 500; }
.cal-markers { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 4px; }
.cal-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--info-color);
}
.cal-dot.attendance { background: var(--success-color); }
.cal-dot.mission { background: var(--primary-color); }
@media (max-width: 480px) {
    .cal-cell { min-height: 48px; }
}
</style>

<script>
(function () {
    let yearMonth = '<?php echo $month; ?>';
    let eventsByDate = {};
    const weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    function shiftMonth(delta) {
        const [y, m] = yearMonth.split('-').map(Number);
        const d = new Date(y, m - 1 + delta, 1);
        yearMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        load();
    }

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function showDay(dateStr) {
        const detail = document.getElementById('cal-day-detail');
        document.querySelectorAll('.cal-cell.selected').forEach(el => el.classList.remove('selected'));
        const cell = document.querySelector('.cal-cell[data-date="' + dateStr + '"]');
        if (cell) cell.classList.add('selected');

        const list = eventsByDate[dateStr] || [];
        if (!list.length) {
            detail.innerHTML = `<p style="color:var(--text-secondary);">${escapeHtml(dateStr)}: keine Einträge</p>`;
            return;
        }
        detail.innerHTML = `<h3 style="margin:0 0 0.5rem;font-size:1rem;">${escapeHtml(dateStr)}</h3>` +
            '<ul style="padding-left:1.1rem;margin:0;">' +
            list.map(e => {
                const label = e.type === 'mission' ? 'Einsatz' : 'Übung';
                const extra = e.meta?.ort || (e.meta?.von ? (e.meta.von + '–' + (e.meta.bis || '')) : '');
                return `<li style="margin-bottom:0.35rem;">
                    <strong>${label}:</strong> ${escapeHtml(e.title)}
                    ${extra ? `<span style="color:var(--text-secondary);"> · ${escapeHtml(extra)}</span>` : ''}
                </li>`;
            }).join('') + '</ul>';
    }

    async function load() {
        const [y, m] = yearMonth.split('-').map(Number);
        document.getElementById('cal-title').textContent =
            new Date(y, m - 1, 1).toLocaleDateString('de-DE', { month: 'long', year: 'numeric' });

        const res = await fetch('/src/php/api/calendar.php?month=' + encodeURIComponent(yearMonth));
        const data = await res.json();
        eventsByDate = {};
        (data.data || []).forEach(e => {
            if (!eventsByDate[e.date]) eventsByDate[e.date] = [];
            eventsByDate[e.date].push(e);
        });

        const first = new Date(y, m - 1, 1);
        const daysInMonth = new Date(y, m, 0).getDate();
        // Monday-based: JS getDay() Sun=0 → convert
        let startPad = (first.getDay() + 6) % 7;
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        const grid = document.getElementById('calendar-grid');
        let html = weekdays.map(w => `<div class="cal-weekday">${w}</div>`).join('');
        for (let i = 0; i < startPad; i++) html += '<div class="cal-cell empty"></div>';
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = yearMonth + '-' + String(day).padStart(2, '0');
            const evs = eventsByDate[dateStr] || [];
            const markers = evs.slice(0, 4).map(e => `<span class="cal-dot ${e.type}"></span>`).join('');
            const cls = ['cal-cell'];
            if (dateStr === todayStr) cls.push('today');
            html += `<div class="${cls.join(' ')}" data-date="${dateStr}" role="button" tabindex="0">
                <div class="cal-daynum">${day}</div>
                <div class="cal-markers">${markers}</div>
            </div>`;
        }
        grid.innerHTML = html;
        grid.querySelectorAll('.cal-cell[data-date]').forEach(el => {
            el.addEventListener('click', () => showDay(el.dataset.date));
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    showDay(el.dataset.date);
                }
            });
        });
        document.getElementById('cal-day-detail').innerHTML = '';
    }

    document.getElementById('cal-prev').addEventListener('click', () => shiftMonth(-1));
    document.getElementById('cal-next').addEventListener('click', () => shiftMonth(1));
    load();
})();
</script>
