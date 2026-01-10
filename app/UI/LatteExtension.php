<?php

declare(strict_types=1);

namespace App\UI;

use App\Core\Runner\RunnerType;
use Latte\Extension;

final class LatteExtension extends Extension
{
	public function __construct(
		private readonly RunnerType $runner,
		private readonly ?string $hotReloadUrl,
	) {}

	public function getFunctions(): array
	{
		return [
			'runnerName' => fn(): string => $this->runner->value,
			'runnerIconAsset' => fn(): string => $this->runner->faviconAsset(),
			'runnerImageAsset' => fn(): string => $this->runner->imageAsset(),
			'hotReloadUrl' => fn(): ?string => $this->hotReloadUrl,
		];
	}
}
