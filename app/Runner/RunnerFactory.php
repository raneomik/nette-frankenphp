<?php declare(strict_types = 1);

namespace App\Runner;

final class RunnerFactory
{
    public function create(bool $workerMode): NetteRunner|FrankenphpRunner
	{
		if ($workerMode) {
			return new FrankenphpRunner();
		}

		return new NetteRunner();
	}
}
