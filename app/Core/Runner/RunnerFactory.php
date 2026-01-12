<?php

declare(strict_types=1);

namespace App\Core\Runner;

final class RunnerFactory
{
	public function create(string $workerMode): DefaultRunner|FrankenphpRunner
	{
		$type = RunnerType::tryFrom($workerMode);

		if (RunnerType::Frankenphp === $type) {
			return new FrankenphpRunner();
		}

		return new DefaultRunner();
	}
}
