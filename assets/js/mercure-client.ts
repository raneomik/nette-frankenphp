
document.addEventListener('DOMContentLoaded', () => {

	const mercureUrlHolder = document.querySelector('[data-mercure-url]') as null|HTMLElement;
	let mercureUrl = '';


	if (!mercureUrlHolder) {
		console.error("Mercure URL not found in data-mercure-url attribute.");
		return;
	}

	mercureUrl = mercureUrlHolder.dataset.mercureUrl as string;

	const eventSource = new EventSource(mercureUrl, {
        withCredentials: true
    });

	const placeholders = document.querySelectorAll("[data-mercure]");
	eventSource.onmessage = event => {
		for (const placeholder of placeholders) {
			placeholder.innerHTML = event.data;
		}
	}

	eventSource.onerror = event => {
		console.error("Mercure connection error:", event);
	}
});
