import './.build/tailwind.css'; // npm run tailwind

import './js/components/xtoast';
import './js/components/xcarousel';
import './js/components/xconfetti';
import './js/components/xtheme-switch';

import './js/mercure-client';
import './js/turbo-client';
import './js/turbo-user-rule';
import './js/mercure-fixes';

import naja from 'naja';
import netteForms from 'nette-forms';
import ClipboardJS from "clipboard";

netteForms.initOnLoad();
naja.initialize();

new ClipboardJS('.clipboard').on('success', ({trigger}) => {
    trigger.dataset.tip = trigger.dataset.copied ?? 'Copied!'; // text from the template: data-copied="…"
    trigger.classList.add('tooltip-open');
    setTimeout(() => { trigger.classList.remove('tooltip-open'); delete trigger.dataset.tip; }, 1500);
});
