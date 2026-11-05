<?php

namespace App\Model\Service;

use Raneomik\NetteMercure\BroadcasterInterface;
use Raneomik\NetteMercure\Core\Publish\Latte\TurboStreamAction;

final readonly class Messenger
{
    public function __construct(
        private BroadcasterInterface $broadcaster,
    ) {
    }

    public function toAll(string $room, string $template, array $params): void
    {
        $this->publish("room/{$room}", $template, $params + [
            'sender' => null,
        ]);
    }

    public function toOthers(string $room, string $senderId, string $template, array $params): void
    {
        $this->publish("room/{$room}", $template, [
            'sender' => $senderId,
        ] + $params);
    }

    public function toPlayer(string $playerId, string $template, array $params): void
    {
        $this->publish("player/{$playerId}", $template, $params + [
            'sender' => null,
        ], private: true);
    }

    private function publish(string $topic, string $template, array $params, bool $private = false): void
    {
        $options = [
            'private' => $private,
            $params['action'] ?? TurboStreamAction::Append,
        ];

        $this->broadcaster->broadcast(
            $topic,
            $params,
            $options,
            $template,
        );
    }
}
