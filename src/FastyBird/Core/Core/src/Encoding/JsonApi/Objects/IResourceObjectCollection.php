<?php declare(strict_types = 1);

/**
 * IResourceObjectCollection.php
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
 * Resource object collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IResourceObject>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IResourceObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $resource
	 */
	public function addMany(array $resource): void;

	public function add(IResourceObject $resource): void;

	public function has(IResourceObject $resource): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<int, IResourceObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
