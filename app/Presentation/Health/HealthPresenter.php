<?php

declare(strict_types=1);

namespace App\Presentation\Health;

use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;

/**
 * GET /health: probe for the Docker healthcheck and the reverse proxy health checks.
 * No session, template or database: it only proves that a PHP worker answers.
 */
final readonly class HealthPresenter implements IPresenter
{
    public function __construct(
        private IResponse $httpResponse,
    ) {
    }

    public function run(Request $request): Response
    {
        $this->httpResponse->setHeader('Cache-Control', 'no-store');
        $this->httpResponse->setContentType('text/plain', 'utf-8');

        return new TextResponse('ok');
    }
}
