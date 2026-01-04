<?php

declare(strict_types=1);

namespace App;

use Nette;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;

final readonly class Bootstrap
{
	private string $rootDir;

	public function __construct(
		private Configurator $configurator = new Configurator,
	) {
		$this->rootDir = dirname(__DIR__);
	}

	public function bootWebApplication(): Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();

		return $this->configurator->createContainer();
	}

	public function initializeEnvironment(): void
	{
		$this->configurator->setTempDirectory($this->rootDir . '/var/temp');

		$this->configurator->setDebugMode(
			(bool) getenv('NETTE_DEBUG')
		); 
		
		$this->configurator->enableTracy($this->rootDir . '/var/log');

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
	}

	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');
		
		$this->configurator->addDynamicParameters([
			'runnerName' => getenv('APP_WORKER_MODE')
				? 'frankenphp'
				: 'nette',
		]);

	}
}
