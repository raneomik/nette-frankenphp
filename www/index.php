<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

(new App\Runner\RunnerFactory())
	->create((bool) ($_SERVER['APP_WORKER_MODE'] ?? false))
	->run()
;
