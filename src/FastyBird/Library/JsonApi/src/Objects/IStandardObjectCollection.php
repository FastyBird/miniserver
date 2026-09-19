<?php declare(strict_types = 1);

/**
 * IStandardObjectCollection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           18.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

use Countable;
use IteratorAggregate;

/**
 * Standard objects collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IStandardObject<string, mixed>>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IStandardObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $objects
	 */
	public function addMany(array $objects): void;

	/**
	 * @phpstan-param IStandardObject<string, mixed> $object
	 */
	public function add(IStandardObject $object): void;

	/**
	 * @phpstan-param IStandardObject<string, mixed> $object
	 */
	public function has(IStandardObject $object): bool;

	/**
	 * @return array<IStandardObject>
	 *
	 * @phpstan-return Array<int, IStandardObject<string, mixed>>
	 */
	public function getAll(): array;

	public function isEmpty(): bool;

}
