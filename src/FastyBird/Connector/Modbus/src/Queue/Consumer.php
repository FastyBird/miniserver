<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queue;

/**
 * Clients messages consumer interface
 */
interface Consumer
{

	public function consume(Messages\Message $message): bool;

}
