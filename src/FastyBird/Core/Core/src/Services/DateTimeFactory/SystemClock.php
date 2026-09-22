<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\DateTimeFactory;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Nette;
use function date_default_timezone_get;

class SystemClock implements Clock
{

	use Nette\SmartObject;

	private DateTimeZone $timeZone;

	public function __construct(DateTimeZone|null $timeZone = null)
	{
		$this->timeZone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
	}

	public function getNow(): DateTimeInterface
	{
		return (new DateTimeImmutable('now'))
			->setTimezone($this->timeZone);
	}

}
