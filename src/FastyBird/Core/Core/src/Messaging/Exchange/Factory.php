<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange;

/**
 * Exchange factory interface
 */
interface Factory
{

	public function create(): void;

}
