<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{

	public static function createRouter(): RouteList
	{
		return (new RouteList)
			->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
	}
}
