import { formatMoney, compactMoney, formatNumber } from './money';

/**
 * Client-side ROI & effort simulator. Runs entirely in the browser so sliders
 * respond instantly; the agency fee comes live from Livewire ($wire.monthlyInvestment)
 * so toggling services in the pricing section updates ROI too.
 *
 * Model (per month, at full ramp):
 *   organic traffic  = baseline x lift          (SEO / CRO uplift)
 *   paid traffic     = ad spend / CPC
 *   conversions      = traffic x conversion rate
 *   revenue          = conversions x AOV
 *   team hours       = base hours x (0.5 + 0.5 x spend / default spend)
 *   investment       = ad spend + agency fee (monthly equivalent of contract)
 *   ROI              = (revenue - current revenue - investment) / investment
 */
// Chart instance lives outside Alpine state: a reactive Proxy around Chart.js causes recursion errors.
export default (cfg) => {
    let chart = null;

    return {
    mode: 'budget', // 'budget' | 'output'
    currency: cfg.currency,
    tenure: Math.min(Math.max(cfg.tenure || 3, 3), 12),

    // Fixed assumptions configured by the agency
    baseline: cfg.baseline,
    cpc: Math.max(cfg.cpc, 0.01),
    lift: cfg.lift,
    baseHours: cfg.hours,
    defaultSpend: Math.max(cfg.spend, 1),
    hourlyRate: cfg.hourlyRate,

    // Client-adjustable inputs
    budget: cfg.spend,
    targetTraffic: Math.round(cfg.baseline * cfg.lift + cfg.spend / Math.max(cfg.cpc, 0.01)),
    cr: cfg.cr,
    aov: cfg.aov,



    init() {
        this.$nextTick(() => this.renderChart());
        this.$watch('mode', () => this.updateChart());
        ['budget', 'targetTraffic', 'cr', 'aov'].forEach((k) => this.$watch(k, () => this.updateChart()));
        this.$watch('$wire.monthlyInvestment', () => this.updateChart());
    },

    destroy() {
        chart?.destroy();
        chart = null;
    },

    get organicTraffic() {
        return this.baseline * this.lift;
    },
    get spend() {
        if (this.mode === 'budget') return Number(this.budget) || 0;
        return Math.max(0, Number(this.targetTraffic) - this.organicTraffic) * this.cpc;
    },
    get traffic() {
        if (this.mode === 'budget') return this.organicTraffic + this.spend / this.cpc;
        return Number(this.targetTraffic) || 0;
    },
    get conversions() {
        return this.traffic * (Number(this.cr) / 100);
    },
    get revenue() {
        return this.conversions * (Number(this.aov) || 0);
    },
    get currentRevenue() {
        return this.baseline * (Number(this.cr) / 100) / Math.max(this.lift, 1) * (Number(this.aov) || 0);
    },
    get hours() {
        return this.baseHours * (0.5 + 0.5 * (this.spend / this.defaultSpend));
    },
    get effortValue() {
        return this.hours * this.hourlyRate;
    },
    get agencyFee() {
        return Number(this.$wire.monthlyInvestment) || 0;
    },
    get investment() {
        return this.spend + this.agencyFee;
    },
    get incremental() {
        return this.revenue - this.currentRevenue;
    },
    get roi() {
        return this.investment > 0 ? ((this.incremental - this.investment) / this.investment) * 100 : 0;
    },
    get roas() {
        return this.spend > 0 ? this.revenue / this.spend : 0;
    },

    // ----- formatting helpers for the template
    money(v) {
        return formatMoney(v, this.currency);
    },
    num(v) {
        return formatNumber(v);
    },

    // ----- monthly projection: SEO lift ramps over 3 months, paid ramps from 70%
    series() {
        const labels = [];
        const current = [];
        const projected = [];
        const invest = [];
        for (let m = 1; m <= this.tenure; m++) {
            const liftRamp = 1 + (this.lift - 1) * Math.min(1, m / 3);
            const paidRamp = Math.min(1, 0.7 + 0.15 * (m - 1));
            const paidTraffic = Math.max(0, this.traffic - this.organicTraffic);
            const t = this.baseline * liftRamp + paidTraffic * paidRamp;
            labels.push('Month ' + m);
            current.push(Math.round(this.currentRevenue));
            projected.push(Math.round(t * (this.cr / 100) * this.aov));
            invest.push(Math.round(this.investment));
        }
        return { labels, current, projected, invest };
    },

    renderChart() {
        const s = this.series();
        const styles = getComputedStyle(this.$el);
        const accent = styles.getPropertyValue('--color-brand-accent').trim() || '#7C5CFF';
        const highlight = styles.getPropertyValue('--color-brand-highlight').trim() || '#E6499B';
        const currency = this.currency;

        chart = new window.Chart(this.$refs.canvas, {
            type: 'bar',
            data: {
                labels: s.labels,
                datasets: [
                    { label: 'Current revenue', data: s.current, backgroundColor: '#cbd5e1', borderRadius: 4, order: 2 },
                    { label: 'Projected revenue', data: s.projected, backgroundColor: accent, borderRadius: 4, order: 1 },
                    { label: 'Monthly investment', data: s.invest, type: 'line', borderColor: highlight, backgroundColor: highlight, borderWidth: 2, pointRadius: 3, tension: 0.2, order: 0 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 250 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${formatMoney(c.parsed.y, currency)}` } },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: (v) => compactMoney(v, currency) }, grid: { color: '#eef0f4' } },
                },
            },
        });
    },

    updateChart() {
        if (!chart) return;
        const s = this.series();
        chart.data.datasets[0].data = s.current;
        chart.data.datasets[1].data = s.projected;
        chart.data.datasets[2].data = s.invest;
        chart.update('none');
    },
};
};
