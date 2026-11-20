<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Model\Exception\TooManyRequestsException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

/**
 * Fixed-window counters in APCu, keyed by client IP (IPv6: its /64, what one subscriber usually gets).
 *
 * APCu is shared by every thread of the FrankenPHP process, not between containers: during a
 * docker rollout the two instances count separately, which only doubles the limit for a few seconds.
 * shortcut: fails open without APCu (CLI without apc.enable_cli, tests): a limiter must not take the app down
 */
final readonly class RateLimiter
{
    public function __construct(
        private IRequest $request,
        private IResponse $response,
    ) {
    }

    /**
     * @param string|null $key null = the client (IP)
     * @throws TooManyRequestsException 429 with Retry-After, rendered by the 4xx error presenter
     */
    public function enforce(string $bucket, int $limit, int $window, ?string $key = null): void
    {
        $retryAfter = $this->hit($bucket, $key ?? $this->client(), $limit, $window);
        if (null === $retryAfter) {
            return;
        }

        $this->response->setHeader('Retry-After', (string) $retryAfter);

        throw new TooManyRequestsException($retryAfter);
    }

    /**
     * @return int|null seconds left in the window once the limit is exceeded, null = allowed
     */
    public function hit(string $bucket, string $key, int $limit, int $window): ?int
    {
        if (! \function_exists('apcu_enabled') || ! apcu_enabled()) {
            return null;
        }

        $windowStart = intdiv(time(), $window) * $window;
        $id = \sprintf('ratelimit:%s:%s:%d', $bucket, hash('xxh3', $key), $windowStart);

        apcu_add($id, 0, $window + 1); // no-op if the counter exists: atomic, unlike fetch + store
        $count = apcu_inc($id);
        if (false === $count) { // evicted in between (cache full): let it through
            return null;
        }

        return $count > $limit ? max(1, $windowStart + $window - time()) : null;
    }

    private function client(): string
    {
        // the real client behind caddy-docker-proxy: http.proxy (TRUSTED_PROXIES) in config/common.neon
        $ip = $this->request->getRemoteAddress() ?? 'unknown';

        $packed = str_contains($ip, ':') ? @inet_pton($ip) : false;

        return false !== $packed && 16 === \strlen($packed)
            ? bin2hex(substr($packed, 0, 8)) . '::/64'
            : $ip;
    }
}
