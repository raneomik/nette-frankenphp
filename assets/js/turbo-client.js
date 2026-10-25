import {EventSourcePolyfill} from 'event-source-polyfill';
import * as Turbo from '@hotwired/turbo';
Turbo.session.drive = false;

document.addEventListener('DOMContentLoaded', async () => {

    const turboContainer = document.querySelector('[data-turbo-listen]');
    if (!turboContainer) {
        return;
    }

    let mercureTopics = turboContainer.dataset.turboListen;

    const subUrl = new URL('/subscribe', window.origin);
    subUrl.searchParams.append('topics', mercureTopics);

    await fetch(subUrl, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},

    })
        .then(response => response.json())
        .then(response => {
            const {jwtToken, subscribeUrl, expiresAt} = response;

            const dec = s => JSON.parse(atob(s.replace(/-/g,'+').replace(/_/g,'/')));
            const [h, p] = jwtToken.split('.');

            console.debug("Received jwt token. ExpiresAt: ", expiresAt);
            console.debug("Trying to connect to mercure: ", subscribeUrl, dec(h), dec(p));

            setupTurboClient(subscribeUrl, jwtToken);
        })
        .catch(error => {
            console.error("Error fetching data from Turbo endpoint:", error);
        });
});

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
