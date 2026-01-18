<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Mercure\DI\Dependency;

use Nette\Mercure\Core\Discovery;

final readonly class PreflightHandler
{
	public function __construct(
		private Discovery $discovery,
		private string $hubName,
	) {}

	public function __invoke(): void
	{
		$this->discovery->addLink($this->hubName);
	}
}
