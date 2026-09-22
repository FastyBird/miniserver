<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Consumers;

use Nette;

/**
 * Consumer configuration
 */
final readonly class Info
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string|null $routingKey,
		private readonly bool $enabled,
	)
	{
	}

	public function getRoutingKey(): string|null
	{
		return $this->routingKey;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

}
