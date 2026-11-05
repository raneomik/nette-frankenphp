import './.build/tailwind.css'; // npm run tailwind

import './js/components/xtoast';
import './js/components/xcarousel';
import './js/components/xconfetti';
import './js/components/xtheme-switch';
import './js/mercure-client.js';
import './js/turbo-client.js';
import './js/turbo-user-rule.ts';

import naja from 'naja';
import netteForms from 'nette-forms';
import ClipboardJS from "clipboard";

netteForms.initOnLoad();
naja.initialize();

document.addEventListener('animationend', (e) => {
    e.animationName === 'toast-out' && e.target.remove();
});

// visual feedback: daisy tooltip forced open for 1.5 s (data-tip only exists during that time, otherwise it would show on hover)
// leaving the game: sendBeacon goes out even while the tab is closing
// shortcut: a crash or a tab killed on mobile sends nothing (cleanRooms catches up after 1 h); reliable presence = Mercure subscriptions API
addEventListener('pagehide', () => {
    const url = document.querySelector('[data-leave-url]')?.dataset.leaveUrl;
    url && navigator.sendBeacon(url);
});
// back navigation from the cache (bfcache): we left the room, reload to re-enter it
addEventListener('pageshow', (e) => e.persisted && document.querySelector('[data-leave-url]') && location.reload());

new ClipboardJS('.clipboard').on('success', ({trigger}) => {
    trigger.dataset.tip = trigger.dataset.copied ?? 'Copied!'; // text from the template: data-copied="…"
    trigger.classList.add('tooltip-open');
    setTimeout(() => { trigger.classList.remove('tooltip-open'); delete trigger.dataset.tip; }, 1500);
});
