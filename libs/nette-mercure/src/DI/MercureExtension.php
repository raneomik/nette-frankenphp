<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Mercure\DI;

use Nette;
use Nette\DI\Definitions\Definition;
use Nette\Mercure\DI\Dependency\DependenciesLoader;
use Nette\Mercure\HubInterface;
use Nette\Mercure\Latte\TemplatingHub;
use Nette\Schema\Expect;
use Symfony\Component\Mercure\FrankenPhpHub;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface as SymfonyHubInterface;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * Nette Framework Mercure services.
 */
class MercureExtension extends Nette\DI\CompilerExtension
{
	public function __construct(
		private readonly bool $debugMode = false,
		private array $hubs = [],
	) {}

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::arrayOf(
			Expect::structure([
				'url' => Expect::string()->default('%baseUrl%/.well-known/mercure')->required()->dynamic(),
				'jwt' => Expect::structure([
					'secret' => Expect::string(getenv('MERCURE_JWT_SECRET_KEY') ?: '!m3rcur3C00ki3!')->dynamic(),
					'publish' => Expect::arrayOf('string')->default(['*'])->dynamic(),
					'subscribe' => Expect::arrayOf('string')->default(['*'])->dynamic(),
					'algorithm' => Expect::string('hmac.sha256'),
					'factory' => Expect::string(LcobucciFactory::class),
				])->required(),
				'debugger' => Expect::bool('%debugMode%'),
				'autowired' => Expect::bool(),
			]),
		)->before(fn($val) => is_array(reset($val)) || reset($val) === null
			? $val
			: ['default' => $val]);
	}

	public function loadConfiguration(): void
	{
		$autowired = true;

		foreach ($this->config as $name => $config) {
			$config->autowired ??= $autowired;
			$autowired = false;
			$this->hubs[$name] = $this->setupHub($config, $name);
		}
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$dependenciesLoader = new DependenciesLoader($this->hubs);
		$dependenciesLoader->load($builder, $this);
	}

	private function setupHub(\stdClass $config, string $name): Definition
	{
		$builder = $this->getContainerBuilder();

		$symfonyHubDefinition = $this->hubDefinition($config, $name);

		$hubDefinition = $builder->addDefinition($this->prefix("hub.$name"))
			->setType(HubInterface::class)
			->setFactory(TemplatingHub::class, [
				$symfonyHubDefinition,
			])
			->setAutowired(!$this->debugMode && $config->autowired);

		if ($this->debugMode && ($config->debugger ?? false)) {
			$hubDefinition = $builder->addDefinition($this->prefix("traceable.hub.$name"))
				->setType(HubInterface::class)
				->setFactory(\Nette\Mercure\Tracy\TraceableHub::class, [
					$hubDefinition,
				])
				->setAutowired($config->autowired);
		}

		return $hubDefinition;
	}

	private function hubDefinition(\stdClass $config, string $name): Definition
	{
		$builder = $this->getContainerBuilder();

		$factoryArguments = $config->jwt->factory === LcobucciFactory::class ? [
			$config->jwt->secret,
			$config->jwt->algorithm,
		] : [
			$config->jwt->secret,
		];

		$tokenFactoryDefinition = $builder->addDefinition($this->prefix("factory.token.$name"))
			->setType(TokenFactoryInterface::class)
			->setFactory($config->jwt->factory, $factoryArguments)
			->setAutowired($config->autowired);

		if (getenv('FRANKENPHP_CONFIG') ?: false) {
			return $builder->addDefinition($this->prefix("sf.hub.$name"))
				->setType($this->debugMode ? FrankenPhpHub::class : SymfonyHubInterface::class)
				->setFactory(FrankenPhpHub::class, [
					$config->url,
					$tokenFactoryDefinition,
				])
				->setAutowired(false);
		}

		$factoryProviderDefinition = $builder->addDefinition($this->prefix("token.provider.$name"))
			->setType(TokenProviderInterface::class)
			->setFactory(FactoryTokenProvider::class, [
				$tokenFactoryDefinition,
				$config->jwt->subscribe,
				$config->jwt->publish,
			])
			->setAutowired($config->autowired);

		return $builder->addDefinition($this->prefix("sf.hub.$name"))
			->setType($this->debugMode ? Hub::class : SymfonyHubInterface::class)
			->setFactory(Hub::class, [
				$config->url,
				$factoryProviderDefinition,
				$tokenFactoryDefinition,
			])
			->setAutowired(false);
	}
}
