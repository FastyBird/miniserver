<?php declare(strict_types = 1);

/**
 * OnServerStartHandler.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           01.03.17
 */

namespace FastyBird\Core\Subscribers\WsServer;

use FastyBird\Core\Controllers\WebSockets\IWampApplication;
use FastyBird\Core\Messaging\WebSockets\PushMessages;
use Nette;
use React\EventLoop\LoopInterface;
use function assert;

/**
 * Server start event for push managers
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class OnServerStartHandler
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(
		private PushMessages\ConsumersRegistry $consumersRegistry,
		private IWampApplication $application,
	)
	{
	}

	public function __invoke(LoopInterface $eventLoop): void
	{
		foreach ($this->consumersRegistry->getConsumers() as $consumer) {
			assert($consumer instanceof PushMessages\IConsumer);
			$consumer->connect($eventLoop, $this->application);
		}
	}

}
