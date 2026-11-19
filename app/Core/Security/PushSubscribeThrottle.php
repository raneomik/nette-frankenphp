<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Service\RateLimiter;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Raneomik\NettePWA\Action\SubscribeAction;

/**
 * Application::onRequest hook: nette-pwa serves /pwa/push/subscribe as a route callback (Nette:Micro),
 * there is no presenter of ours to put the limit in. Every call stores or rewrites a row:
 * without a limit, made-up endpoints (any URL on an accepted push service) could fill the database.
 */
final readonly class PushSubscribeThrottle
{
    /** subscribe, unsubscribe and browser rotations together, per client */
    private const int Limit = 20;

    private const int Window = 600;

    public function __construct(
        private IRequest $httpRequest,
        private RateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Application $application, Request $request): void
    {
        if ('Nette:Micro' !== $request->getPresenterName()
            || trim($this->httpRequest->getUrl()->getPathInfo(), '/') !== SubscribeAction::DefaultPath // shortcut: assumes pwa.push.subscribeAtPath keeps its default
        ) {
            return;
        }

        $this->rateLimiter->enforce('push-subscribe', self::Limit, self::Window);
    }
}
