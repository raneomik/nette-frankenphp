<?php

declare(strict_types=1);

namespace App\Presentation\Subscribe;

use Nette;
use Nette\Application\Attributes\Parameter;
use Nette\Http\IResponse;
use Raneomik\NetteMercure\Core\Subscribe\AuthorizationInterface;
use Raneomik\NetteMercure\SubscriberInterface;

/**
 * Issues the subscriber JWT for the topics a page listens to (assets/js/turbo-client.js).
 * Only topics the visitor may read are granted: anyone could otherwise ask for "*" or for
 * another player's private "player/<id>" topic.
 */
final class SubscribePresenter extends Nette\Application\UI\Presenter
{
    /** topics readable by every visitor */
    private const array PublicTopics = ['chat', 'turbo-test-topic'];

    /** rooms are public: the lobby lists them */
    private const string RoomTopic = '~^room/[A-Za-z0-9-]{1,64}$~D';

    #[Parameter]
    public ?string $hub = null;

    #[Parameter]
    public string|array $topics = [];

    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly SubscriberInterface $subscriber,
    ) {
    }

    public function renderDefault(): void
    {
        if (! $this->isAjax()) {
            return;
        }

//        $this->authorization->setCookie($this->topics, hub: $this->hub);

        $topics = array_values(array_unique(array_filter((array) $this->topics, $this->isAllowed(...))));
        if ([] === $topics) {
            $this->error('No topic you may subscribe to.', IResponse::S403_Forbidden);
        }

        $this->sendJson(
            $this->subscriber->subscribe($this->hub, $topics),
        );
    }

    private function isAllowed(mixed $topic): bool
    {
        if (! \is_string($topic)) {
            return false;
        }

        $user = $this->getUser();

        return \in_array($topic, self::PublicTopics, true)
            || 1 === preg_match(self::RoomTopic, $topic)
            || ($user->isLoggedIn() && $topic === 'player/' . $user->getId());
    }
}
