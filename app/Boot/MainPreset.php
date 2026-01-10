<?php

declare(strict_types=1);

namespace App\Boot;

use App\Core\Runner\RunnerType;
use Contributte\Bootstrap\ExtraConfigurator;
use Contributte\Nella\Boot\Preset\BasePreset;
use Contributte\Nella\DI\NellaExtension;
use Nette\DI\Compiler;

final class MainPreset extends BasePreset
{
	private function __construct(
		protected string $bootPoint,
	) {}

	public static function create(string $bootPoint): self
	{
		return new self($bootPoint);
	}

	public function apply(ExtraConfigurator $configurator): void
	{
		$configurator->setEnvDebugMode();

		$rootDir = dirname($this->bootPoint);
		$configurator->addStaticParameters([
			'rootDir' => $rootDir,
			'appDir' => realpath($rootDir . '/app'),
			'wwwDir' => realpath($rootDir . '/www'),
			'logDir' => realpath($rootDir . '/var/log'),
			'baseUrl' => '/',
		]);

		$configurator->setTempDirectory($rootDir . '/var/temp');

		$configurator->addDynamicParameters([
			'runner' => RunnerType::from(getenv('APP_RUNNER') ?: 'contributte'),
			'hotReloadUrl' => $_SERVER['FRANKENPHP_HOT_RELOAD'] ?? null,
		]);

		$configurator->enableTracy($rootDir . '/var/log');

		// extensions
		$configurator->onCompile[] = static function (ExtraConfigurator $configurator, Compiler $compiler): void {
			$compiler->addExtension('nella', new NellaExtension());
		};

		// environment variables
		$configurator->onCompile[] = static function (ExtraConfigurator $configurator, Compiler $compiler): void {
			$compiler->addConfig(['parameters' => $configurator->getEnvironmentParameters()]);
		};

		// config.neon
		$configurator->addConfig($rootDir . '/config/config.neon');

		// local.neon
		if (file_exists($rootDir . '/config/local.neon')) {
			$configurator->addConfig($rootDir . '/config/local.neon');
		}
	}
}
