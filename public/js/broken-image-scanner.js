(() => {
    'use strict';
    const root = document.getElementById('image-scanner');
    if (!root) return;
    const get = id => document.getElementById(id);
    const start = get('scan-start');
    const pause = get('scan-pause');
    let state = JSON.parse(get('scan-initial-state').textContent);
    let running = false;
    let busy = false;
    let token = root.dataset.token;
    const number = n => Number(n || 0).toLocaleString();
    function render() {
        ['clean', 'cached', 'repaired', 'copied', 'missing', 'review'].forEach(key => { get(`count-${key}`).textContent = number(state[key]); });
        const paused = !running && !['idle', 'complete'].includes(state.phase);
        get('scan-activity').textContent = (paused ? 'Paused. ' : '') + (state.activity || 'Ready to scan every submission, across all years.');
        get('scan-check-label').textContent = `${number(state.checked)} / ${number(state.total)} records`;
        get('scan-check').value = state.phase === 'idle' ? 0 : state.total ? Math.min(100, state.checked / state.total * 100) : state.phase === 'complete' ? 100 : 0;
        const repair = get('scan-repair');
        if (state.phase === 'index') {
            repair.removeAttribute('value');
            get('scan-repair-label').textContent = 'Searching folders…';
        } else {
            repair.value = state.phase === 'complete' ? 100 : state.queued ? state.resolved / state.queued * 100 : 0;
            get('scan-repair-label').textContent = `${number(state.resolved)} / ${number(state.queued)} queued images`;
        }
        get('scan-files').textContent = `${number(state.indexed)} files indexed. ${number(state.queued)} images queued for recovery.`;
        if (state.phase === 'fill') {
            get('scan-fill').removeAttribute('value');
            get('scan-fill-label').textContent = `${number(state.filled_entries)} submissions checked`;
        } else {
            get('scan-fill').value = state.phase === 'complete' ? 100 : 0;
            get('scan-fill-label').textContent = state.phase === 'complete' ? `${number(state.copied)} copies saved` : 'Waiting';
        }
        start.querySelector('span').textContent = state.phase === 'complete' ? 'Scan for new or changed images' : state.phase === 'idle' ? 'Start scan & repair' : 'Resume scan';
        const results = get('scan-results');
        results.replaceChildren();
        (state.results || []).forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item px-0 text-break';
            const badge = document.createElement('span');
            badge.className = `badge mr-2 badge-${item.status === 'repaired' ? 'success' : item.status === 'missing' ? 'warning text-dark' : 'secondary'}`;
            badge.textContent = ({ missing: 'Hidden from gallery', review: 'Needs review', copied: 'Required photo filled', optional: 'Optional image missing' })[item.status] || 'Restored';
            li.append(badge, document.createTextNode(item.message));
            results.append(li);
        });
        get('scan-empty').hidden = (state.results || []).length > 0;
    }
    function controls() {
        start.disabled = running || busy;
        pause.hidden = !running;
        pause.disabled = !running;
    }
    async function run() {
        if (busy || running) return;
        running = true;
        get('scan-error').hidden = true;
        controls();
        let action = ['idle', 'complete'].includes(state.phase) ? 'start' : 'step';
        try {
            while (running) {
                busy = true;
                const body = new URLSearchParams({ action, [root.dataset.tokenName]: token });
                const response = await fetch(root.dataset.endpoint, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, body });
                const data = await response.json();
                if (data.csrf) token = data.csrf;
                if (!response.ok || data.error) throw new Error(data.error || 'The scan could not continue. Reload this page and resume.');
                state = data.state;
                render();
                busy = false;
                if (state.phase === 'complete') running = false;
                action = 'step';
                if (running) await new Promise(resolve => setTimeout(resolve, 150));
            }
        } catch (error) {
            const alert = get('scan-error');
            alert.textContent = `${error.message} Progress from completed batches is saved. Reload and resume if needed.`;
            alert.hidden = false;
        } finally {
            running = false;
            busy = false;
            controls();
        }
    }
    start.addEventListener('click', run);
    pause.addEventListener('click', () => {
        running = false;
        controls();
        get('scan-activity').textContent = 'Pausing after the current batch. Saved progress can be resumed later.';
    });
    render();
})();
