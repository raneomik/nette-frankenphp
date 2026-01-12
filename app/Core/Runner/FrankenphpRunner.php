<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final readonly class FrankenphpRunner
{
	private const MAX_REQUESTS = 20;

	public function run(): void
	{
		ignore_user_abort(true);

		$handler = static function (): void {
			try { // handle errors during boot
				// needed to reset state between requests, specially for Tracy
				$bootstrap = new Bootstrap();

				$container = $bootstrap->bootWebApplication();
				$application = $container->getByType(Application::class);
			} catch (\Throwable $e) {
				Debugger::exceptionHandler($e);

				frankenphp_finish_request();

				return;
			}

			$application->onError[] = function (Application $application, \Throwable $e) use ($container): void {
				if ($container->getParameters()['debugMode'] ?? false) {
					Debugger::exceptionHandler($e);
					exit(255);
				}
			};

			$application->onShutdown[] = function () use ($container): void {
				gc_collect_cycles();

				if ($container->getParameters()['debugMode'] ?? false) {
					// needed to dump tracy-bar
					exit(0);
				}

				frankenphp_finish_request();
			};

			$application->run();
		};

		// @phpstan-ignore-next-line
		$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? self::MAX_REQUESTS);

		do {
			$keepRunning = frankenphp_handle_request($handler);
		} while ($keepRunning && (-1 === $maxRequests || 0 < --$maxRequests));
	}
}
