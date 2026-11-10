import './.build/tailwind.css'; // npm run tailwind
import '@fontsource-variable/bricolage-grotesque'; // headings (--font-display)

import './js/components/xtoast';
import './js/components/xcarousel';
import './js/components/xconfetti';
import './js/components/xtheme-switch';
import './js/components/xclipboard';

import './js/mercure-client';
import './js/turbo-client';
import './js/turbo-user-rule';
import './js/mercure-fixes';
import './js/push-client';

import naja from 'naja';
import netteForms from 'nette-forms';

netteForms.initOnLoad();
naja.initialize();

