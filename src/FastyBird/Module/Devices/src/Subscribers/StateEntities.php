<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Subscribers;

use DateTimeInterface;
use Exception;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\EventLoop\Application\Status;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching as NetteCaching;
use Symfony\Component\EventDispatcher;

/**
 * Devices state entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateEntities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	private const ACTION_CREATED = 'created';

	private const ACTION_UPDATED = 'updated';

	private const ACTION_DELETED = 'deleted';

	public function __construct(
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
		private readonly Caching\Container $moduleCaching,
		private readonly Status $eventLoopStatus,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ConnectorPropertyStateEntityCreated::class => 'stateCreated',
			Events\ConnectorPropertyStateEntityUpdated::class => 'stateUpdated',
			Events\DevicePropertyStateEntityCreated::class => 'stateCreated',
			Events\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
			Events\ChannelPropertyStateEntityCreated::class => 'stateCreated',
			Events\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',
		];
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws ValuesExceptions\InvalidData
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 */
	public function stateCreated(
		Events\ConnectorPropertyStateEntityCreated|Events\DevicePropertyStateEntityCreated|Events\ChannelPropertyStateEntityCreated $event,
	): void
	{
		$this->cleanCache($event->getProperty());

		$this->publishDocument(
			$event->getSource(),
			$event->getProperty(),
			$event->getRead(),
			$event->getGet(),
			self::ACTION_CREATED,
		);
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws ValuesExceptions\InvalidData
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 */
	public function stateUpdated(
		Events\ConnectorPropertyStateEntityUpdated|Events\DevicePropertyStateEntityUpdated|Events\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->cleanCache($event->getProperty());

		$this->publishDocument(
			$event->getSource(),
			$event->getProperty(),
			$event->getRead(),
			$event->getGet(),
			self::ACTION_UPDATED,
		);
	}

	private function cleanCache(
		Documents\Connectors\Properties\Property|Documents\Devices\Properties\Property|Documents\Channels\Properties\Property $document,
	): void
	{
		$this->moduleCaching->getStateCache()->clean([
			NetteCaching\Cache::Tags => [$document->getId()->toString()],
		]);
		$this->moduleCaching->getStateStorageCache()->clean([
			NetteCaching\Cache::Tags => [$document->getId()->toString()],
		]);
	}

	/**
	 * @throws Exception
	 * @throws ValuesExceptions\InvalidData
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 */
	private function publishDocument(
		Sources\Source $source,
		Documents\Connectors\Properties\Dynamic|Documents\Devices\Properties\Dynamic|Documents\Channels\Properties\Dynamic|Documents\Devices\Properties\Mapped|Documents\Channels\Properties\Mapped $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty $readState,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $getState,
		string $action,
	): void
	{
		if ($property instanceof Documents\Connectors\Properties\Dynamic) {
			switch ($action) {
				case self::ACTION_CREATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY;

					break;
				case self::ACTION_UPDATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY;

					break;
				case self::ACTION_DELETED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY;

					break;
				default:
					return;
			}

			$document = $this->documentFactory->create(
				Documents\States\Connectors\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'connector' => $property->getConnector()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);

		} elseif (
			$property instanceof Documents\Devices\Properties\Dynamic
			|| $property instanceof Documents\Devices\Properties\Mapped
		) {
			switch ($action) {
				case self::ACTION_CREATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY;

					break;
				case self::ACTION_UPDATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY;

					break;
				case self::ACTION_DELETED:
					$routingKey = Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY;

					break;
				default:
					return;
			}

			$document = $this->documentFactory->create(
				Documents\States\Devices\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'device' => $property->getDevice()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);

		} else {
			switch ($action) {
				case self::ACTION_CREATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY;

					break;
				case self::ACTION_UPDATED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY;

					break;
				case self::ACTION_DELETED:
					$routingKey = Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY;

					break;
				default:
					return;
			}

			$document = $this->documentFactory->create(
				Documents\States\Channels\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'channel' => $property->getChannel()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);
		}

		$this->getPublisher($this->eventLoopStatus->isRunning())->publish(
			$source,
			$routingKey,
			$document,
		);
	}

	private function getPublisher(bool $async): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
