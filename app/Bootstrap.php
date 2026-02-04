<?php

declare(strict_types=1);

namespace App;

use App\Core\Runner\RunnerType;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Loaders\RobotLoader;

final readonly class Bootstrap
{
	private string $rootDir;
	private bool $isDebug;

	public function __construct(
		private Configurator $configurator = new Configurator,
	) {
		$this->rootDir = dirname(__DIR__);
	}

	public function isDebug(): bool
	{
		return $this->isDebug ??= (bool) getenv('NETTE_DEBUG') ?: false;
	}

	public function bootWebApplication(bool $initializeContainer = true): Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();

		return $this->configurator->createContainer($initializeContainer);
	}

	public function initializeEnvironment(): void
	{
		$this->configurator
			->setDebugMode($this->isDebug())
			->setTempDirectory($this->rootDir . '/var/temp')

			->addDynamicParameters([
				'hotReloadUrl' => $_SERVER['FRANKENPHP_HOT_RELOAD'] ?? null,
			])
			->enableTracy($this->rootDir . '/var/log')
		;

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
	}

	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';

		$this->configurator
			->addConfig($configDir . '/common.neon')
			->addConfig($configDir . '/services.neon')
			->addDynamicParameters([
				'runner' => RunnerType::from(getenv('APP_RUNNER') ?: 'nette'),
			]);
	}
}
