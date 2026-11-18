<?php

declare(strict_types=1);

namespace App\Core;

use Contributte\Translation\Translator;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
	public static function createRouter(Translator $translator): \Nette\Routing\RouteList
    {
		// one URL per language, for search engines: "/fr/...", "/cs/..."; the default language has no prefix
		$locale = \sprintf('[<locale=%s %s>/]', $translator->getDefaultLocale(), implode('|', $translator->getLocalesWhitelist() ?? []));

		$router = new RouteList();
		$router->addRoute('health', 'Health:default');
		$router
		    ->withModule('RockPaperScissors') // adds the module's list to $router
		    ->addRoute($locale . 'rock-paper-scissors[/<room>]/<action>', 'Base:default')
		    ->end();
		$router->addRoute($locale . '<presenter>/<action>[/<id>]', 'Home:default');

		return $router;
	}
}
