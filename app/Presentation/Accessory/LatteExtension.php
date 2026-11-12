<?php

declare(strict_types=1);

namespace App\Presentation\Accessory;

use App\Core\Runner\RunnerType;
use Contributte\Translation\Translator;
use Latte\Extension;

final class LatteExtension extends Extension
{
	public function __construct(
	    private readonly RunnerType $runner,
	    private readonly Translator $translator,
	) {
    }

	public function getFunctions(): array
	{
		return [
		    'runnerName' => fn(): string => $this->runner->value,
		    'runnerIconAsset' => $this->runner->faviconAsset(...),
		    'runnerImageAsset' => $this->runner->imageAsset(...),
		    'locale' => $this->translator->getLocale(...),
		    'locales' => fn(): array => $this->translator->getLocalesWhitelist() ?? [],
		    'tRoot' => fn(string $layout): string => dirname(__DIR__) . '/' . $layout,
		];
	}
}
