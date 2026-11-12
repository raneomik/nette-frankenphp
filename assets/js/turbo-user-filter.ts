const me = document.querySelector<HTMLMetaElement>('meta[name="user-id"]');

// Turbo ships no types: the event arrives as a plain Event, so its detail is typed here
document.addEventListener('turbo:before-stream-render', (e) => {
    const stream = (e as CustomEvent<{ newStream?: Element }>).detail?.newStream ?? e.target as Element;
    // one rendering per locale is published (Messenger): keep the page's one
    const lang = stream.getAttribute('data-lang');
    if (stream.getAttribute('data-sender') === me?.content || (lang && lang !== document.documentElement.lang)) {
        e.preventDefault();
    }
});
