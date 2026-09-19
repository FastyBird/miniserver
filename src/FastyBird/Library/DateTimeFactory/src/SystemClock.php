<?php declare(strict_types = 1);

/**
 * SystemClock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeFactory!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\DateTimeFactory;

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
