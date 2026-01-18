<?php

declare(strict_types=1);

namespace Nette\Mercure\Tracy;

use Symfony\Component\Mercure\HubRegistry;
use Tracy;

final readonly class MercurePanel implements Tracy\IBarPanel
{
	private const ICON = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 147.5 144" fill="currentColor">
            <path d="M72 144c-39.7 0-72-32.3-72-72S32.3 0 72 0s72 32.3 72 72-32.3 72-72 72zM72 6.1C35.7 6.1 6.1 35.7 6.1 72s29.6 65.9 65.9 65.9 66-29.6 66-65.9S108.4 6.1 72 6.1z"/>
            <path d="M72 14.7a56.5 56.5 0 0 0-49.7 83.4c1.5-1.1 3.1-2.2 4.9-3.3 31-18.5 58.4-33.7 76.6-62 0 0-1.3 32-19.7 46.9-4.1 3.3-7.6 5.9-10.9 8 9.3-4.9 17.4-10.5 23.7-18 0 0-.5 21.5-16.3 31-5.9 3.5-10.8 5.5-15.8 7 9-2 14.9-4.5 19.6-7.7-4 13.3-12.7 20.3-26.2 22.3-4.9.5-9.1-.5-13.5-1.5 8.1 4.5 17.4 7 27.3 7 31.2 0 56.5-25.3 56.5-56.5S103.3 14.7 72 14.7z"/>
        </svg>
        SVG;

	public function __construct(
		private HubRegistry $traceableHubs,
	) {}

	public function getTab(): string
	{
		$icon = self::ICON;
		return <<<HTML
            <span title="Mercure">{$icon}</span>
        HTML;
	}

	public function getPanel(): string
	{
		$icon = self::ICON;

		return str_replace(
			'%info%',
			$this->discoveryData(),
			<<<HTML
            <style type="text/css">
                .mercure-panel p,
                .mercure-panel dt {
                    font-weight: bold;
                }

                .mercure-panel dl,
                .mercure-panel dd {
                    font-size: 0.9rem;
                }

                .mercure-panel dd {
                    margin-bottom: 1em;
                    margin-left: 2em;
                }

                .mercure-panel details summary {
                    margin-bottom: 1rem;
                    cursor: pointer;
                    position: relative;
                    anchor-name: --summary;

                    &::marker {
                        content: "";
                    }

                    &::before,
                    &::after {
                        content: "";
                        border-block-start: 3px solid #575753;
                        height: 0;
                        width: 1rem;

                        inset-block-start: 50%;
                        inset-inline-end: 0;

                        position: absolute;
                        position-anchor: --summary;
                        position-area: top start;
                    }

                    &::after {
                        transform: rotate(90deg);
                        transform-origin: 50%;
                    }
                }

                .mercure-panel details[open] summary::after {
                    transform: rotate(0deg);
                }
            </style>
            <div class="tracy-inner">
                <div class="tracy-inner-container mercure-panel">
                    <h1 style="display: flex; align-items: center; gap: 1rem;">
                        <span style="width: 32px;">{$icon}</span>
                        <span>Mercure</span>
                    </h1>
                    %info%
                </div>
                </div>
            </div>
        HTML
		);
	}

	private function discoveryData(): string
	{
		$totalDuration = 0;
		$totalMemory = 0;

		$messageHtml = <<<'HTML'
        <tr>
            <td>%type%</td>
            <td>%topics%</td>
            <td>%content%</td>
        </tr>
        HTML;

		$hubHtml = <<<'HTML'
        <details %open% name="mercure-hub">
            <summary>
                <h2>Hub %index%</h2>
            </summary>
            <div style="margin-top: 1rem;">
                <dl>
                    <dt>url</dt>
                    <dd>%hubUrl%</dd>
                    <dt>Sent Messages</dt>
                    <dd>
                        <table>
                            <colgroup>
                                <col style="width:20%">
                                <col style="width:20%">
                                <col style="width:60%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Type</th>
                                    <th style="text-align: center;">Topics</th>
                                    <th style="text-align: center;">Content</th>
                                </tr>
                            </thead>

                            <tbody>%messages%</tbody>

                            <tfoot>
                                <tr>
                                <th scope="row" colspan="2" style="text-align: right;">Total</th>
                                <td style="text-align: center;">%messagesCount%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </dd>

                    <dt>Duration</dt>
                    <dd>%duration%</dd>

                    <dt>Memory</dt>
                    <dd>%memory%</dd>
                </dl>
            </div>
        </details>
        HTML;

		$output = '';

		/**
		 * @var TraceableHub $hub
		 */
		foreach ($this->traceableHubs->all() as $index => $hub) {
			$totalDuration += $hubDuration = $hub->getDuration();
			$totalMemory += $hubMemory = $hub->getMemory();
			$sentMessages = $hub->getMessageObjects();
			$sentMessagesCount = $hub->count();

			$messageRow = array_map(fn(MessageData $message) => str_replace(
				['%topics%', '%type%', '%content%'],
				[
					htmlspecialchars((string) implode(',', $message->getTopics())),
					htmlspecialchars((string) $message->getType() ?? '-'),
					htmlspecialchars((string) $message->getData()),
				],
				$messageHtml,
			), $sentMessages);

			$output .= str_replace(
				[
					'%open%',
					'%hubUrl%',
					'%index%',
					'%messagesCount%',
					'%messages%',
					'%duration%',
					'%memory%',
				],
				[
					$this->traceableHubs->getHub() === $hub ? 'open' : '',
					htmlspecialchars($hub->getPublicUrl()),
					$index,
					(string) $sentMessagesCount,
					implode('', $messageRow),
					sprintf('%.2f ms', $hubDuration * 1000),
					sprintf('%.2f kB', $hubMemory / 1024),
				],
				$hubHtml
			);
		}

		$totalDurationText = sprintf('%.2f ms', $totalDuration * 1000);
		$totalMemoryText = sprintf('%.2f kB', $totalMemory / 1024);

		return <<<HTML
        <div>
            <div>
                <dl>
                    <dt></dt>
                    <dd>{$output}</dd>

                    <dt>Total Duration</dt>
                    <dd>{$totalDurationText}</dd>

                    <dt>Total Memory</dt>
                    <dd>{$totalMemoryText}</dd>
                </dl>
            </div>
        </div>
        HTML;
	}
}
