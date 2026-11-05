// daisyUI theme-controller: the checked checkbox applies its value as theme; persisted across pages
class XThemeSwitch extends HTMLElement {
    connectedCallback(): void {
        const input = this.querySelector<HTMLInputElement>('input.theme-controller');
        if (!input) return;

        try {
            input.checked = localStorage.getItem('theme') === input.value;
        } catch { /* storage blocked: default theme */ }

        input.addEventListener('change', () => {
            // data-theme set by the inline <head> script would otherwise win over an unchecked box
            input.checked
                ? document.documentElement.dataset.theme = input.value
                : delete document.documentElement.dataset.theme;

            try {
                input.checked
                    ? localStorage.setItem('theme', input.value)
                    : localStorage.removeItem('theme');
            } catch { /* storage blocked: choice not remembered */ }
        });
    }
}

customElements.define('x-theme-switch', XThemeSwitch);
