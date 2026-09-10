export function notificationBell({ unreadCount = 0, unreadIds = [], acknowledgeUrl, refreshUrl, csrfToken,
    request = (...args) => fetch(...args), setTimer = setTimeout, clearTimer = clearTimeout }) {
    return {
        unreadCount,
        canonicalUnreadCount: unreadCount,
        unreadIds,
        justOpenedUnreadIds: [],
        itemsHtml: '',
        isOpen: false,
        acknowledging: false,
        readError: '',
        openSession: 0,
        requestVersion: 0,
        pending: Promise.resolve(),
        timer: null,
        destroyed: false,
        init() {
            this.itemsHtml = this.$refs.items.innerHTML;
            this.$watch('notificationsOpen', (open) => this.setOpen(open));
        },
        destroy() {
            this.destroyed = true;
            this.requestVersion++;
            this.stopRefresh();
        },
        isHighlighted(id, unread) {
            return unread || this.justOpenedUnreadIds.includes(id);
        },
        async send(url, method = 'GET') {
            const response = await request(url, {
                method,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    ...(method === 'POST' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });
            if (!response.ok) throw new Error('Unable to update notifications.');
            return response.json();
        },
        applyState(data) {
            this.unreadCount = data.unread_count;
            this.canonicalUnreadCount = data.unread_count;
            this.unreadIds = data.unread_ids;
            // Avoid replacing focused links when a poll returns identical markup.
            if (this.itemsHtml !== data.html) this.itemsHtml = data.html;
        },
        setOpen(open) {
            if (open === this.isOpen || this.destroyed) return this.pending;
            this.isOpen = open;
            const session = ++this.openSession;
            this.stopRefresh();
            if (!open) {
                this.justOpenedUnreadIds = [];
                return this.pending;
            }

            const version = ++this.requestVersion;
            this.justOpenedUnreadIds = [...this.unreadIds];
            this.unreadCount = 0;
            this.readError = '';
            this.acknowledging = true;
            // Serialize rapid close/reopen actions so older responses cannot win.
            this.pending = this.pending.then(() => this.send(acknowledgeUrl, 'POST')).then((data) => {
                if (this.destroyed || version !== this.requestVersion) return;
                this.applyState(data);
                if (this.isOpen && session === this.openSession) {
                    this.justOpenedUnreadIds = data.opened_unread_ids;
                }
            }).catch(async () => {
                if (this.destroyed || version !== this.requestVersion) return;
                this.unreadCount = this.canonicalUnreadCount;
                this.readError = 'Could not update read state. You can still open notifications or use Mark all read.';
                try {
                    const data = await this.send(refreshUrl);
                    if (!this.destroyed && version === this.requestVersion) this.applyState(data);
                } catch {
                    // Keep the last confirmed count if the network is unavailable.
                }
            }).finally(() => {
                if (!this.destroyed && version === this.requestVersion) {
                    this.acknowledging = false;
                    this.scheduleRefresh();
                }
            });
            return this.pending;
        },
        async refresh() {
            if (this.destroyed || this.acknowledging) return;
            const version = ++this.requestVersion;
            try {
                const data = await this.send(refreshUrl);
                if (!this.destroyed && version === this.requestVersion) this.applyState(data);
            } catch {
                // A read-only refresh failure must not hide unread notifications.
            }
        },
        stopRefresh() {
            if (this.timer !== null) clearTimer(this.timer);
            this.timer = null;
        },
        scheduleRefresh() {
            this.stopRefresh();
            if (!this.isOpen || this.destroyed) return;
            this.timer = setTimer(async () => {
                await this.refresh();
                this.scheduleRefresh();
            }, 15000);
        },
    };
}
