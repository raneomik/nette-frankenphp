<?php

declare(strict_types=1);

namespace App\Core\Tracy;

use App\Core\Runner\RunnerType;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Tracy;

final readonly class RunnerPanel implements Tracy\IBarPanel
{
	public function __construct(
		private RunnerType $runner,
		private Registry $assets,
	) {}

	public function getTab(): string
	{
		return <<<HTML
			<span title="Runner">🏃‍♂️</span>
		HTML;
	}

	public function getPanel(): string
	{
		$runner = htmlspecialchars($this->runner->value);

		/** @var ImageAsset */
		$image = $this->assets->tryGetAsset('img:' . $runner);

		return <<<HTML
			<div class="tracy-inner">
			<div class="tracy-inner-container">
					<h1>Runner</h1>
					<div style="margin-top: 1rem;">
						<p>Current runner type is <strong>$runner</strong>.</p>
						<div style="margin-top: 1rem; max-width: 222px; max-height: 222px;">
						{$image->getImportElement()}
						</div>
					</div>
				</div>
			</div>
		HTML;
	}
}
