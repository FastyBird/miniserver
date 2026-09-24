<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Transformers;

/**
 * Transformer base value object interface
 */
interface Transformer
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
