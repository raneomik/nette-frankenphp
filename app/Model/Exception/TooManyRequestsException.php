<?php

declare(strict_types=1);

namespace App\Model\Exception;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

final class TooManyRequestsException extends BadRequestException
{
    public function __construct(
        public readonly int $retryAfter,
    ) {
        parent::__construct(
            "Too many requests. Retry after {$retryAfter} seconds.",
            IResponse::S429_TooManyRequests,
        );
    }
}
