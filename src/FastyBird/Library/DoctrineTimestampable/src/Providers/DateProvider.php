<?php declare(strict_types = 1);

/**
 * UTCDateTime.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Providers
 * @since          1.0.0
 *
 * @date           05.01.23
 */

namespace FastyBird\Library\DoctrineTimestampable\Providers;

use DateTimeInterface;

/**
 * Date provider
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Providers
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface DateProvider
{

	public function getDate(): DateTimeInterface;

	public function getTimestamp(): int;

}
