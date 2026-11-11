<?php

declare(strict_types=1);

namespace App\Model\Service;

use Nette\Security\User;
use Raneomik\NettePWA\Push\TopicAuthorizer;

/**
 * Which topics the visitor may listen to: Mercure subscriber JWT (SubscribePresenter) and Web Push
 * (nette-pwa). Anyone could otherwise ask for "*" or for another player's private "player/<id>" topic.
 */
final readonly class TopicAccess implements TopicAuthorizer
{
    /**
     * topics readable by every visitor
     */
    private const array PublicTopics = ['chat', 'turbo-test-topic', 'room/lobby'];

    /**
     * rooms are public: the lobby lists them
     */
    private const string RoomTopic = '~^room/[A-Za-z0-9-]{1,64}$~D';

    public function __construct(
        private User $user,
    ) {
    }

    public function isAllowed(string $topic): bool
    {
        return \in_array($topic, self::PublicTopics, true)
            || 1 === preg_match(self::RoomTopic, $topic)
            || ($this->user->isLoggedIn() && $topic === 'player/' . $this->user->getId());
    }
}
