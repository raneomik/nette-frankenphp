import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import php from 'highlight.js/lib/languages/php';
import yaml from 'highlight.js/lib/languages/yaml';
import xml from 'highlight.js/lib/languages/xml';
import latte from './hljs-latte';

// yaml -> neon & xml -> html
hljs.registerLanguage('yaml', yaml);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('php', php);
hljs.registerLanguage('latte', latte);

// retire l'indentation du template et les lignes vides autour, le bloc étant affiché en white-space: pre
function dedent(code: string): string {
    const lines = code.replace(/^\s*\n|\s+$/g, '').split('\n');
    const indent = Math.min(...lines.filter(line => line.trim()).map(line => line.search(/\S/)));
    return lines.map(line => line.slice(indent)).join('\n');
}

class XCode extends HTMLElement {
    connectedCallback(): void {
        const language = this.dataset.lang as string;
        if (!hljs.getLanguage(language))
            return;

        // textContent : le template échappe le code, le navigateur le décode
        this.innerHTML = hljs.highlight(dedent(this.textContent ?? ''), { language }).value;
    }
}
customElements.define('x-code', XCode);
