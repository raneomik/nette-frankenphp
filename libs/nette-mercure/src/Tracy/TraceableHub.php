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

use Nette\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\RemoteHubInterface;

/**
 * Traces updates for profiler.
 *
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class TraceableHub implements HubInterface, RemoteHubInterface
{
	private array $messages = [];

	public function __construct(
		private HubInterface $hub,
		private Metrics $metrics = new Metrics(),
	) {}

	public function getUrl(): string
	{
		if (method_exists($this->hub, 'getUrl')) {
			return $this->hub->getUrl();
		}

		throw new \RuntimeException('The getUrl() method is not implemented by the decorated hub.');
	}

	public function getPublicUrl(): string
	{
		return $this->hub->getPublicUrl();
	}

	public function getProvider(): TokenProviderInterface
	{
		if (method_exists($this->hub, 'getProvider')) {
			return $this->hub->getProvider();
		}

		throw new \RuntimeException('The getUrl() method is not implemented by the decorated hub.');
	}

	public function getFactory(): ?TokenFactoryInterface
	{
		return $this->hub->getFactory();
	}

	public function publish(
		Update $update,
		?string $template = null,
		null|object|array $templateData = null
	): string {
		$this->metrics->start(__CLASS__);
		$messageId = $this->hub->publish($update, $template, $templateData);
		$this->metrics->stop(__CLASS__);

		$this->messages[] = [
			'object' => new MessageData($update, $template, $templateData),
			'duration' => $this->metrics->getDuration(__CLASS__),
			'memory' => $this->metrics->getMemory(__CLASS__),
		];

		return $messageId;
	}

	public function reset(): void
	{
		$this->messages = [];
	}

	public function count(): int
	{
		return \count($this->messages);
	}

	public function getMessages(): array
	{
		return $this->messages;
	}

	public function getMessageObjects(): array
	{
		return array_column($this->messages, 'object');
	}

	public function getDuration(): float
	{
		return (float) array_sum(array_column($this->messages, 'duration'));
	}

	public function getMemory(): int
	{
		return (int) array_sum(array_column($this->messages, 'memory'));
	}
}
