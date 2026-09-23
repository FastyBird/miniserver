<?php declare(strict_types = 1);

namespace FastyBird\Core\Utilities\Tools;

use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Providers\DoctrineTimestampable as DoctrineTimestampableProviders;
use Override;

/**
 * Date provider for doctrine timestampable
 */
final readonly class DateTimeProvider implements DoctrineTimestampableProviders\DateProvider
{

	public function __construct(private Clock\Clock $clock)
	{
	}

	#[Override]
	public function getDate(): DateTimeInterface
	{
		return $this->clock->getNow();
	}

	#[Override]
	public function getTimestamp(): int
	{
		return $this->clock->getNow()->getTimestamp();
	}

}
