const SYMBOLS = { INR: '₹', USD: '$', EUR: '€', GBP: '£', AUD: 'A$', CAD: 'C$', AED: 'AED ' };

export function formatMoney(amount, currency = 'INR') {
    const code = (currency || 'INR').toUpperCase();
    const symbol = SYMBOLS[code] ?? code + ' ';
    const locale = code === 'INR' ? 'en-IN' : 'en-US';
    const value = Math.round(Number(amount) || 0);
    return (value < 0 ? '-' : '') + symbol + Math.abs(value).toLocaleString(locale);
}

export function compactMoney(amount, currency = 'INR') {
    const code = (currency || 'INR').toUpperCase();
    const symbol = SYMBOLS[code] ?? code + ' ';
    const v = Math.abs(Number(amount) || 0);
    let out;
    if (code === 'INR') {
        out = v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'K' : v.toFixed(0);
    } else {
        out = v >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'K' : v.toFixed(0);
    }
    return (amount < 0 ? '-' : '') + symbol + out;
}

export function formatNumber(n) {
    return Math.round(Number(n) || 0).toLocaleString('en-US');
}
