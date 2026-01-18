<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use Nette\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	private readonly HubInterface $hubOne;
	private readonly HubInterface $hubTwo;

	public function __construct(
		private readonly HubRegistry $hubs,
	) {
		$this->hubOne = $this->hubs->getHub('one');
		$this->hubTwo = $this->hubs->getHub('two');
	}

	public function renderDefault(): void
	{
		$this->hubOne->publish(
			new Update(
				'test-topic',
				'Hello, Mercure!'
			)
		);
		$this->hubTwo
			->publish(
				new Update('test-topic'),
				__DIR__ . '/test.stream.latte',
				['text' => 'Hello from Mercure!']
			);
	}
}
