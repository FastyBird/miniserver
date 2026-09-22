<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

/**
 * Server push consumers registry interface
 */
interface IConsumersRegistry
{

	public function addConsumer(IConsumer $consumer): void;

	public function getConsumer(string $name): IConsumer;

	/**
	 * @return array<IConsumer>
	 */
	public function getConsumers(): array;

}
