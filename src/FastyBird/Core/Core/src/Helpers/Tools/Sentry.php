<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\Tools;

use Sentry\ClientInterface;

/**
 * Sentry connection helpers
 */
final readonly class Sentry
{

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
