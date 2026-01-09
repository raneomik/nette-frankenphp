<?php

namespace Override;

use Adapterman\Adapterman as BaseAdapterman;
use Adapterman\Http;

class Adapterman extends BaseAdapterman
{
	public const VERSION = "nette experiment";

	public static function init(): void
	{
		try {
			putenv('APP_RUNNER=adapterman');

			// OK initialize the functions
			// require __DIR__ . '/functions/AdapterFunctions.php';
			// require __DIR__ . '/../vendor/joanhey/adapterman/src/functions/AdapterSessionFunctions.php';
			class_alias(Http::class, \Protocols\Http::class);
			Http::init();
		} catch (\Exception $e) {
			fwrite(STDERR, self::NAME . ' Error:' . PHP_EOL);
			fwrite(STDERR, $e->getMessage());
			exit;
		}

		fwrite(STDOUT, self::NAME . ' OK' . PHP_EOL);
	}
}
