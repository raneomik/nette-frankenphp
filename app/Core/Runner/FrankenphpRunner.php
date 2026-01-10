<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final readonly class FrankenphpRunner
{
	private const int MAX_REQUESTS = 20;

	public function run(): void
	{
		ignore_user_abort(true);

		$bootloader = Bootstrap::load();

		$handler = static function () use ($bootloader): void {
			try { // handle errors during booting
				// needed to reset state between requests, specially for Tracy
				$configurator = $bootloader->boot();
				$application = $configurator->createContainer()
					->getByType(Application::class);
			} catch (\Throwable $e) {
				Debugger::exceptionHandler($e);

				frankenphp_finish_request();

				return;
			}

			$application->onShutdown[] = function (Application $application, ?\Throwable $e = null) use ($configurator): void {
				gc_collect_cycles();


				if ($configurator->isDebugMode()) {
					if (null !== $e) {
						Debugger::exceptionHandler($e);
					}

					// needed to render tracy-bar
					exit(0);
				}

				frankenphp_finish_request();
			};

			$application->run();
		};

		$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? self::MAX_REQUESTS);

		do {
			$keepRunning = frankenphp_handle_request($handler);
		} while ($keepRunning && (-1 === $maxRequests || 0 < --$maxRequests));
	}
}
