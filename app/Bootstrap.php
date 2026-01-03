<?php declare(strict_types = 1);

namespace App;

use App\Boot\LocalPreset;
use Contributte\Bootstrap\ExtraConfigurator;
use Contributte\Nella\Boot\Bootloader;

final class Bootstrap
{
	public static function boot(): ExtraConfigurator
	{
		return Bootloader::create()
			->use(LocalPreset::create(dirname(__DIR__)))
			->boot()
		;
	}
}
