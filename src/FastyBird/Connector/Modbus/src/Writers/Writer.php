<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Writers;

/**
 * Properties writer interface
 */
interface Writer
{

	public function connect(): void;

	public function disconnect(): void;

}
