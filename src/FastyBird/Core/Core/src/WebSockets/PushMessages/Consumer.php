<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\PushMessages;

use Override;

/**
 * Server push consumer
 */
abstract class Consumer implements IConsumer
{

	public function __construct(private string $name)
	{
	}

	#[Override]
	public function getName(): string
	{
		return $this->name;
	}

}
