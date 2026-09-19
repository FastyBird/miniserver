<?php declare(strict_types = 1);

/**
 * IErrorObjectCollection.php
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

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Error object collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IErrorObject>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IErrorObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $error
	 */
	public function addMany(array $error): void;

	public function add(IErrorObject $error): void;

	public function has(IErrorObject $error): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<int, IErrorObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
