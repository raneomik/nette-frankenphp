<?php

/*
 * This file is part of the Mercure Component project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nette\Mercure;

use Symfony\Component\Mercure\HubInterface as SymfonyHubInterface;
use Symfony\Component\Mercure\Update;

interface HubInterface extends SymfonyHubInterface
{
	public function publish(
		Update $update,
		?string $template = null,
		null|object|array $templateData = null
	): string;
}
