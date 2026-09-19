<?php declare(strict_types = 1);

/**
 * ILinkObject.php
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
 * Link value interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface ILinkObject
{

	public function getHref(): string;

	public function hasMeta(): bool;

	/**
	 * @phpstan-return IMetaObjectCollection<string, IMetaObject>
	 */
	public function getMeta(): IMetaObjectCollection;

}
