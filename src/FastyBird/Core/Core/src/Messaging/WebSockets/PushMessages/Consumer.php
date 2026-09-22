<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

use Nette;

/**
 * Server push consumer
 */
abstract class Consumer implements IConsumer
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(private string $name)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

}
