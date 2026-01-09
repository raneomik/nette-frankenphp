<?php

declare(strict_types=1);

namespace App\Core\Runner;

enum RunnerType: string
{
	case Nette = 'nette';
	case Frankenphp = 'frankenphp';
	case Unknown = 'unknown';

	public function faviconAsset(): string
	{
		return 'favicon:' . $this->value;
	}

	public function imageAsset(): string
	{
		return 'img:' . $this->value;
	}

	public function supportsHotReload(): bool
	{
		return match ($this) {
			self::Frankenphp => true,
			default => false,
		};
	}
}
