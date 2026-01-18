<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final readonly class DefaultRunner
{
	public function run(): void
	{
		$bootstrap = new Bootstrap();
		$container = $bootstrap->bootWebApplication();
		$application = $container->getByType(Application::class);

		$application->onShutdown[] = function (): void {
			gc_collect_cycles();
		};

		if (false === $bootstrap->isDebug()) {
			// for benchmarking tool - removes tracy bar in debug mode
			$this->applyContentLengthHeader($application);
		}

		$application->run();
	}

	private function applyContentLengthHeader(Application $application): void
	{
		$application->onStartup[] = function (): void {
			ob_start('ob_gzhandler');
		};

		$application->onShutdown[] = function (Application $application, ?\Throwable $e = null): void {
			header('Content-Length: ' . ob_get_length()); // the length of the gzip'd content
			Debugger::removeOutputBuffers(null !== $e);
		};
	}
}
