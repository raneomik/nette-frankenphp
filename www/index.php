<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

(new App\Core\Runner\RunnerFactory())
	->create($_SERVER['APP_RUNNER'] ?? 'nette')
	->run()
;
