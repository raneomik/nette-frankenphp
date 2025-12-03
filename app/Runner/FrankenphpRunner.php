<?php declare(strict_types = 1);

namespace App\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final class FrankenphpRunner
{
    public function run(): void
    {
        ignore_user_abort(true);

		$container = new Bootstrap();

		$handler = static function () use ($container): void {
			// initialized container & application
			$application = $container
				->bootWebApplication()
				->getByType(Application::class)

			$application->onError[] = function (Application $application, \Throwable $e): void {
				// needed to render correct error pages
				Debugger::exceptionHandler($e);

				exit(255);
			};

			$application->onShutdown[] = function () use ($configurator): void {
				gc_collect_cycles();

				if ($configurator->isDebugMode()) {
					// needed to render tracy-bar
					exit(0);
				}

				frankenphp_finish_request();
			};

			$application->run();
		};

        $maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 20);

        do {
            $keepRunning = frankenphp_handle_request($handler);
        } while ($keepRunning && (-1 === $maxRequests || 0 < --$maxRequests));
    }
}
