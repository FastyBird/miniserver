<?php declare(strict_types = 1);

/**
 * ILinkObjectCollection.php
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
 * Link object collection interface
 *
 * @extends IteratorAggregate<string, ILinkObject|string>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface ILinkObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $link
	 */
	public function addMany(array $link): void;

	public function add(ILinkObject|string $link, string $key): void;

	public function has(string $key): bool;

	public function get(string $key): string|ILinkObject;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<string, ILinkObject|string>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
