import { test } from 'node:test';
import assert from 'node:assert/strict';
import { notificationBell } from '../../resources/js/notification-bell.js';

function setup(responses) {
    const calls = [];
    const bell = notificationBell({ unreadCount: 2, unreadIds: ['a', 'b'], acknowledgeUrl: '/ack', refreshUrl: '/refresh',
        setTimer: () => 1, clearTimer: () => {},
        request: async (url, options) => {
            calls.push([url, options.method]);
            const response = responses.shift();
            if (response instanceof Error) throw response;
            return { ok: true, json: async () => response };
        } });
    return { bell, calls };
}
const state = (ids = [], opened = []) => ({ unread_count: ids.length, unread_ids: ids, opened_unread_ids: opened, html: ids.join(',') });

test('open acknowledges once, retains highlighting, and refresh leaves new arrivals unread', async () => {
    const { bell, calls } = setup([state([], ['a', 'b']), state(['c']), state([], ['c'])]);
    assert.equal(calls.length, 0);
    const pending = bell.setOpen(true);
    assert.equal(bell.unreadCount, 0);
    await pending;
    await bell.setOpen(true);
    assert.equal(calls.length, 1);
    assert.equal(bell.isHighlighted('a', false), true);
    await bell.refresh();
    assert.equal(bell.unreadCount, 1);
    assert.deepEqual(calls[1], ['/refresh', 'GET']);
    await bell.setOpen(false);
    assert.equal(bell.isHighlighted('a', false), false);
    await bell.setOpen(true);
    assert.deepEqual(bell.justOpenedUnreadIds, ['c']);
});

test('failed acknowledgement and refresh restore confirmed badge without closing', async () => {
    const { bell } = setup([new Error('offline'), new Error('offline')]);
    await bell.setOpen(true);
    assert.equal(bell.unreadCount, 2);
    assert.equal(bell.isOpen, true);
    assert.ok(bell.readError);
});

test('closing during acknowledgement cannot restore session highlighting', async () => {
    const { bell } = setup([state([], ['a', 'b'])]);
    const pending = bell.setOpen(true);
    bell.setOpen(false);
    await pending;
    assert.deepEqual(bell.justOpenedUnreadIds, []);
    assert.equal(bell.unreadCount, 0);
});

test('initialization only watches deliberate opens and does not acknowledge', () => {
    const { bell, calls } = setup([]);
    bell.$refs = { items: { innerHTML: '<a>Existing notification</a>' } };
    bell.$watch = (name) => assert.equal(name, 'notificationsOpen');
    bell.init();
    assert.equal(calls.length, 0);
    assert.equal(bell.itemsHtml, '<a>Existing notification</a>');
});

test('rapid close and reopen keeps only the newest acknowledgement state', async () => {
    const { bell, calls } = setup([state([], ['a', 'b']), state([], ['c'])]);
    bell.setOpen(true);
    bell.setOpen(false);
    await bell.setOpen(true);
    assert.equal(calls.length, 2);
    assert.deepEqual(bell.justOpenedUnreadIds, ['c']);
    assert.equal(bell.acknowledging, false);
});
