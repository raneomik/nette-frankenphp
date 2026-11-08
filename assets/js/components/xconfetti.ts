import confetti from 'canvas-confetti';

/** <x-confetti emoji="✌️">: fires a burst when inserted dynamically into the page */
class XConfetti extends HTMLElement {
    connectedCallback(): void {
        if (initialUpgrade || matchMedia('(prefers-reduced-motion: reduce)').matches)
            return;

        const emoji = this.getAttribute('emoji');
        const count = (this.getAttribute('count') ?? 60) as number;
        const spread = (this.getAttribute('spread') ?? 80) as number;
        const shapes = emoji ? [confetti.shapeFromText({text: emoji, scalar: 2})] : undefined;
        confetti({
            particleCount: count,
            spread: spread,
            origin: {y: 0.6},
            scalar: 2,
            ...(shapes && {shapes})
        });
    }
}

// <x-confetti> already in the initial HTML (page reload) are upgraded during define(): ignore them,
// only those inserted later (Naja snippet, Mercure stream) celebrate a round that just ended
let initialUpgrade = true;
customElements.define('x-confetti', XConfetti);
initialUpgrade = false;
