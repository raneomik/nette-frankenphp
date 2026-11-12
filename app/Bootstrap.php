<?php

declare(strict_types=1);

namespace App;

use App\Core\Runner\RunnerType;
use App\Core\Tracy\StderrLogger;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Tracy;

final readonly class Bootstrap
{
    private string $rootDir;

    private bool $isDebug;

    public function __construct(
        private Configurator $configurator = new Configurator(),
    ) {
        $this->rootDir = dirname(__DIR__);

        Tracy\Debugger::setLogger(new StderrLogger(Tracy\Debugger::getLogger()));
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
                'appDir' => __DIR__,
                'rootDir' => $this->rootDir,
                'dataDir' => $this->rootDir . '/var/data',
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
            ->addConfig($configDir . '/services.neon');

        // require-dev packages are configured there; absent with composer install --no-dev (prod image)
        if (self::devPackagesInstalled()) {
            $this->configurator->addConfig($configDir . '/dev.neon');
        }

        $this->configurator
            ->addDynamicParameters([
                'runner' => RunnerType::from(getenv('APP_RUNNER') ?: 'nette'),
                // same secret as the one verifying JWTs on the hub side (Caddyfile)
                'mercureJwtSecret' => (string) getenv('MERCURE_JWT_SECRET'),
                // e.g. "172.16.0.0/12": reverse proxy network, for an https:// baseUrl
                'trustedProxies' => self::listFromEnv('TRUSTED_PROXIES'),
                // Web Push (pwa: push, config/common.neon), off while empty: generate a pair with
                // php -r 'require "vendor/autoload.php"; print_r(Minishlink\WebPush\VAPID::createVapidKeys());'
                'vapidPublicKey' => (string) getenv('VAPID_PUBLIC_KEY'),
                'vapidPrivateKey' => (string) getenv('VAPID_PRIVATE_KEY'),
            ]);
    }

    /**
     * Whether composer installed the require-dev packages (false after install --no-dev).
     */
    private static function devPackagesInstalled(): bool
    {
        return \Composer\InstalledVersions::getRootPackage()['dev'];
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
