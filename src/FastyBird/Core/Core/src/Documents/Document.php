<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use Orisai\ObjectMapper;

/**
 * Data document interface
 */
interface Document extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
