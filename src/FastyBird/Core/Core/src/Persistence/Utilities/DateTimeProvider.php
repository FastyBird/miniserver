<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Utilities;

use DateTimeInterface;
use Psr\Clock\ClockInterface;

/**
 * Date provider for doctrine timestampable
 */
final readonly class DateTimeProvider
{

	public function __construct(private ClockInterface $clock)
	{
	}

	public function getDate(): DateTimeInterface
	{
		return $this->clock->now();
	}

	public function getTimestamp(): int
	{
		return $this->clock->now()->getTimestamp();
	}

}
