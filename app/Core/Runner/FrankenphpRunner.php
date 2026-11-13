<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final readonly class FrankenphpRunner
{
    private const int MAX_REQUESTS = 1000;

    public function run(): void
    {
        ignore_user_abort(true);

        Debugger::$onFatalError[] = static fn (\Throwable $e) => Debugger::$productionMode ?: Debugger::log($e, Debugger::EXCEPTION);

        try {
            $bootstrap = new Bootstrap();
            $handler = $bootstrap->isDebug()
                ? $this->developmentHandler($bootstrap)
                : $this->productionHandler($bootstrap);
        } catch (\Throwable $e) {
            self::report($e); // Tracy may not be enabled yet: StderrLogger still writes its line
            frankenphp_finish_request();

            exit;
        }

        $maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? self::MAX_REQUESTS);

        do {
            $keepRunning = frankenphp_handle_request($handler);
        } while ($keepRunning && (-1 === $maxRequests || 0 < --$maxRequests));
    }

    private function productionHandler(Bootstrap $bootstrap): \Closure
    {
        $bootstrap->prepare();
        return static function () use ($bootstrap): void {
            try {
                $application = $bootstrap->createContainer()->getByType(Application::class);

                $application->onShutdown[] = function (): void {
                    gc_collect_cycles();
                    frankenphp_finish_request();
                };

                $application->run();
            } catch (\Throwable $e) {
                self::report($e);
            }
        };
    }

    /**
     * Logs an exception that left the request handler and answers 500.
     *
     * Nothing else would: FrankenPHP reports such an exception itself as an engine error, which
     * bypasses set_exception_handler() (so Tracy never sees it) and keeps the worker running (so
     * Tracy's shutdown handler does not run either), while Tracy::enable() has switched PHP's own
     * log_errors off. Result without this: a 500 with an empty body and not a single log line,
     * e.g. when the DI container cannot be compiled.
     */
    private static function report(\Throwable $e): void
    {
        $logFailure = Debugger::tryLog($e, Debugger::EXCEPTION);

        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        // Tracy's own page, rendered here: Debugger::exceptionHandler() shows it only for the
        // first exception of a process, and a worker serves many requests
        $template = Debugger::$errorTemplate
            ?? \dirname((string) new \ReflectionClass(Debugger::class)->getFileName()) . '/dist/error.500.phtml';
        (static fn (bool $logged) => require $template)(null === $logFailure);
    }

    private function developmentHandler(Bootstrap $bootstrap): \Closure
    {
        return static function () use ($bootstrap): void {
            try {
                $bootstrap->prepare(); // needed for correct tracy time&memory metrics
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
