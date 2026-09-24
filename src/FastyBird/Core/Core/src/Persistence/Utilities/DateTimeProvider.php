<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Utilities;

use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Persistence\Providers;
use Override;

/**
 * Date provider for doctrine timestampable
 */
final readonly class DateTimeProvider implements Providers\DateProvider
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
