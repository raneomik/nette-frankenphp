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

        try {
            $bootstrap = new Bootstrap();
            $handler = $bootstrap->isDebug()
                ? $this->developmentHandler($bootstrap)
                : $this->productionHandler($bootstrap);
        } catch (\Throwable $e) {
            Debugger::log($e);
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
        $bootstrap->prepare();
        return static function () use ($bootstrap): void {
            $application = $bootstrap->createContainer()->getByType(Application::class);

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
                $bootstrap->prepare(); // needed for tracy metrics
                $container = $bootstrap->createContainer();
            } catch (\Throwable $e) {
                Debugger::exceptionHandler($e);
                exit(0);
            }

            $application = $container->getByType(Application::class);
            $application->onError[] = function (Application $application, \Throwable $e): void {
                Debugger::exceptionHandler($e);
                exit(0);
            };

            $application->onShutdown[] = function (): void {
                gc_collect_cycles();
                exit(0);
            };

            $application->run();
        };
    }
}
