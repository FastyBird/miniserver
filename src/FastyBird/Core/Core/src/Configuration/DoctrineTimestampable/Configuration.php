<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           06.01.16
 */

namespace FastyBird\Core\Configuration\DoctrineTimestampable;

use Nette;

/**
 * Doctrine timestampable extension configuration storage
 * Store basic extension settings
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		public readonly bool $lazyAssociation = false,
		public readonly bool $autoMapField = false,
		public readonly string $dbFieldType = 'datetime_immutable',
	)
	{
	}

	public function autoMapField(): bool
	{
		return $this->autoMapField === true;
	}

	public function useLazyAssociation(): bool
	{
		return $this->lazyAssociation === true;
	}

}
