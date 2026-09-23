<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\DateTimeFactory;

use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Override;
use function date_default_timezone_get;

class SystemClock implements Clock
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
	public function getNow(): DateTimeInterface
	{
		return (new DateTimeImmutable('now'))
			->setTimezone($this->timeZone);
	}

}
