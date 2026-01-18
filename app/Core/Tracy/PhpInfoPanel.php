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
            <h1>Php Info</h1>
            <div class="tracy-inner phpinfo">
                <div class="tracy-inner-container">
                    %info%
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
                #tracy-debug .tracy-inner.phpinfo {
                    min-width: unset;
                }
                .phpinfo .tracy-inner-container hr {
                    width: unset;
                }
            CSS;

		/** @var \DOMElement $mainDiv */
		$mainDiv = $dom->getElementsByTagName('div')->item(0);

		$phphinfo = str_replace(
			['h1'],
			['h2'],
			$dom->saveHTML($mainDiv) ?: 'no info',
		);

		return str_replace(
			['%style%', '%info%'],
			[
				$dom->saveHTML($style) ?: '',
				$phphinfo,
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
