const tags = `
	<meta name="frankenphp-hot-reload:url" content="${window.Tracy.hotReloadUrl}">
	<script src="https://cdn.jsdelivr.net/npm/frankenphp-hot-reload/+esm" type="module"></script>
`;

const title = document.getElementsByTagName('title')[0];
title.insertAdjacentHTML('afterend', tags);

