<?php declare(strict_types = 1);

namespace FastyBird\Core\Formats\Tools;

use ArrayIterator;
use IteratorAggregate;
use Nette\Utils;
use Override;
use Traversable;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function strval;
use function trim;

/**
 * String enum value format
 *
 * @implements     IteratorAggregate<int, string>
 */
final class StringEnum implements IteratorAggregate
{

	/** @var array<int, string> */
	private array $items;

	/**
	 * @param string|array<int, string> $items
	 */
	public function __construct(string|array $items)
	{
		$parts = is_string($items) ? explode(',', $items) : $items;

		$this->items = array_values(
			array_filter(
				array_map(static fn (mixed $item): string => trim(strval($item)), $parts),
				static fn (string $item): bool => $item !== '',
			),
		);
	}

	/**
	 * @return array<string>
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	public function hasItem(string $item): bool
	{
		return in_array(
			Utils\Strings::lower($item),
			array_map(static fn (string $item): string => Utils\Strings::lower($item), $this->items),
			true,
		);
	}

	/**
	 * @return array<string>
	 */
	public function toArray(): array
	{
		return $this->getItems();
	}

	/**
	 * @return array<string>
	 */
	public function getValue(): array
	{
		return $this->toArray();
	}

	#[Override]
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->getItems());
	}

	#[Override]
	public function __toString(): string
	{
		return implode(',', $this->getItems());
	}

}
