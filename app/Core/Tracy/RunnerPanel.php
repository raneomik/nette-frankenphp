<?php

declare(strict_types=1);

namespace App\Core\Tracy;

use App\Core\Runner\RunnerType;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Tester\Runner\Runner;
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
		$image = $this->assets->tryGetAsset($this->runner->imageAsset());

		$additionalInfo = '';

		if (RunnerType::Frankenphp === $runner) {

			if (function_exists('frankenphp_get_status')) {
				$additionalInfo = sprintf('<p>%s</p>', htmlspecialchars(frankenphp_get_status()));
			}
		}

		return <<<HTML;
            <h1>Runner</h1>
            <div class="tracy-inner runner">
                <div class="tracy-inner-container">
                    <div style="margin-top: 1rem;">
                        <p>Current runner type is <strong>$runner</strong>.</p>
                        <div style="margin-top: 1rem; max-width: 222px; max-height: 222px;">
                        {$image->getImportElement()}
                        </div>
                        $additionalInfo
                </div>
            </div>
        HTML;
	}
}
