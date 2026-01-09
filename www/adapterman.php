<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Override\Adapterman;
use Adapterman\Http;
use App\Core\Runner\AdaptermanRunner;
use Workerman\Worker;
use Workerman\Timer;

Adapterman::init();

$http_worker = new Worker('http://localhost:8000');
$http_worker->count = (int)\shell_exec('nproc') * 4;
$http_worker->name = 'AdapterMan';

$http_worker->onWorkerStart = function (Worker $worker) {
	if ($worker->id === 0) {
		Timer::add(600, function () {
			Http::tryGcSessions();
		});
	}
};

$http_worker->onMessage = static function ($connection) {
	$path = $_SERVER['REQUEST_URI'];

	if (false !== $file = AdaptermanRunner::dumpFile($path)) {
		$connection->send($file);
		return;
	}

	$connection->send(AdaptermanRunner::run());
};

Worker::runAll();
