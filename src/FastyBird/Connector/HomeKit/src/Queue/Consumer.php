<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue;

/**
 * Messages consumer interface
 */
interface Consumer
{

	public function consume(Messages\Message $message): bool;

}
