<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;

final readonly class GenericRunner
{
	public function run(): void
	{
		$bootstrap = new Bootstrap();
		$bootstrap->initializeEnvironment();

		$application = $bootstrap
			->bootWebApplication()
			->getByType(Application::class);

		$application->onShutdown[] = function (): void {
			gc_collect_cycles();
		};

		$application->run();
	}
}
