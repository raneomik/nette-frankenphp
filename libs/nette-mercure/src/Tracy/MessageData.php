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

namespace Nette\Mercure\Tracy;

use Symfony\Component\Mercure\Update;

final class MessageData
{
	private string $renderedData;

	public function __construct(
		private Update $update,
		private ?string $template = null,
		private null|object|array $templateData = null,
	) {
		if (null !== $template && null !== $templateData) {
			$this->renderedData = json_encode([
				'template' => $template,
				'data' => $templateData
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		} else {
			$this->renderedData = $update->getData();
		}
	}

	public function getTopics(): array
	{
		return $this->update->getTopics();
	}

	public function getType(): ?string
	{
		return $this->update->getType();
	}

	public function getData(): string
	{
		return $this->renderedData;
	}
}
