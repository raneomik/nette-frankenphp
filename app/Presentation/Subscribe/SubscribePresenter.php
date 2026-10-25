<?php

declare(strict_types=1);

namespace App\Presentation\Subscribe;

use Nette;
use Nette\Application\Attributes\Parameter;
use Raneomik\NetteMercure\Core\Subscribe\AuthorizationInterface;
use Raneomik\NetteMercure\SubscriberInterface;

final class SubscribePresenter extends Nette\Application\UI\Presenter
{

    #[Parameter]
    public ?string $hub = null;

    #[Parameter]
    public string|array $topics = ['*'];


    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly SubscriberInterface $subscriber,
    ) {
    }

    public function renderDefault(): void
    {
        if (!$this->isAjax()) {
            return;
        }

//        $this->authorization->setCookie($this->topics, hub: $this->hub);

        $this->sendJson(
            $this->subscriber->subscribe($this->hub, $this->topics),
        );
    }
}
