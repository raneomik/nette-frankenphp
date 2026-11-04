<?php

declare(strict_types=1);

namespace App;

use App\Core\Runner\RunnerType;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\FileSystem;

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
        return $this->isDebug ??= (bool) getenv('NETTE_DEBUG');
    }

    public function prepare(): void
    {
        $this->initializeEnvironment();
        $this->setupContainer();
    }

    public function createContainer(): Container
    {
        return $this->configurator->createContainer();
    }

    public function initializeEnvironment(): void
    {
        FileSystem::createDir($this->rootDir . '/var/temp/sessions');
        FileSystem::createDir($this->rootDir . '/var/data');
        FileSystem::createDir($this->rootDir . '/var/log');

        $this->configurator
            ->setDebugMode($this->isDebug())
            ->setTempDirectory($this->rootDir . '/var/temp')
            ->addStaticParameters([
                'rootDir' => $this->rootDir,
                'dataDir' => $this->rootDir . '/var/data',
            ])

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
                // même secret que celui qui vérifie les JWT côté hub (Caddyfile)
                'mercureJwtSecret' => (string) getenv('MERCURE_PUBLISHER_JWT_KEY'),
                // ex. "172.16.0.0/12" : réseau du reverse proxy, pour un baseUrl en https://
                'trustedProxies' => self::listFromEnv('TRUSTED_PROXIES'),
            ]);
    }

    /**
     * @return list<string>
     */
    private static function listFromEnv(string $name): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode(',', (string) getenv($name))),
            static fn (string $value): bool => '' !== $value,
        ));
    }
}
