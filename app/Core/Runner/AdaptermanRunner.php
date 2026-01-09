<?php

declare(strict_types=1);

namespace App\Core\Runner;

use App\Bootstrap;
use Nette\Application\Application;
use Tracy\Debugger;

final class AdaptermanRunner
{
	private const array ASSET_PATHS = [
		'/img',
		'/favicon',
	];

	private const string SERVER_PATH = '/www';

	private static int $handled = 0;

	public function __construct(
		private Bootstrap $bootstrap = new Bootstrap()
	) {}

	public static function run(): string|false
	{
		self::$handled++;

		$blueScreen = Debugger::getBlueScreen();

		Debugger::$onFatalError[] = function (\Throwable $e) use ($blueScreen): void {
			$blueScreen->render($e);
		};

		ob_start();
		$runner = new self();

		try {
			$runner->bootstrap->initializeEnvironment();
			$container = $runner->bootstrap->bootWebApplication();
			$application = $container->getByType(Application::class);

			$application->run();
		} catch (\Throwable $e) {
			Debugger::exceptionHandler($e);
		} finally {
			$output = ob_get_clean();

			gc_collect_cycles();
		}

		return $output;
	}

	public static function dumpFile(string $path): string|false
	{
		$assetMatches = array_filter(
			self::ASSET_PATHS,
			fn(string $assetPath): bool => str_contains($path, $assetPath),
		);

		if ([] === $assetMatches) {
			return false;
		}

		$path = parse_url($path, PHP_URL_PATH);
		$assetPath = dirname(__DIR__, 3) . self::SERVER_PATH . $path;
		var_dump($assetPath, file_exists($assetPath));
		if (false === file_exists($assetPath)) {
			return false;
		}

		return file_get_contents($assetPath);
	}

	// TODO: investigate - bypass @see Debugger\Helpers::isCli()
	public static function dumpTracyBar(): string|false
	{
		ob_start();
		Debugger::getStrategy()->renderBar();
		return ob_get_clean();
	}
}
