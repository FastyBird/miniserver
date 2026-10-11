<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Writers;

/**
 * Properties writer interface
 */
interface Writer
{

	public function connect(): void;

	public function disconnect(): void;

}
