<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;

/**
 * Enables this module's SocketsBridge exchange consumer, registered disabled, once the WebSocket
 * server is created, so exchange documents reach WAMP clients only from then on. Registered with
 * the WebSocketsExtension::SERVER_CREATED_LISTENER_TAG tag, whose value is its priority (#658).
 */
final readonly class EnableSocketsBridge
{

	public function __construct(private ExchangeConsumers\Container $consumers)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __invoke(Events\ServerCreated $event): void
	{
		$this->consumers->enable(DevicesConsumers\SocketsBridge::class);
	}

}
