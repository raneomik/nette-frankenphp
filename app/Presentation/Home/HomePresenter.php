<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use Raneomik\NetteMercure\BroadcasterInterface;
use Raneomik\NetteMercure\Core\Publish\Latte\TurboStreamAction;
use Raneomik\NetteMercure\Core\Subscribe\AuthorizationInterface;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private BroadcasterInterface $broadcaster,
		private AuthorizationInterface $authorization,
	) {
    }

    protected function startup(): void
    {
        parent::startup();

        $this->authorization->setCookie(['test-topic']);

    }

	public function handleDispatch(string $type = 'plain'): void
	{
        if ('turbo' === $type) {
            $this->broadcaster->broadcast(
                topics: ['turbo-test-topic'],
                data: 'Hello with Turbo from Mercure!',
                options: [
                    'template' => 'test.stream.latte',
                    'target' => 'toast-container',
                    'action' => TurboStreamAction::Append,
                ],
            );

            return;
        }

        $this->broadcaster->broadcast(
            data: 'Hello from Mercure!',
            topics: ['test-topic'],
            template: 'test.latte',
        );
    }
}
