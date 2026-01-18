
document.addEventListener('DOMContentLoaded', () => {

	const mercureUrlPlaceholder = document.querySelector('[data-mercure-url]');
	let mercureUrl = '';


	if (!mercureUrlPlaceholder) {
		console.error("Mercure URL not found in data-mercure-url attribute.");
		return;
	}

	mercureUrl = mercureUrlPlaceholder.dataset.mercureUrl;

	const eventSource = new EventSource(mercureUrl);

	const placeholders = document.querySelectorAll("[data-mercure]");
	eventSource.onmessage = event => {
		for (const placeholder of placeholders) {
			placeholder.textContent = event.data;
		}
	}

	eventSource.onerror = event => {
		console.error("Mercure connection error:", event);
	}
});
