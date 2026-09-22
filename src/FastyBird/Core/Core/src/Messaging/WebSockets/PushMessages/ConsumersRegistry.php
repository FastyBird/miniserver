<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

use FastyBird\Core\Exceptions;
use Nette;
use Override;
use function sprintf;

/**
 * Server push consumers registry
 */
final class ConsumersRegistry implements IConsumersRegistry
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/** @var array<IConsumer> */
	private array $consumers = [];

	public function __construct()
	{
		$this->consumers = [];
	}

	#[Override]
	public function addConsumer(IConsumer $consumer): void
	{
		$this->consumers[$consumer->getName()] = $consumer;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function getConsumer(string $name): IConsumer
	{
		if (isset($this->consumers[$name])) {
			return $this->consumers[$name];
		}

		throw new Exceptions\InvalidArgument(sprintf('Consumer with name "%s" was not found.', $name));
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getConsumers(): array
	{
		return $this->consumers;
	}

}
