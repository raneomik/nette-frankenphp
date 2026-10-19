import {EventSourcePolyfill} from 'event-source-polyfill';
import * as Turbo from '@hotwired/turbo';

document.addEventListener('DOMContentLoaded', async () => {

    const turboContainer = document.querySelector('[data-turbo-listen]');
    if (!turboContainer) {
        return;
    }

    let mercureUrl;
    let mercureTopics = turboContainer.dataset.turboListen;

    const subUrl = new URL('/subscribe', window.origin);
    subUrl.searchParams.append('topics', mercureTopics);

    await fetch(subUrl, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},

    })
        .then(response => {
            mercureUrl = new URL(getHubUrl(response), window.origin);
            mercureUrl.searchParams.append('match', mercureTopics)

            return response.json()
        })
        .then(data => {
            console.debug("Received jwt token. ExpiresAt: ", data.expiresAt);

            setupTurboClient(mercureUrl, data.jwtToken);

        })
        .catch(error => {
            console.error("Error fetching data from Turbo endpoint:", error);
        });
});

function setupTurboClient(mercureUrl, mercureJWTToken) {
    const dec = s => JSON.parse(atob(s.replace(/-/g,'+').replace(/_/g,'/')));
    const [h, p] = mercureJWTToken.split('.');

    console.debug("Trying to connect to mercure: ", mercureUrl, dec(h), dec(p));
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

const getHubUrl = (resp) => {
    const link = resp.headers.get("Link");
    if (!link) {
        throw new Error('No rel="mercure" Link header provided.');
    }

    return link.match(/<(.*)>.*rel="mercure".*/)[1];
};
