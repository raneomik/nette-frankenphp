class XCarousel extends HTMLElement {
    #ac!: AbortController;
    #observers: (ResizeObserver | MutationObserver)[] = [];
    #positions: number[] = [];
    track!: HTMLElement;
    prev!: HTMLButtonElement | null;
    next!: HTMLButtonElement | null;
    dots!: HTMLElement | null;
    #tipSlots: HTMLElement[] = []; // tooltip on wide screens, inline panel on small ones
    #tipIndex = -1;

    connectedCallback(): void {
        this.#ac = new AbortController();
        const signal = this.#ac.signal;
        const track = this.querySelector<HTMLElement>('.carousel');
        if (!track) return reportError(new Error('x-carousel: .carousel element not found'));
        this.track = track;
        this.prev = this.querySelector<HTMLButtonElement>('[data-prev]');
        this.next = this.querySelector<HTMLButtonElement>('[data-next]');
        this.dots = this.querySelector<HTMLElement>('[data-dots]');
        this.#tipSlots = [...this.querySelectorAll<HTMLElement>('[data-tip-slot]')];
        this.#tipIndex = -1;

        this.prev?.addEventListener('click', () => this.go(-1), {signal});
        this.next?.addEventListener('click', () => this.go(1), {signal});
        this.track.addEventListener('scroll', () => this.#update(), {passive: true, signal});
        this.track.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.go(-1);
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.go(1);
            }
        }, {signal});

        const ro = new ResizeObserver(() => this.#measure());
        ro.observe(this.track);
        const mo = new MutationObserver(() => this.#measure());
        mo.observe(this.track, {childList: true});
        this.#observers = [ro, mo];

        this.#measure();
    }

    disconnectedCallback() {
        this.#ac.abort();
        this.#observers.forEach((o) => o.disconnect());
    }

    go(dir: number): void {
        const count = this.#positions.length;
        this.#scrollTo((this.#index() + dir + count) % count); // loop: after the last one, back to the first
    }

    // scrollLeft to reach for each item, honouring its scroll-snap-align
    #measure() {
        const track = this.track;
        const max = track.scrollWidth - track.clientWidth;
        const origin = track.getBoundingClientRect().left - track.scrollLeft;

        const positions = [...track.querySelectorAll(':scope > .carousel-item')].map((item) => {
            const r = item.getBoundingClientRect();
            const align = getComputedStyle(item).scrollSnapAlign;
            const shift = align.includes('center') ? (track.clientWidth - r.width) / 2
                : align.includes('end') ? track.clientWidth - r.width
                    : 0;
            return Math.round(Math.min(max, Math.max(0, r.left - origin - shift)));
        });

        this.#positions = [...new Set(positions)]; // trailing items stuck at the same scroll position merge
        this.#renderDots();
        this.#update();
    }

    #renderDots() {
        if (!this.dots) return;
        const count = this.#positions.length;
        this.dots.hidden = count < 2;
        this.dots.replaceChildren(...this.#positions.map((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'size-2.5 rounded-full bg-base-content/20 transition '
                + 'aria-[current=true]:scale-125 aria-[current=true]:bg-primary';
            dot.setAttribute('aria-label', `Position ${i + 1} of ${count}`);
            dot.addEventListener('click', () => this.#scrollTo(i));
            return dot;
        }));
    }

    #index() {
        const x = this.track.scrollLeft;
        return this.#positions.reduce((best, p, i) =>
            Math.abs(p - x) < Math.abs(this.#positions[best] - x) ? i : best, 0);
    }

    #scrollTo(i: number) {
        const left = this.#positions[i];
        if (left !== undefined) this.track.scrollTo({left, behavior: 'smooth'});
    }

    #update() {
        const i = this.#index();
        const single = this.#positions.length < 2; // when looping, the arrows only disappear if there is nothing to scroll
        if (this.prev) this.prev.disabled = single;
        if (this.next) this.next.disabled = single;
        this.dots?.querySelectorAll('button').forEach((dot, j) =>
            dot.setAttribute('aria-current', String(j === i)));
        this.#syncTip(i);
    }

    // shortcut: assumes one position per card (full-width cards); if trailing cards merge in #positions, the tooltip follows the wrong one
    #syncTip(i: number) {
        if (!this.#tipSlots.length || i === this.#tipIndex) return; // #update runs on every scroll: only copy on change
        this.#tipIndex = i;
        const tpl = this.track
            .querySelectorAll(':scope > .carousel-item')[i]
            ?.querySelector<HTMLTemplateElement>('template[data-tip]');
        for (const slot of this.#tipSlots) slot.replaceChildren(...(tpl ? [tpl.content.cloneNode(true)] : []));
    }
}

customElements.define('x-carousel', XCarousel);
