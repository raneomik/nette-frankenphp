<?php

declare(strict_types = 1);

namespace App\UI;

use Latte\Extension;

class LatteExtension extends Extension
{
	public function __construct(
		private readonly string $runnerName,
	) {
	}

	public function getFunctions(): array
	{
		return [
			'runnerName' => fn() => $this->runnerName,
			'runnerIcon' => fn() => "/{$this->runnerName}.ico",
		];
	}
}
