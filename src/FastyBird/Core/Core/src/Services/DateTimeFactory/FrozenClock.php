<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\DateTimeFactory;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Override;
use ValueError;
use function assert;
use function date_default_timezone_get;
use function floor;
use function round;

final class FrozenClock implements Clock
{

	private DateTimeImmutable $dt;

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function __construct(float|DateTimeInterface $timestamp, DateTimeZone|null $timeZone = null)
	{
		if ($timestamp instanceof DateTime) {
			$dt = DateTimeImmutable::createFromMutable($timestamp);

		} elseif ($timestamp instanceof DateTimeImmutable) {
			$dt = $timestamp;

		} else {
			[$seconds, $microseconds] = $this->getParts($timestamp);

			$dt = DateTimeImmutable::createFromFormat('U', (string) $seconds);
			assert($dt instanceof DateTimeImmutable);

			$dt = $dt->modify("+$microseconds microsecond");
		}

		$this->dt = $dt->setTimezone($timeZone ?? new DateTimeZone(date_default_timezone_get()));
	}

	#[Override]
	public function getNow(): DateTimeInterface
	{
		return clone $this->dt;
	}

	/**
	 * @return array{float, float}
	 */
	private function getParts(float $seconds): array
	{
		$wholeSeconds = floor($seconds);
		$microseconds = round(($seconds - $wholeSeconds) * 1E6);

		return [$wholeSeconds, $microseconds];
	}

}
