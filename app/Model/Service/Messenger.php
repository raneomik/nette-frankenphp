<?php

namespace App\Model\Service;

use Contributte\Translation\Translator;
use Raneomik\NetteMercure\BroadcasterInterface;

final readonly class Messenger
{
    public function __construct(
        private BroadcasterInterface $broadcaster,
        private Translator $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toAll(string $room, string $template, array $params): void
    {
        $this->publish("room/{$room}", $template, $params + [
            'sender' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toOthers(string $room, string $senderId, string $template, array $params): void
    {
        $this->publish("room/{$room}", $template, [
            'sender' => $senderId,
        ] + $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toPlayer(string $playerId, string $template, array $params): void
    {
        $this->publish("player/{$playerId}", $template, $params + [
            'sender' => null,
        ], private: true);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function publish(string $topic, string $template, array $params, bool $private = false): void
    {
        $options = [
            'private' => $private,
        ];

        if ($params['action'] ?? false) {
            $options['action'] = $params['action'];
        }

        // the recipients' locales are unknown: one rendering per locale, each page keeps its own (data-lang, turbo-user-filter.ts)
        $current = $this->translator->getLocale();
        try {
            foreach ($this->translator->getLocalesWhitelist() ?? [$current] as $locale) {
                $this->translator->setLocale($locale);
                $this->broadcaster->broadcast(
                    $topic,
                    [
                        'lang' => $locale,
                    ] + $params,
                    $options,
                    $template,
                );
            }
        } finally {
            $this->translator->setLocale($current);
        }
    }
}
