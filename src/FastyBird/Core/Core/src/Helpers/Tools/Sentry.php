<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\Tools;

use Nette;
use Sentry\ClientInterface;

/**
 * Sentry connection helpers
 */
class Sentry
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ClientInterface|null $client = null,
	)
	{
	}

	public function clear(): void
	{
		$this->client?->flush();
	}

}
