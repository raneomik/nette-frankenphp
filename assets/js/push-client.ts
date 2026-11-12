type Push = {
    buttons: HTMLButtonElement[],
    key: string,
    topics: string[],
    locale: string,
    subscribeUrl: string,
    swUrl: string,
};

const meta = (name: string) => document.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)?.content;

document.addEventListener('DOMContentLoaded', async () => {
    const buttons = [...document.querySelectorAll<HTMLButtonElement>('[data-pwa-push]')];
    const key = meta('pwa-vapid-key');
    const subscribeUrl = meta('pwa-subscribe-url');

    if (0 >= buttons.length) {
        return;
    }

    if (Notification.permission === 'denied') {
        buttons.forEach((button) => disable(button, button.dataset.pwaBlocked ?? '🔕 Notifications blocked'));
        return;
    }

    if (!key || !subscribeUrl || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        // navigator.standalone exists only on iOS: there, Web Push needs the installed app
        const install = 'standalone' in navigator && !navigator.standalone;
        buttons.forEach((button) => install && key
            ? disable(button, button.dataset.pwaInstall ?? '📲 Add to Home Screen to get notifications')
            : button.hidden = true)
        ;
        return;
    }

    const push: Push = {
        buttons,
        key,
        topics: [...new Set(buttons.flatMap((button) => button.dataset.pwaPush?.split(/\s+/).filter(Boolean) ?? []))],
        locale: document.documentElement.lang,
        subscribeUrl,
        // the service worker reads its subscribe URL from its own query string (pushsubscriptionchange)
        swUrl: `${meta('pwa-sw-url') ?? '/sw.js'}?subscribe=${encodeURIComponent(subscribeUrl)}`,
    };

    buttons.forEach((button) => {
        button.dataset.pwaOff ??= button.textContent;
        button.addEventListener('click', () => button.getAttribute('aria-pressed') === 'true' ? unsubscribe(push) : subscribe(push));
    });

    // already on: refresh silently (the topics may have changed, e.g. a new session).
    // Granted but no subscription = the visitor turned it off: do not subscribe again behind their back.
    const registration = await navigator.serviceWorker.getRegistration();
    const current = await registration?.pushManager.getSubscription();
    if (Notification.permission === 'granted' && current) {
        await subscribe(push);
    } else {
        buttons.forEach((button) => toggle(button, false));
    }
});

async function subscribe(push: Push) {
    if (await Notification.requestPermission() !== 'granted') {
        push.buttons.forEach((button) => disable(button, button.dataset.pwaBlocked ?? '🔕 Notifications blocked'));
        return;
    }

    try {
        await navigator.serviceWorker.register(push.swUrl);
        const registration = await navigator.serviceWorker.ready; // subscribe() needs an active worker
        const subscription = await registration.pushManager.getSubscription()
            ?? await registration.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: base64UrlToBytes(push.key)});

        await send(push.subscribeUrl, 'POST', {subscription, topics: push.topics, locale: push.locale});
        push.buttons.forEach((button) => toggle(button, true));
    } catch (error) {
        console.error(error);
        push.buttons.forEach((button) => disable(button, button.dataset.pwaError ?? '🔕 Notifications unavailable'));
    }
}

async function unsubscribe(push: Push) {
    try {
        const registration = await navigator.serviceWorker.getRegistration();
        const subscription = await registration?.pushManager.getSubscription();
        if (subscription) {
            // server first: if the browser unsubscribed first and the request failed, the row would stay until a 410
            await send(push.subscribeUrl, 'DELETE', {endpoint: subscription.endpoint});
            await subscription.unsubscribe();
        }
        push.buttons.forEach((button) => toggle(button, false));
    } catch (error) {
        console.error(error);
        push.buttons.forEach((button) => disable(button, button.dataset.pwaError ?? '🔕 Notifications unavailable'));
    }
}

async function send(url: string, method: 'PUT'|'DELETE'|'POST', body: object) {
    const response = await fetch(url, {
        method,
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify(body),
    });
    if (!response.ok) {
        throw new Error(`Push subscription ${method} refused: ${response.status}`);
    }
}

function toggle(button: HTMLButtonElement, on: boolean) {
    button.textContent = on
        ? (button.dataset.pwaOn ?? '🔔 Notifications on')
        : button.dataset.pwaOff ?? '🔕 Notifications off';
    button.setAttribute('aria-pressed', String(on));
    button.disabled = false;
}

function disable(button: HTMLButtonElement, label: string) {
    button.textContent = label;
    button.removeAttribute('aria-pressed');
    button.disabled = true;
}

function base64UrlToBytes(value: string) {
    const base64 = (value + '='.repeat((4 - value.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
}
