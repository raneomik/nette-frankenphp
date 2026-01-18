<?php

declare(strict_types=1);

namespace Nette\Mercure\Latte;

use Latte\Extension;
use Nette\Mercure\Tracy\MercurePanel;
use Symfony\Component\Mercure\HubRegistry;
use Tracy;

final class MercureExtension extends Extension
{
	public function __construct(
		private readonly HubRegistry $hubRegistry,
	) {
		Tracy\Debugger::getBar()->addPanel(
			new MercurePanel($hubRegistry)
		);
		Tracy\Debugger::$customJsFiles[] = dirname(__DIR__, 2) . '/Tracy/dist/hotReload.js';
	}

	public function getFunctions(): array
	{
		return [
			'mercure' => $this->mercure(...),
		];
	}

	/**
	 * @param string|string[]|null                                                                                                                       $topics  A topic or an array of topics to subscribe for. If this parameter is omitted or `null` is passed, the URL of the hub will be returned (useful for publishing in JavaScript).
	 * @param array{subscribe?: string[]|string, publish?: string[]|string, additionalClaims?: array<string, mixed>, lastEventId?: string, hub?: string} $options The options to pass to the JWT factory
	 *
	 * @return string The URL of the hub with the appropriate "topic" query parameters (if any)
	 */
	private function mercure(string|array|null $topics = null, array $options = []): string
	{
		$hub = $options['hub'] ?? null;
		$url = $this->hubRegistry->getHub($hub)->getPublicUrl();
		if (null !== $topics) {
			// We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
			$separator = '?';
			foreach ((array) $topics as $topic) {
				$url .= $separator . 'topic=' . rawurlencode($topic);
				if ('?' === $separator) {
					$separator = '&';
				}
			}
		}

		if ('' !== ($options['lastEventId'] ?? '')) {
			$encodedLastEventId = rawurlencode($options['lastEventId']);
			$url .= "&lastEventID=$encodedLastEventId";
		}

		return $url;
	}
}
