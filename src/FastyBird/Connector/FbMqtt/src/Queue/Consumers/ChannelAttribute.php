<?php declare(strict_types = 1);

/**
 * ChannelAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Device channel attributes MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelAttribute implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelPropertiesManager,
		private readonly DevicesModels\Entities\Channels\Controls\ControlsRepository $channelControlsRepository,
		private readonly DevicesModels\Entities\Channels\Controls\ControlsManager $channelControlsManager,
		private readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\ChannelAttribute) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $message->getDevice()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'channel-attribute-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getDevice(),
					],
					'channel' => [
						'identifier' => $message->getChannel(),
					],
				],
			);

			return true;
		}

		$findChannelQuery = new Queries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($message->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

		if ($channel === null) {
			$this->logger->warning(
				sprintf('Device channel "%s" is not registered', $message->getChannel()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'channel-attribute-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'identifier' => $message->getChannel(),
					],
				],
			);

			return true;
		}

		$this->databaseHelper->transaction(function () use ($message, $channel): void {
			$toUpdate = [];

			if ($message->getAttribute() === Queue\Messages\Attribute::NAME) {
				$toUpdate['name'] = $message->getValue();
			}

			if ($message->getAttribute() === Queue\Messages\Attribute::PROPERTIES && is_array($message->getValue())) {
				$this->setChannelProperties($channel, Utils\ArrayHash::from($message->getValue()));
			}

			if ($message->getAttribute() === Queue\Messages\Attribute::CONTROLS && is_array($message->getValue())) {
				$this->setChannelControls($channel, Utils\ArrayHash::from($message->getValue()));
			}

			if ($toUpdate !== []) {
				$this->channelsManager->update($channel, Utils\ArrayHash::from($toUpdate));
			}
		});

		$this->logger->debug(
			'Consumed channel attribute message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
				'type' => 'channel-attribute-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param Utils\ArrayHash<string> $properties
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	private function setChannelProperties(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier($propertyName);

			if ($this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery) === null) {
				$this->channelPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'channel' => $channel,
					'identifier' => $propertyName,
					'settable' => false,
					'queryable' => false,
					'dataType' => MetadataTypes\DataType::UNKNOWN,
				]));
			}
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		// Cleanup for unused properties
		foreach ($this->channelPropertiesRepository->findAllBy($findChannelPropertiesQuery) as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->channelPropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $controls
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	private function setChannelControls(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			$findChannelControlQuery = new DevicesQueries\Entities\FindChannelControls();
			$findChannelControlQuery->forChannel($channel);
			$findChannelControlQuery->byName($controlName);

			if ($this->channelControlsRepository->findOneBy($findChannelControlQuery) === null) {
				$this->channelControlsManager->create(Utils\ArrayHash::from([
					'channel' => $channel,
					'name' => $controlName,
				]));
			}
		}

		$findChannelControlQuery = new DevicesQueries\Entities\FindChannelControls();
		$findChannelControlQuery->forChannel($channel);

		// Cleanup for unused control
		foreach ($this->channelControlsRepository->findAllBy($findChannelControlQuery) as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->channelControlsManager->delete($control);
			}
		}
	}

}
