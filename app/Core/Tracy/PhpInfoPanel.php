<?php

declare(strict_types=1);

namespace App\Core\Tracy;

use Tracy;

final readonly class PhpInfoPanel implements Tracy\IBarPanel
{
	public function getTab(): string
	{
		return <<<HTML
            <span title="phpinfo()">ℹ️</span>
        HTML;
	}

	public function getPanel(): string
	{
		$html = <<<'HTML'
            %style%
            <div class="tracy-inner">
                <div class="tracy-inner-container phpinfo">
                    <h1>Php Info</h1>
                    %info%
                </div>
                </div>
            </div>
        HTML;

		ob_start();
		phpinfo();
		$info = ob_get_clean();

		$dom = new \DOMDocument();
		@$dom->loadHTML($info ?: '');

		$style = $dom->getElementsByTagName('style')->item(0);

		if (!$style) {
			$style = $dom->createElement('style');
			$dom->appendChild($style);
		}

		$style->nodeValue = $this->transformCss($style->nodeValue ?: '')
			. <<<'CSS'
                #tracy-debug .tracy-inner-container.phpinfo {
                    min-width: unset;
                }
                .phpinfo hr {
                    width: unset;
                }
            CSS;

		$mainDiv = $dom->getElementsByTagName('div')->item(0);

		foreach ($mainDiv?->getElementsByTagName('h1') ?? [] as $div) {
			$h2 = $dom->createElement('h2');
			$h2->nodeValue = $div->nodeValue ?: '';
			$div->parentElement?->replaceChild($h2, $div);
		}

		return str_replace(
			['%style%', '%info%'],
			[
				$dom->saveHTML($style) ?: '',
				$dom->saveHTML($mainDiv) ?: 'no info'
			],
			$html
		);
	}

	private function transformCss(string $css): string
	{
		$css = preg_replace('~\bbody\s*\{[^}]*\}~m', '', $css) ?: $css;
		$css = preg_replace('~@media[^{]*\{(?:[^{}]|\{[^}]*\})*\}~si', '', $css) ?: $css;
		$css = preg_replace_callback(
			'~(^|})\s*([^{@}][^{]*)\{~m',
			function ($m) {
				$selectors = array_map('trim', explode(',', $m[2]));
				$selectors = array_map(
					fn($s) => str_starts_with($s, '.phpinfo')
						? $s
						: '.phpinfo ' . $s,
					$selectors
				);
				return $m[1] . ' ' . implode(', ', $selectors) . ' {';
			},
			$css
		);

		return $css ?: '';
	}
}
