<?php declare(strict_types = 1);

/**
 * FrozenClock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeFactory!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           29.08.24
 */

namespace FastyBird\Library\DateTimeFactory;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Nette;
use function assert;
use function date_default_timezone_get;
use function floor;
use function round;

class FrozenClock implements Clock
{

	use Nette\SmartObject;

	private DateTimeImmutable $dt;

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
