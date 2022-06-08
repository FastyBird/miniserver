<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Consumer
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\Consumers;

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\WsServerPlugin\Publishers as WsServerPluginPublishers;
use Nette;
use Psr\Log;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Consumer implements ExchangeConsumer\IConsumer
{

	use Nette\SmartObject;

	/** @var WsServerPluginPublishers\IPublisher */
	private WsServerPluginPublishers\IPublisher $publisher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		WsServerPluginPublishers\IPublisher $publisher,
		?Log\LoggerInterface $logger
	) {
		$this->publisher = $publisher;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function consume(
		$source,
		MetadataTypes\RoutingKeyType $routingKey,
		?MetadataEntities\IEntity $data
	): void {
		$this->publisher->publish(
			$source,
			$routingKey,
			$data
		);
	}

}
