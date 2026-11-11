<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{
	public static function createRouter(): \Nette\Routing\RouteList
    {
		$router = new RouteList();
		$router->addRoute('health', 'Health:default');
		$router
		    ->withModule('RockPaperScissors') // adds the module's list to $router
		    ->addRoute('rock-paper-scissors[/<room>]/<action>', 'Base:default')
		    ->end();
		$router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');

		return $router;
	}
}
