<?php

declare(strict_types=1);

namespace App;

use App\Boot\MainPreset;
use Contributte\Bootstrap\ExtraConfigurator;
use Contributte\Nella\Boot\Bootloader;

final readonly class Bootstrap
{
	private Bootloader $bootloader;

	private function __construct()
	{
		$this->bootloader = Bootloader::create();
	}

	public static function load(): Bootloader
	{
		return (new self())->bootloader
			->use(MainPreset::create(__DIR__))
		;
	}

	public function boot(): ExtraConfigurator
	{
		return $this->bootloader->boot();
	}
}
