<?php declare(strict_types = 1);

/**
 * IConsumersRegistry.php
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

namespace FastyBird\Library\WebSockets\Wamp\PushMessages;

/**
 * Server push consumers registry interface
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
