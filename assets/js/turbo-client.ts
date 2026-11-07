import {EventSourcePolyfill, type MessageEvent as SseMessage, type Event as SseEvent} from 'event-source-polyfill';
import * as Turbo from '@hotwired/turbo';
Turbo.session.drive = false;

document.addEventListener('DOMContentLoaded', async () => {

    const turboContainers = document.querySelectorAll('[data-turbo-listen]') as NodeListOf<HTMLElement>;

    const subUrl = new URL('/subscribe', window.origin);
    turboContainers.forEach((container: HTMLElement) => {
        const topicToListenTo = container.dataset.turboListen as string;
        // "topics[]": with a repeated "topics", PHP keeps only the last value
        subUrl.searchParams.append('topics[]', topicToListenTo);
    });

    subscribe(subUrl)
});

async function subscribe(subUrl: URL) {
    await fetch(subUrl, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},

    })
        .then(response => response.json())
        .then(response => {
            const {jwt, subscribeUrl} = response;

            const dec = (s: string) => JSON.parse(
                atob(
                    s.replace(/-/g, '+').replace(/_/g, '/')
                )
            );
            const [h, p] = jwt.token.split('.');

            console.debug("Received jwt token. ExpiresAt: ", jwt.ttl);
            console.debug("Trying to connect to mercure: ", subscribeUrl, dec(h), dec(p));

            setupTurboClient(subscribeUrl, jwt.token);
        })
        .catch(error => {
            console.error("Error fetching data from Turbo endpoint:", error);
        });
}
function setupTurboClient(mercureUrl: string, mercureJWTToken: string) {
    const eventSource = new EventSourcePolyfill(mercureUrl, {
        headers: {
            'Authorization': `Bearer ${mercureJWTToken}`
        }
    });

    eventSource.addEventListener('turbo-stream', (event) => { // custom event name: the types only give a plain Event
        Turbo.renderStreamMessage((event as SseMessage).data);
    });

    // updates published before this point are lost (no history replay): pages refetch what they display
    eventSource.onopen = () => document.dispatchEvent(new Event('mercure:open'));

    eventSource.onerror = (event: SseEvent) => {
        console.error("Mercure connection error: ", event); // the polyfill types declare no error field

        Turbo.disconnectStreamSource(eventSource);
    }

    document.onclose = () => {
        console.info("Bye !");
        Turbo.disconnectStreamSource(eventSource);
        eventSource.close();
    };
}
