<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Writers;

/**
 * Properties writer interface
 */
interface Writer
{

	public function connect(): void;

	public function disconnect(): void;

}
