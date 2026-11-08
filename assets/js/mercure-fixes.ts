
// visual feedback: daisy tooltip forced open for 1.5 s (data-tip only exists during that time, otherwise it would show on hover)
// leaving the game: sendBeacon goes out even while the tab is closing
// shortcut: a crash or a tab killed on mobile sends nothing (cleanRooms catches up after 1 h); reliable presence = Mercure subscriptions API
addEventListener('pagehide', () => {
    const leaveUrlHolder = document.querySelector('[data-leave-url]') as null|HTMLElement;
    const url = leaveUrlHolder?.dataset.leaveUrl;

    url && navigator.sendBeacon(url);
});

// [data-refetch-on-connect][id]: content possibly published before Mercure connected (no history replay), refetched once connected
// (e.g. the lobby is rendered before the leave beacon of the game page just left is handled)
document.addEventListener('mercure:open', async () => {
    const stale = document.querySelectorAll('[data-refetch-on-connect][id]');
    if (!stale.length)
        return;

    const fresh = new DOMParser().parseFromString(await (await fetch(location.href)).text(), 'text/html');
    stale.forEach(el => el.innerHTML = fresh.getElementById(el.id)?.innerHTML ?? el.innerHTML); // innerHTML: wrappers (an open <details>) keep their state
});

// back navigation from the cache (bfcache): the game page re-enters its room, live pages get fresh content and Mercure connection
addEventListener('pageshow', (e: PageTransitionEvent) => e.persisted
    && document.querySelector('[data-leave-url], [data-refetch-on-connect]')
    && location.reload());
