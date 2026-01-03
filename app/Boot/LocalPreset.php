<?php declare(strict_types = 1);

namespace App\Boot;

use Contributte\Bootstrap\ExtraConfigurator;
use Contributte\Nella\Boot\Preset\BasePreset;
use Contributte\Nella\DI\NellaExtension;
use Nette\DI\Compiler;

class LocalPreset extends BasePreset
{
	private function __construct(
		protected string $bootPoint,
	) {
	}

	public static function create(string $bootPoint): self
	{
		return new self($bootPoint);
	}

	public function apply(ExtraConfigurator $configurator): void
	{
		$configurator->setEnvDebugMode();

		$configurator->addStaticParameters([
			'rootDir' => dirname($this->bootPoint),
			'appDir' => $this->bootPoint,
			'wwwDir' => realpath($this->bootPoint . '/www'),
			'logDir' => realpath($this->bootPoint . '/var/log'),
			'tempDir' => realpath($this->bootPoint . '/var/tmp'),
		]);

		$configurator->addDynamicParameters([
			'runnerName' => getenv('APP_WORKER_MODE')
				? 'frankenphp'
				: 'nette',
		]);

		$configurator->enableTracy($this->bootPoint . '/var/log');

		// extensions
		$configurator->onCompile[] = static function (ExtraConfigurator $configurator, Compiler $compiler): void {
			$compiler->addExtension('nella', new NellaExtension());
		};

		// environment variables
		$configurator->onCompile[] = static function (ExtraConfigurator $configurator, Compiler $compiler): void {
			$compiler->addConfig(['parameters' => $configurator->getEnvironmentParameters()]);
		};


		// config.neon
		$configurator->addConfig($this->bootPoint . '/config/config.neon');

		// local.neon
		if (file_exists($this->bootPoint . '/config/local.neon')) {
			$configurator->addConfig($this->bootPoint . '/config/local.neon');
		}
	}
}
