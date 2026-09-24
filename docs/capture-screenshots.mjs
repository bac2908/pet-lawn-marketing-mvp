// Capture the local app with the installed browser; no npm packages required.
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { mkdir, mkdtemp, readFile, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const baseUrl = new URL(process.argv[2] ?? 'http://localhost:8000');
const leadId = process.env.SCREENSHOT_LEAD_ID ?? '1';
if (!/^[1-9]\d*$/.test(leadId)) throw new Error('SCREENSHOT_LEAD_ID must be a positive integer.');
if (!['localhost', '127.0.0.1', '[::1]'].includes(baseUrl.hostname)) {
    throw new Error('This script only captures the local demo app.');
}
const browserPath = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
].find(existsSync);
if (!browserPath) throw new Error('Microsoft Edge or Google Chrome is required.');
if (!(await fetch(baseUrl)).ok) throw new Error('Start the app and build Vite assets first.');

const docsDirectory = path.dirname(fileURLToPath(import.meta.url));
const outputDirectory = path.join(docsDirectory, 'images');
await mkdir(outputDirectory, { recursive: true });
const profileDirectory = await mkdtemp(path.join(tmpdir(), 'PetLawnReadme-'));
const browser = spawn(browserPath, [
    '--headless=new', '--hide-scrollbars', '--no-first-run',
    '--no-default-browser-check', '--disable-background-networking',
    '--disable-extensions', '--remote-debugging-address=127.0.0.1',
    '--remote-debugging-port=0', `--user-data-dir=${profileDirectory}`, 'about:blank',
], { windowsHide: true, stdio: 'ignore' });

const delay = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
let socket;
let send;
try {
    const portFile = path.join(profileDirectory, 'DevToolsActivePort');
    for (let attempt = 0; !existsSync(portFile); attempt++) {
        if (attempt >= 100 || browser.exitCode !== null) throw new Error('Browser did not start.');
        await delay(100);
    }
    const port = (await readFile(portFile, 'utf8')).split('\n')[0].trim();
    const target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json();
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', reject, { once: true });
    });

    let sequence = 0;
    const pending = new Map();
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        const request = pending.get(message.id);
        if (!request) return;
        clearTimeout(request.timeout);
        pending.delete(message.id);
        if (message.error) request.reject(new Error(message.error.message));
        else request.resolve(message.result);
    });
    send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timeout = setTimeout(() => {
            pending.delete(id);
            reject(new Error(`Browser command timed out: ${method}`));
        }, 15000);
        pending.set(id, { resolve, reject, timeout });
        socket.send(JSON.stringify({ id, method, params }));
    });
    await send('Page.enable');
    await send('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-reduced-motion', value: 'reduce' }],
    });

    const captures = [
        { file: 'landing-page.png', route: '/', width: 1440, height: 1130 },
        { file: 'lead-form.png', route: '/', width: 1440, height: 1100, selector: '#lead-form' },
        { file: 'dashboard.png', route: '/dashboard', width: 1600, height: 1000, fullPage: true },
        { file: 'dashboard-hot.png', route: '/dashboard?segment=HOT&sort=score_desc', width: 1600, height: 1000, fullPage: true },
        { file: 'lead-detail.png', route: `/leads/${leadId}`, width: 1440, height: 1000, fullPage: true },
        { file: 'lead-detail-mobile.png', route: `/leads/${leadId}`, width: 390, height: 844, fullPage: true },
        { file: 'lead-edit.png', route: `/leads/${leadId}/edit`, width: 1440, height: 1000, fullPage: true },
        { file: 'lead-edit-mobile.png', route: `/leads/${leadId}/edit`, width: 390, height: 844, fullPage: true },
        { file: 'lead-care.png', route: `/leads/${leadId}`, width: 1440, height: 1000, selector: '#lead-care' },
    ];
    const selectedFiles = process.argv.slice(3);
    const selectedCaptures = selectedFiles.length ? captures.filter(capture => selectedFiles.includes(capture.file)) : captures;
    if (!selectedCaptures.length) throw new Error('No matching screenshot name.');
    for (const capture of selectedCaptures) {
        await send('Emulation.setDeviceMetricsOverride', {
            width: capture.width, height: capture.height, deviceScaleFactor: 1, mobile: false,
        });
        const navigation = await send('Page.navigate', { url: new URL(capture.route, baseUrl).href });
        if (navigation.errorText) throw new Error(navigation.errorText);
        let ready = false;
        for (let attempt = 0; attempt < 100; attempt++) {
            const state = await send('Runtime.evaluate', {
                expression: 'document.readyState === "complete" && !!document.querySelector("main")', returnByValue: true,
            });
            if (state.result.value) { ready = true; break; }
            await delay(100);
        }
        if (!ready) throw new Error('Page did not finish loading.');
        const layout = await send('Runtime.evaluate', {
            expression: `(async () => {
                await document.fonts.ready;
                const element = ${capture.selector ? `document.querySelector(${JSON.stringify(capture.selector)})` : 'null'};
                if (element) element.scrollIntoView({ behavior: 'instant', block: 'start' });
                else window.scrollTo({ top: 0, behavior: 'instant' });
                await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                const box = element?.getBoundingClientRect();
                return box ? { y: box.top + window.scrollY, height: box.height } : null;
            })()`,
            awaitPromise: true, returnByValue: true,
        });
        if (layout.exceptionDetails) throw new Error('Could not measure screenshot area.');
        const metrics = await send('Page.getLayoutMetrics');
        const section = layout.result.value;
        const clip = {
            x: 0, y: section ? Math.floor(section.y) : 0, width: capture.width,
            height: Math.ceil(section?.height ?? (capture.fullPage ? metrics.cssContentSize.height : capture.height)), scale: 1,
        };
        const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip });
        await writeFile(path.join(outputDirectory, capture.file), Buffer.from(screenshot.data, 'base64'));
        console.log(`${capture.file}: ${clip.width} x ${clip.height}`);
    }
} finally {
    if (send && socket.readyState === WebSocket.OPEN) {
        await send('Browser.close').catch(() => {});
        socket.close();
    } else if (browser.exitCode === null) {
        browser.kill();
    }
}
