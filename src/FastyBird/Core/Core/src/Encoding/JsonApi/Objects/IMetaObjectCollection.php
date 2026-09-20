<?php declare(strict_types = 1);

/**
 * IMetaObjectCollection.php
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

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Meta object collection interface
 *
 * @phpstan-extends IteratorAggregate<string, IMetaObject>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IMetaObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $meta
	 */
	public function addMany(array $meta): void;

	public function add(IMetaObject $meta, string $key): void;

	public function get(string $key): IMetaObject;

	public function has(string $key): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<string, IMetaObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
