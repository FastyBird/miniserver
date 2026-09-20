<?php declare(strict_types = 1);

/**
 * ConsumersRegistry.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

use FastyBird\Core\Exceptions;
use Nette;
use function sprintf;

/**
 * Server push consumers registry
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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

	public function addConsumer(IConsumer $consumer): void
	{
		$this->consumers[$consumer->getName()] = $consumer;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
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
	public function getConsumers(): array
	{
		return $this->consumers;
	}

}
