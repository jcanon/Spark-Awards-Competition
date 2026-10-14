const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.resolve(__dirname, '../../public/js/utils/bulk-actions.js'), 'utf8');

function setup({ fail = false, action = 'gallery_hide', confirm = true } = {}) {
    let listener;
    const field = { value: 'stale-token' };
    const submitted = [];
    const alerts = [];
    let requests = 0;
    let confirmations = 0;
    const form = {
        getAttribute: name => ({ 'data-action-selector': '#action', 'data-csrf-refresh-url': '/token' }[name] || null),
        elements: { namedItem: name => name === 'csrf' ? field : null },
        addEventListener: (name, callback) => { listener = callback; },
        setAttribute() {}, removeAttribute() {},
        requestSubmit(submitter) { submit(submitter); },
    };
    function submit(submitter) {
        const event = { submitter, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        listener(event);
        if (!event.defaultPrevented) submitted.push({ submitter, token: field.value });
    }
    const window = {
        confirm() { confirmations++; return confirm; },
        alert(message) { alerts.push(message); },
        async fetch() {
            requests++;
            if (fail) throw new Error('offline');
            return { ok: true, redirected: false, json: async () => ({ name: 'csrf', hash: 'fresh-token' }) };
        },
    };
    const document = {
        readyState: 'complete',
        querySelectorAll: () => [form],
        querySelector: () => ({ value: action }),
    };
    vm.runInNewContext(source, { window, document });
    return { submit, submitted, alerts, requests: () => requests, confirmations: () => confirmations };
}
const flush = () => new Promise(resolve => setImmediate(resolve));

test('refreshes stale token once and preserves the submit button', async () => {
    const state = setup();
    const button = { formaction: '/bulk' };
    state.submit(button);
    state.submit(button);
    assert.equal(state.submitted.length, 0);
    await flush();
    assert.equal(state.requests(), 1);
    assert.deepEqual(state.submitted, [{ submitter: button, token: 'fresh-token' }]);
});

test('failed refresh retains form and permits retry without posting', async () => {
    const state = setup({ fail: true });
    state.submit();
    await flush();
    state.submit();
    await flush();
    assert.equal(state.requests(), 2);
    assert.equal(state.submitted.length, 0);
    assert.equal(state.alerts.length, 2);
});

test('delete cancellation does not refresh or submit', async () => {
    const state = setup({ action: 'delete', confirm: false });
    state.submit();
    await flush();
    assert.equal(state.requests(), 0);
    assert.equal(state.submitted.length, 0);
});

test('confirmed delete asks only once when refreshed form is submitted', async () => {
    const state = setup({ action: 'delete' });
    state.submit();
    await flush();
    assert.equal(state.confirmations(), 1);
    assert.equal(state.submitted.length, 1);
});
