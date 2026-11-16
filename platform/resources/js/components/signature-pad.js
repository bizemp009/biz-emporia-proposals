/** HTML5 canvas signature pad (mouse, pen and touch via Pointer Events). */
export default () => ({
    drawing: false,
    hasInk: false,
    ctx: null,
    last: null,

    init() {
        this.$nextTick(() => this.resize());
        this._onResize = () => this.resize(true);
        window.addEventListener('resize', this._onResize);
    },

    destroy() {
        window.removeEventListener('resize', this._onResize);
    },

    resize(preserve = false) {
        const canvas = this.$refs.canvas;
        if (!canvas) return;
        const snapshot = preserve && this.hasInk ? canvas.toDataURL() : null;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        this.ctx = canvas.getContext('2d');
        this.ctx.scale(ratio, ratio);
        this.ctx.lineWidth = 2.2;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.strokeStyle = '#111827';
        if (snapshot) {
            const img = new Image();
            img.onload = () => this.ctx.drawImage(img, 0, 0, rect.width, rect.height);
            img.src = snapshot;
        }
    },

    point(e) {
        const r = this.$refs.canvas.getBoundingClientRect();
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    },

    start(e) {
        e.preventDefault();
        this.$refs.canvas.setPointerCapture?.(e.pointerId);
        this.drawing = true;
        this.last = this.point(e);
        this.ctx.beginPath();
        this.ctx.arc(this.last.x, this.last.y, 1, 0, Math.PI * 2);
        this.ctx.fillStyle = '#111827';
        this.ctx.fill();
        this.hasInk = true;
    },

    move(e) {
        if (!this.drawing) return;
        e.preventDefault();
        const p = this.point(e);
        this.ctx.beginPath();
        this.ctx.moveTo(this.last.x, this.last.y);
        this.ctx.lineTo(p.x, p.y);
        this.ctx.stroke();
        this.last = p;
    },

    end() {
        this.drawing = false;
    },

    clear() {
        const c = this.$refs.canvas;
        this.ctx.clearRect(0, 0, c.width, c.height);
        this.hasInk = false;
    },

    async submit() {
        if (!this.hasInk) {
            this.$wire.signatureData = '';
        } else {
            this.$wire.signatureData = this.$refs.canvas.toDataURL('image/png');
        }
        await this.$wire.signProposal();
    },
});
