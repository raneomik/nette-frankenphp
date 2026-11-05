import {EventSourcePolyfill} from 'event-source-polyfill';
import * as Turbo from '@hotwired/turbo';
Turbo.session.drive = false;

document.addEventListener('DOMContentLoaded', async () => {

    const turboContainers = document.querySelectorAll('[data-turbo-listen]');

    const subUrl = new URL('/subscribe', window.origin);
    turboContainers.forEach(container => {
        // "topics[]": with a repeated "topics", PHP keeps only the last value
        subUrl.searchParams.append('topics[]', container.dataset.turboListen);
    });

    subscribe(subUrl)
});

async function subscribe(subUrl) {
    await fetch(subUrl, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},

    })
        .then(response => response.json())
        .then(response => {
            const {jwt, subscribeUrl} = response;

            const dec = s => JSON.parse(atob(s.replace(/-/g, '+').replace(/_/g, '/')));
            const [h, p] = jwt.token.split('.');

            console.debug("Received jwt token. ExpiresAt: ", jwt.ttl);
            console.debug("Trying to connect to mercure: ", subscribeUrl, dec(h), dec(p));

            setupTurboClient(subscribeUrl, jwt.token);
        })
        .catch(error => {
            console.error("Error fetching data from Turbo endpoint:", error);
        });
}
function setupTurboClient(mercureUrl, mercureJWTToken) {
    const eventSource = new EventSourcePolyfill(mercureUrl, {
        headers: {
            'Authorization': `Bearer ${mercureJWTToken}`
        }
    });

    eventSource.addEventListener('turbo-stream', event => {
        Turbo.renderStreamMessage(event.data);
    });

    eventSource.onerror = event => {
        console.error("Mercure connection error: ", event.error);

        Turbo.disconnectStreamSource(eventSource);
    }

    document.onclose = () => {
        console.info("Bye !");
        Turbo.disconnectStreamSource(eventSource);
        eventSource.close();
    };
}
