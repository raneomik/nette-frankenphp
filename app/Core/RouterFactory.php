<?php

declare(strict_types=1);

namespace App\Core;

use Contributte\Translation\Translator;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
	public static function createRouter(Translator $translator): \Nette\Routing\RouteList
    {
		$router = new RouteList();
		$router
		    ->addRoute('health', 'Health:default')
		    ->addRoute('robots.txt', 'Seo:robots')
		    ->addRoute('sitemap.xml', 'Seo:sitemap');

        // one URL per language, for search engines: "/fr/...", "/cs/..."; the default language has no prefix
        $locale = \sprintf('[<locale=%s %s>/]', $translator->getDefaultLocale(), implode('|', $translator->getLocalesWhitelist() ?? []));

        // room ids: same shape as the Mercure topics TopicAccess allows (room/<id>);
		// anything else is a 404, so no arbitrary string ends up as a row in SQLite
		$router
		    ->withModule('RockPaperScissors') // adds the module's list to $router
		    ->addRoute($locale . 'rock-paper-scissors[/<room [A-Za-z0-9-]{1,64}>]/<action>', 'Base:default')
		    ->end();
		$router->addRoute($locale . '<presenter>/<action>[/<id>]', 'Home:default');

		return $router;
	}
}
