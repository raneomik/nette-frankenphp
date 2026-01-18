<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use Nette\Mercure\BroadcasterInterface;
use Nette\Mercure\Latte\TurboStream\Action;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private BroadcasterInterface $broadcaster,
	) {}

	public function renderDefault(): void
	{
		$this->broadcaster->broadcast(
			data: 'Hello, Mercure!',
			topics: ['test-topic'],
			template: 'test.latte',
			options: [
				'template' => 'test.stream.latte',
				'action' => Action::Append,
			],
		);
		$this->broadcaster->broadcast(
			data: 'Hello, Mercure!',
			topics: ['test-topic'],
			template: 'test.latte',
			options: ['hub' => 'two'],
		);
	}
}
