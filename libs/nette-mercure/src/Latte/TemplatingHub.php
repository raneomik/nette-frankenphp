<?php

/*
 * This file is part of the Mercure Component project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nette\Mercure\Latte;

use Latte\Engine;
use Nette\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\HubInterface as SymfonyHubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

final class TemplatingHub implements HubInterface
{
	public function __construct(
		private SymfonyHubInterface $hub,
		private Engine $latte = new Engine(),
	) {}

	public function getFactory(): ?TokenFactoryInterface
	{
		return $this->hub->getFactory();
	}

	public function getPublicUrl(): string
	{
		return $this->hub->getPublicUrl();
	}

	public function publish(
		Update $update,
		?string $template = null,
		null|object|array $templateData = null
	): string {
		if (null !== $template && null !== $templateData) {
			$renderedData = $this->latte->renderToString(
				$template,
				$templateData,
			);

			$update = new Update(
				$update->getTopics(),
				$renderedData,
				$update->isPrivate(),
				$update->getId(),
				$update->getType(),
				$update->getRetry(),
			);
		}

		return $this->hub->publish($update);
	}
}
