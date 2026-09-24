<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           21.11.23
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;
use function React\Async\await;

/**
 * Device channel property consumer trait
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository
 * @property-read DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository
 * @property-read DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager
 * @property-read DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository
 * @property-read DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager
 * @property-read Helpers\Database $databaseHelper
 * @property-read Tuya\Logger $logger
 */
trait ChannelProperty
{

	/**
	 * @param class-string<DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Dynamic> $type
	 * @param string|array<int, string>|array<int, string|int|float|array<int, string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function setChannelProperty(
		string $type,
		Uuid\UuidInterface $channelId,
		string|bool|int|null $value,
		Types\DataType $dataType,
		string $identifier,
		string|null $name = null,
		array|string|null $format = null,
		string|null $unit = null,
		float|int|string|null $invalid = null,
		int|float|null $scale = null,
		float|int|null $step = null,
		bool $settable = false,
		bool $queryable = false,
	): void
	{
		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->byChannelId($channelId);
		$findChannelPropertyQuery->byIdentifier($identifier);

		$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		if ($property !== null && $value === null && $type === DevicesEntities\Channels\Properties\Variable::class) {
			$this->databaseHelper->transaction(
				function () use ($property): void {
					$this->channelsPropertiesManager->delete($property);
				},
			);

			return;
		}

		if ($value === null && $type === DevicesEntities\Channels\Properties\Variable::class) {
			return;
		}

		if ($property !== null && !$property instanceof $type) {
			$this->databaseHelper->transaction(function () use ($property): void {
				$this->channelsPropertiesManager->delete($property);
			});

			$this->logger->warning(
				'Stored channel property was not of valid type',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);

			$property = null;
		}

		if ($property === null) {
			$channel = $this->channelsRepository->find($channelId, Entities\Channels\Channel::class);

			if ($channel === null) {
				$this->logger->error(
					'Channel was not found, property could not be configured',
					[
						'source' => Sources\Connector::TUYA->value,
						'type' => 'message-consumer',
						'channel' => [
							'id' => $channelId->toString(),
						],
						'property' => [
							'identifier' => $identifier,
						],
					],
				);

				return;
			}

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->create(
					Utils\ArrayHash::from(array_merge(
						[
							'entity' => $type,
							'channel' => $channel,
							'identifier' => $identifier,
							'name' => $name,
							'dataType' => $dataType,
							'format' => $format,
							'unit' => $unit,
							'invalid' => $invalid,
							'scale' => $scale,
							'step' => $step,
						],
						$type === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					)),
				),
			);

			$this->logger->debug(
				'Channel property was created',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				// Some Tuya devices has invalid values configured
				// E.g. wi-fi dimmable device has allowed values "incandescent" and "halogen" but it also provide "led" value
				await($this->channelPropertiesStatesManager->delete($property->getId()));
			}

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->update(
					$property,
					Utils\ArrayHash::from(array_merge(
						[
							'dataType' => $dataType,
							'format' => $format,
							'unit' => $unit,
							'invalid' => $invalid,
							'scale' => $scale,
							'step' => $step,
						],
						$type === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					)),
				),
			);

			$this->logger->debug(
				'Channel property was updated',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);
		}
	}

}
