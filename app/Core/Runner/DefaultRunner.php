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
		$configurator = Bootstrap::load()->boot();
		$application = $configurator
			->createContainer()
			->getByType(Application::class);

		$application->onShutdown[] = function (): void {
			gc_collect_cycles();
		};

		if (false === $configurator->isDebugMode()) {
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
