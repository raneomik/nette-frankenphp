<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use Raneomik\NetteMercure\BroadcasterInterface;
use Raneomik\NetteMercure\Core\Publish\Latte\TurboStreamAction;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private BroadcasterInterface $broadcaster,
	) {
    }

	public function renderDefault(): void
	{
		$this->broadcaster->broadcast(
			data: 'Hello with Turbo from hub one!',
			topics: ['another-test-topic'],
			template: 'test.test.latte',
			options: [
				'template' => 'test.stream.latte',
				'action' => TurboStreamAction::Append,
			],
		);

		$this->broadcaster->broadcast(
			data: 'Hello Mercure from hub two!',
			topics: ['test-topic'],
			template: 'test.latte',
			options: ['hub' => 'two'],
		);
	}
}
