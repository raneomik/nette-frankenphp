type HoldReason = 'hover' | 'focus' | 'hidden';

class XToast extends HTMLElement {
    #ac!: AbortController;
    #anim?: Animation;
    #holds = new Set<HoldReason>();

    connectedCallback(): void {
        this.#ac = new AbortController();
        const signal = this.#ac.signal;
        this.querySelector('[data-dismiss]')?.addEventListener('click', () => this.dismiss(), {signal});

        const duration = Number(this.dataset.timeout ?? 5000);
        const bar = this.querySelector<HTMLElement>('[data-progress]');
        if (duration <= 0) {
            bar?.remove();
            return;
        }

        // the animation acts as the timer, even without a bar (KeyframeEffect with no target)
        const anim = this.#anim = new Animation(new KeyframeEffect(
            bar,
            bar ? [{transform: 'scaleX(1)'}, {transform: 'scaleX(0)'}] : [],
            {duration, easing: 'linear', fill: 'forwards'},
        ));
        anim.finished.then(() => this.dismiss(), () => {});

        const hold = (reason: HoldReason) => { this.#holds.add(reason); anim.pause(); };
        const release = (reason: HoldReason) => { this.#holds.delete(reason); if (!this.#holds.size) anim.play(); };

        this.addEventListener('mouseenter', () => hold('hover'), {signal});
        this.addEventListener('mouseleave', () => release('hover'), {signal});
        this.addEventListener('focusin', () => hold('focus'), {signal});
        this.addEventListener('focusout', () => release('focus'), {signal});

        // a toast pushed by Mercure while the tab is in the background waits until we come back
        const visibility = () => document.hidden ? hold('hidden') : release('hidden');
        document.addEventListener('visibilitychange', visibility, {signal});
        document.hidden ? hold('hidden') : anim.play();
    }

    disconnectedCallback(): void {
        this.#ac.abort();
        this.#anim?.cancel();
    }

    dismiss(): void {
        if (this.dataset.leaving) return;
        this.dataset.leaving = '1';
        this.#anim?.pause();
        this.animate(
            [{opacity: 1, transform: 'none'}, {opacity: 0, transform: 'translateX(1rem)'}],
            {duration: 200, easing: 'ease-in'},
        ).finished.then(() => this.remove());
    }
}
customElements.define('x-toast', XToast);

