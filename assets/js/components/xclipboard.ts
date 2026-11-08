import ClipboardJS from 'clipboard';

/** <x-clipboard data-clipboard-text="…" data-copied="…">: copies the text on click and shows a tooltip for 1.5 s */
class XClipboard extends HTMLElement {
    connectedCallback(): void {
        new ClipboardJS(this).on('success', (e: ClipboardJS.Event) => {
            const trigger = e.trigger as HTMLElement;

            trigger.dataset.tip = trigger.dataset.copied ?? 'Copied!'; // text from the template: data-copied="…"
            trigger.classList.add('tooltip-open');
            setTimeout(() => {
                trigger.classList.remove('tooltip-open');
                delete trigger.dataset.tip;
            }, 1500);
        });
    }
}

customElements.define('x-clipboard', XClipboard);
