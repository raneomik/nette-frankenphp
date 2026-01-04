<?php declare(strict_types = 1);

namespace App\Runner;

use App\Bootstrap;
use Nette\Application\Application;

final class NetteRunner
{
    public function run(): void
    {
		$bootstrap = new Bootstrap();
		$bootstrap->initializeEnvironment();

		$application = $bootstrap
			->bootWebApplication()
			->getByType(Application::class)
		;

		$application->onShutdown[] = function (): void {
			gc_collect_cycles();
		};

		$application->run();
	}
}
