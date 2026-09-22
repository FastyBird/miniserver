<?php declare(strict_types = 1);

namespace FastyBird\Core\Utilities\Tools;

use DateTimeInterface;
use FastyBird\Core\Providers\DoctrineTimestampable as DoctrineTimestampableProviders;
use FastyBird\Core\Services\DateTimeFactory;

/**
 * Date provider for doctrine timestampable
 */
readonly class DateTimeProvider implements DoctrineTimestampableProviders\DateProvider
{

	public function __construct(private DateTimeFactory\Clock $clock)
	{
	}

	public function getDate(): DateTimeInterface
	{
		return $this->clock->getNow();
	}

	public function getTimestamp(): int
	{
		return $this->clock->getNow()->getTimestamp();
	}

}
