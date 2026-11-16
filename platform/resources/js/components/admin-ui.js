// Admin-panel UI helpers: theme (light / dark / system), styled confirm dialog,
// dashboard charts and the proposal-form section nav.

const THEME_KEY = 'theme';
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function readTheme() {
    try {
        return localStorage.getItem(THEME_KEY) || 'system';
    } catch {
        return 'system';
    }
}

function applyTheme(mode) {
    const root = document.documentElement;
    if (!root.hasAttribute('data-themeable')) return false; // client-facing pages stay light
    const dark = mode === 'dark' || (mode === 'system' && darkQuery.matches);
    root.classList.toggle('dark', dark);
    return dark;
}

export function registerAdminUi(Alpine) {
    // ------------------------------------------------------------ theme
    Alpine.store('theme', {
        mode: readTheme(),
        dark: document.documentElement.classList.contains('dark'),
        set(mode) {
            this.mode = mode;
            try {
                localStorage.setItem(THEME_KEY, mode);
            } catch {}
            this.dark = applyTheme(mode);
            window.dispatchEvent(new CustomEvent('theme-changed'));
        },
    });
    darkQuery.addEventListener('change', () => {
        const store = Alpine.store('theme');
        store.dark = applyTheme(store.mode);
        window.dispatchEvent(new CustomEvent('theme-changed'));
    });
    document.addEventListener('livewire:navigated', () => applyTheme(Alpine.store('theme').mode));

    // ------------------------------------------------------------ confirm dialog
    // Usage: @click="$confirm({ title, message, confirmText }).then(ok => ok && $wire.delete(1))"
    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        danger: true,
        resolver: null,
        ask(opts) {
            const o = typeof opts === 'string' ? { message: opts } : opts || {};
            this.title = o.title ?? 'Are you sure?';
            this.message = o.message ?? '';
            this.confirmText = o.confirmText ?? 'Confirm';
            this.danger = o.danger ?? true;
            this.resolver?.(false);
            this.open = true;
            return new Promise((resolve) => (this.resolver = resolve));
        },
        answer(value) {
            this.open = false;
            this.resolver?.(value);
            this.resolver = null;
        },
    });
    Alpine.magic('confirm', () => (opts) => Alpine.store('confirm').ask(opts));

    // ------------------------------------------------------------ charts
    // Single-series bar chart. `rows` = [{label, value, display}] ; colour = brand accent.
    Alpine.data('barChart', (rows, opts = {}) => ({
        chart: null,
        init() {
            this.draw();
            this._onTheme = () => this.draw();
            window.addEventListener('theme-changed', this._onTheme);
        },
        destroy() {
            window.removeEventListener('theme-changed', this._onTheme);
            this.chart?.destroy();
        },
        draw() {
            this.chart?.destroy();
            const css = getComputedStyle(document.documentElement);
            const accent = css.getPropertyValue('--color-brand-accent').trim() || '#7c5cff';
            const ink = css.getPropertyValue('--color-slate-500').trim();
            const grid = css.getPropertyValue('--color-slate-200').trim();
            const surface = css.getPropertyValue('--color-surface').trim() || '#ffffff';
            const horizontal = !!opts.horizontal;
            const valueAxis = {
                beginAtZero: true,
                grid: { color: grid, drawTicks: false },
                border: { display: false },
                ticks: { color: ink, padding: 6, maxTicksLimit: 5, callback: opts.percent ? (v) => v + '%' : (v) => compact(v, opts.indian) },
                max: opts.percent ? 100 : undefined,
            };
            const categoryAxis = { grid: { display: false }, border: { color: grid }, ticks: { color: ink, padding: 4 } };

            this.chart = new window.Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: rows.map((r) => r.label),
                    datasets: [
                        {
                            data: rows.map((r) => r.value),
                            backgroundColor: accent,
                            hoverBackgroundColor: accent,
                            borderColor: surface,
                            borderWidth: 0,
                            borderRadius: 4,
                            borderSkipped: 'start',
                            maxBarThickness: horizontal ? 18 : 28,
                        },
                    ],
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 250 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            displayColors: false,
                            callbacks: { label: (ctx) => rows[ctx.dataIndex].display ?? String(ctx.raw) },
                        },
                    },
                    scales: horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
                },
            });
        },
    }));

    // ------------------------------------------------------------ section nav (scroll-spy)
    Alpine.data('sectionNav', (ids) => ({
        active: ids[0],
        init() {
            const io = new IntersectionObserver(
                (entries) => {
                    const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                    if (visible[0]) this.active = visible[0].target.id;
                },
                { rootMargin: '-120px 0px -60% 0px' },
            );
            ids.forEach((id) => {
                const el = document.getElementById(id);
                if (el) io.observe(el);
            });
            this._io = io;
        },
        destroy() {
            this._io?.disconnect();
        },
        go(id) {
            this.active = id;
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // keep the active pill visible in the horizontal scroller
            this.$nextTick(() => this.$el.querySelector(`[data-target="${id}"]`)?.scrollIntoView({ inline: 'center', block: 'nearest' }));
        },
    }));
}

function compact(v, indian) {
    const n = Number(v);
    const a = Math.abs(n);
    if (indian) {
        if (a >= 1e7) return +(n / 1e7).toFixed(1) + 'Cr';
        if (a >= 1e5) return +(n / 1e5).toFixed(1) + 'L';
    } else {
        if (a >= 1e9) return +(n / 1e9).toFixed(1) + 'B';
        if (a >= 1e6) return +(n / 1e6).toFixed(1) + 'M';
    }
    if (a >= 1e3) return +(n / 1e3).toFixed(1) + 'k';
    return n;
}
