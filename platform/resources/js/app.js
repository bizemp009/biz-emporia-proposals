import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Chart from 'chart.js/auto';
import 'trix';
import 'trix/dist/trix.css';

import roiCalculator from './components/roi-calculator';
import signaturePad from './components/signature-pad';
import richEditor from './components/rich-editor';
import { registerAdminUi } from './components/admin-ui';

window.Chart = Chart;

registerAdminUi(Alpine);

// Trix: no inline file attachments (screenshots go through the uploader instead).
document.addEventListener('trix-file-accept', (e) => e.preventDefault());

Alpine.data('roiCalculator', roiCalculator);
Alpine.data('signaturePad', signaturePad);
Alpine.data('richEditor', richEditor);

Alpine.data('copyLink', (url) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(url);
        } catch {
            const t = document.createElement('textarea');
            t.value = url;
            document.body.appendChild(t);
            t.select();
            document.execCommand('copy');
            t.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 1600);
    },
}));

Livewire.start();
