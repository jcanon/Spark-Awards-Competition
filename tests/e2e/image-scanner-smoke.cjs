// Run with node; resolve playwright from installed dependencies (or NODE_PATH).
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const root = path.resolve(__dirname, '../..');
(async () => {
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    try {
        const page = await browser.newPage();
        const html = execFileSync('php', [path.join(root, 'tests/_support/render_image_scanner.php')], { encoding: 'utf8' });
        let calls = 0;
        let fail = false;
        await page.route('http://scanner.test/**', async route => {
            const request = route.request();
            const pathname = new URL(request.url()).pathname;
            if (pathname.endsWith('/step')) {
                calls++;
                assert.ok(request.postData().includes('csrf_test_name='));
                await new Promise(resolve => setTimeout(resolve, 80));
                if (fail) return route.fulfill({ status: 409, json: { error: 'Test retryable error', csrf: 'new-token' } });
                const phase = calls === 1 ? 'scan' : calls === 2 ? 'index' : calls === 3 ? 'repair' : calls === 4 ? 'fill' : 'complete';
                return route.fulfill({ json: { csrf: 'new-token', state: {
                    phase, total: 4, checked: calls === 1 ? 0 : 4, queued: 2, clean: 1, cached: 1,
                    repaired: phase === 'complete' ? 1 : 0, missing: phase === 'complete' ? 1 : 0,
                    copied: phase === 'complete' ? 2 : 0,
                    resolved: phase === 'complete' ? 2 : 0, indexed: calls >= 3 ? 12 : 0,
                    activity: `Working: ${phase}`, results: phase === 'complete' ? [
                        { status: 'repaired', message: 'Submission e1: Restored /uploads/2020/mobile/a.png' },
                        { status: 'missing', message: 'Submission e2: <script>unsafe</script> Hidden from gallery.' },
                        { status: 'copied', message: 'Submission e3: Photo 1 filled with a copy of Photo 2.' }
                    ] : []
                } } });
            }
            if (pathname === '/css/sb-admin-2.css' || pathname === '/js/broken-image-scanner.js') {
                return route.fulfill({ contentType: pathname.endsWith('.css') ? 'text/css' : 'application/javascript', body: fs.readFileSync(path.join(root, 'public', pathname), 'utf8') });
            }
            return route.fulfill({ contentType: 'text/html', body: html });
        });
        await page.goto('http://scanner.test/');
        for (const width of [375, 768, 1440]) {
            await page.setViewportSize({ width, height: 1100 });
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `Overflow at ${width}`);
        }
        await page.locator('#scan-start').focus();
        await page.keyboard.press('Enter');
        await page.locator('#scan-pause').click();
        await page.waitForFunction(() => !document.getElementById('scan-start').disabled);
        const pausedCalls = calls;
        await page.waitForTimeout(350);
        assert.equal(calls, pausedCalls, 'No requests after pause');
        await page.locator('#scan-start').click();
        await page.waitForFunction(() => document.getElementById('count-repaired').textContent === '1');
        assert.equal(await page.locator('#scan-results li').count(), 3);
        assert.equal(await page.locator('#count-copied').textContent(), '2');
        assert.equal(await page.locator('#scan-fill').getAttribute('value'), '100');
        assert.equal(await page.locator('#scan-results script').count(), 0);
        assert.equal(await page.locator('#scan-repair').getAttribute('value'), '100');
        await page.screenshot({ path: path.join(process.env.TEMP, 'broken-image-scanner-desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 375, height: 1000 });
        await page.screenshot({ path: path.join(process.env.TEMP, 'broken-image-scanner-mobile.png'), fullPage: true });
        fail = true;
        await page.locator('#scan-start').click();
        await page.waitForFunction(() => !document.getElementById('scan-error').hidden);
        assert.match(await page.locator('#scan-error').textContent(), /Progress from completed batches is saved/);
        assert.equal(await page.locator('#scan-start').isEnabled(), true);
        console.log('PASS: responsive layout, keyboard start, pause/resume, completion, safe results, error recovery.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
