<?php declare(strict_types = 1);

namespace FastyBird\Core\Providers\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Date provider
 */
interface DateProvider
{

	public function getDate(): DateTimeInterface;

	public function getTimestamp(): int;

}
