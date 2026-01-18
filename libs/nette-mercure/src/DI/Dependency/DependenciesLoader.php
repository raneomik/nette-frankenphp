<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Mercure\DI\Dependency;

use Nette;
use Nette\Application\Application;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\Statement;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Mercure\Core\Discovery;
use Nette\Mercure\Latte\MercureExtension;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * Nette Framework Mercure dependencies.
 */
final readonly class DependenciesLoader
{
	public function __construct(
		private array $hubs = [],
	) {}

	public function load(ContainerBuilder $builder, Nette\DI\CompilerExtension $extension): void
	{
		$registryDefinition = $builder->addDefinition($extension->prefix("hub.registry"))
			->setType(HubRegistry::class)
			->setFactory(HubRegistry::class, [
				array_first($this->hubs),
				$this->hubs,
			])
			->setAutowired(true);


		$serializerDefinition = $builder->addDefinition("symfony.links.header_serializer")
			->setType(HttpHeaderSerializer::class)
			->setFactory(HttpHeaderSerializer::class, [])
			->setAutowired(false);

		$requestDef = $builder->getDefinitionByType(Request::class);
		$reponseDef = $builder->getDefinitionByType(Response::class);
		$discoveryDefinition = $builder->addDefinition($extension->prefix('hub.discovery'))
			->setType(Discovery::class)
			->setFactory(Discovery::class, [
				$registryDefinition,
				$serializerDefinition,
				$requestDef,
				$reponseDef,
			])
			->setAutowired(false);

		$appDef = $builder->getDefinitionByType(Application::class);

		foreach (array_keys($this->hubs) as $hubName) {
			$preflight = $builder->addDefinition($extension->prefix("preflight.$hubName"))
				->setType(PreflightHandler::class)
				->setArguments([
					$discoveryDefinition,
					$hubName,
				])
				->setAutowired(false);

			$appDef->addSetup('?->onRequest[] = ?', [
				'@self',
				$preflight
			]);
		}

		if ($latte = $builder->getByType(LatteFactory::class)) {
			$builder->getDefinition($latte)
				->getResultDefinition()
				->addSetup('addExtension', [new Statement(MercureExtension::class, [
					$registryDefinition,
					'%hotReloadUrl%',
				])]);
		}
	}
}
