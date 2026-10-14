<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Support;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

/**
 * A clock a test moves forward, for whatever has to happen a given time after something else
 */
final class MovableClock implements ClockInterface
{

	public function __construct(private DateTimeImmutable $now)
	{
	}

	#[Override]
	public function now(): DateTimeImmutable
	{
		return $this->now;
	}

	public function advance(int $seconds): void
	{
		$this->now = $this->now->setTimestamp($this->now->getTimestamp() + $seconds);
	}

}
