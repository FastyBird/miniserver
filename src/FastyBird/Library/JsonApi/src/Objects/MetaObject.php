<?php declare(strict_types = 1);

/**
 * MetaObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

/**
 * Meta value
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class MetaObject implements IMetaObject
{

	/**
	 * @param string|int|float|bool|array<mixed> $value
	 */
	public function __construct(private string|int|float|bool|array $value)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValue()
	{
		return $this->value;
	}

}
