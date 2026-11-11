<?php

declare(strict_types=1);

namespace App\Presentation\Subscribe;

use App\Model\Service\TopicAccess;
use Nette;
use Nette\Application\Attributes\Parameter;
use Nette\Http\IResponse;
use Raneomik\NetteMercure\SubscriberInterface;

/**
 * Issues the subscriber JWT for the topics a page listens to (assets/js/turbo-client.js):
 * only those the visitor may read (TopicAccess).
 */
final class SubscribePresenter extends Nette\Application\UI\Presenter
{
    #[Parameter]
    public ?string $hub = null;

    /**
     * @var string|array<mixed> one topic or several, as sent: filtered by renderDefault()
     */
    #[Parameter]
    public string|array $topics = [];

    public function __construct(
        private readonly SubscriberInterface $subscriber,
        private readonly TopicAccess $topicAccess,
    ) {
    }

    public function renderDefault(): void
    {
        if (! $this->isAjax()) {
            return;
        }

        $topics = array_values(array_unique(array_filter((array) $this->topics, fn (mixed $topic): bool => \is_string($topic) && $this->topicAccess->isAllowed($topic))));
        if ([] === $topics) {
            $this->error('No topic you may subscribe to.', IResponse::S403_Forbidden);
        }

        $this->sendJson(
            $this->subscriber->subscribe($topics, $this->hub),
        );
    }
}
