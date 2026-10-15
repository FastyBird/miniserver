<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

/**
 * Data document interface
 */
interface Document
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
