import './.build/tailwind.css'; // npm run tailwind

import './js/mercure-client.js';
import './js/turbo-client.js';
import naja from 'naja';
import netteForms from 'nette-forms';

netteForms.initOnLoad();
naja.initialize();
