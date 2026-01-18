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

		try {
			$bootstrap = new Bootstrap();
			$handler = $bootstrap->isDebug()
				? $this->developmentHandler($bootstrap)
				: $this->productionHandler($bootstrap);
		} catch (\Throwable $e) {
			var_dump($e->getMessage());

			Debugger::exceptionHandler($e);
			frankenphp_finish_request();

			exit;
		}

		// @phpstan-ignore-next-line
		$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? self::MAX_REQUESTS);

		do {
			$keepRunning = frankenphp_handle_request($handler);
		} while ($keepRunning && (-1 === $maxRequests || 0 < --$maxRequests));
	}


	private function productionHandler(Bootstrap $bootstrap): \Closure
	{
		$container = $bootstrap->bootWebApplication(false);
		return static function () use ($container): void {

			$container->initialize();
			$application = $container->getByType(Application::class);

			$application->onShutdown[] = function (): void {
				gc_collect_cycles();
				frankenphp_finish_request();
			};

			$application->run();
		};
	}

	private function developmentHandler(Bootstrap $bootstrap): \Closure
	{
		return static function () use ($bootstrap): void {
			try {
				$container = $bootstrap->bootWebApplication();
			} catch (\Throwable $e) {
				Debugger::exceptionHandler($e);

				frankenphp_finish_request();

				throw $e;
			}

			$application = $container->getByType(Application::class);
			$application->onError[] = function (Application $application, \Throwable $e): void {
				Debugger::exceptionHandler($e);
				exit(255);
			};

			$application->onShutdown[] = function (): void {
				gc_collect_cycles();
				exit(0);
			};

			$application->run();
		};
	}
}
