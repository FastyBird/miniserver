<?php declare(strict_types = 1);

namespace FastyBird\Core\Clock;

use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeZone;
use Override;
use Psr\Clock\ClockInterface;
use function date_default_timezone_get;

final class SystemClock implements ClockInterface
{

	private DateTimeZone $timeZone;

	/**
	 * @throws DateInvalidTimeZoneException
	 */
	public function __construct(DateTimeZone|null $timeZone = null)
	{
		$this->timeZone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
	}

	#[Override]
	public function now(): DateTimeImmutable
	{
		return (new DateTimeImmutable('now'))
			->setTimezone($this->timeZone);
	}

}
