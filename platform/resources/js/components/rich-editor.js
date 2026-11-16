/**
 * Trix editor bound to a Livewire property.
 *
 *   <div wire:ignore x-data="richEditor('executiveSummary')">
 *       <input id="x" type="hidden" value="…"><trix-editor x-ref="trix" input="x"></trix-editor>
 *   </div>
 *
 * Edits are pushed to Livewire (deferred until the next request). When the server
 * replaces the content (e.g. a template is applied) it dispatches
 * `rich-text-load` with { field, html } and the editor reloads.
 */
export default (field) => ({
    init() {
        const editor = this.$refs.trix;

        editor.addEventListener('trix-change', () => {
            this.$wire.$set(field, editor.value, false);
        });

        this._onLoad = (e) => {
            if (e.detail?.field !== field) return;
            const apply = () => editor.editor.loadHTML(e.detail.html ?? '');
            editor.editor ? apply() : editor.addEventListener('trix-initialize', apply, { once: true });
        };
        window.addEventListener('rich-text-load', this._onLoad);
    },

    destroy() {
        window.removeEventListener('rich-text-load', this._onLoad);
    },
});
