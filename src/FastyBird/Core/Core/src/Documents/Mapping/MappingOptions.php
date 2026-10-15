<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

/**
 * How one call of Mapper::map() treats its input. A caller states what it needs and never reaches the mapping
 * library behind the mapper, so the library can change without touching a caller
 */
final readonly class MappingOptions
{

	/**
	 * @param bool $allowUnknownFields input keys the target class does not declare are ignored instead of refused
	 */
	public function __construct(public bool $allowUnknownFields = false)
	{
	}

}
