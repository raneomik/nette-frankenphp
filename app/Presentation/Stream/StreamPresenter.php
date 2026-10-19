<?php

declare(strict_types=1);

namespace App\Presentation\Stream;

use Nette;
use Nette\Application\Attributes\Parameter;
use Raneomik\NetteMercure\BroadcasterInterface;
use Raneomik\NetteMercure\Core\Publish\Latte\TurboStreamAction;

final class StreamPresenter extends Nette\Application\UI\Presenter
{
    #[Parameter]
    public ?string $action = null;

    public function __construct(
        private BroadcasterInterface $broadcaster,
    ) {
    }

	public function renderDefault(): void
	{
        $this->broadcaster->broadcast(
            topics: ['test-topic'],
            data: 'Hello Turbo!',
            options: [
                'action' => $this->action ?? TurboStreamAction::Update,
            ],
            template: 'stream.latte',
        );

        $this->redirect('Home:default');
	}
}
