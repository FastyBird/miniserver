<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Link object collection interface
 *
 * @extends IteratorAggregate<string, ILinkObject|string>
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
