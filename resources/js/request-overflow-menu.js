export const requestOverflowMenu = (requestId, duplicateModeUrl, reportUrl) => ({
    requestId,
    duplicateModeUrl,
    reportUrl,
    open: false,
    reportOpen: false,
    top: 0,
    left: 0,
    _outsideHandler: null,
    _otherMenuHandler: null,
    _scrollHandler: null,

    init() {
        this._otherMenuHandler = (event) => {
            if (event.detail?.requestId !== this.requestId) this.close(false);
        };
        this._outsideHandler = (event) => {
            if (!this.open || this.$refs.trigger?.contains(event.target) || this.$refs.menu?.contains(event.target)) return;
            this.close(false);
        };
        this._scrollHandler = () => this.close(false);
        window.addEventListener('gmj:request-overflow-open', this._otherMenuHandler);
        document.addEventListener('pointerdown', this._outsideHandler);
        window.addEventListener('scroll', this._scrollHandler, true);
        window.addEventListener('resize', this._scrollHandler);
    },

    destroy() {
        window.removeEventListener('gmj:request-overflow-open', this._otherMenuHandler);
        document.removeEventListener('pointerdown', this._outsideHandler);
        window.removeEventListener('scroll', this._scrollHandler, true);
        window.removeEventListener('resize', this._scrollHandler);
    },

    toggle() {
        if (this.open) return this.close(false);
        window.dispatchEvent(new CustomEvent('gmj:request-overflow-open', { detail: { requestId: this.requestId } }));
        this.position();
        this.open = true;
        this.$nextTick(() => this.$refs.action?.focus());
    },

    position() {
        const rect = this.$refs.trigger.getBoundingClientRect();
        const width = Math.min(240, window.innerWidth - 16);
        this.left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
        this.top = rect.bottom + 8;
        if (this.top + 56 > window.innerHeight) this.top = Math.max(8, rect.top - 56);
    },

    close(returnFocus = false) {
        if (!this.open) return;
        this.open = false;
        if (returnFocus) this.$nextTick(() => this.$refs.trigger?.focus());
    },

    beginDuplicateMode() {
        this.close(false);
        window.location.assign(this.duplicateModeUrl);
    },

    signIn(url) {
        this.close(false);
        window.location.assign(url);
    },

    openReport() {
        this.close(false);
        this.reportOpen = true;
        this.$nextTick(() => this.$refs.reason?.focus());
    },

    closeReport() {
        if (!this.reportOpen) return;
        this.reportOpen = false;
        this.$nextTick(() => this.$refs.trigger?.focus());
    },

    escape() {
        if (this.reportOpen) this.closeReport();
        else this.close(true);
    },
});
