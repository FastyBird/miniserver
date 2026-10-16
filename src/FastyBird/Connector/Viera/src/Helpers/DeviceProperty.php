<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Helpers;

use Doctrine\DBAL;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Types as VieraTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Ramsey\Uuid;
use function array_merge;

final readonly class DeviceProperty
{

	public function __construct(
		private DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private Helpers\Database $databaseHelper,
		private Viera\Logger $logger,
	)
	{
	}

	/**
	 * @param class-string<DevicesEntities\Devices\Properties\Variable|DevicesEntities\Devices\Properties\Dynamic> $type
	 * @param string|array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws DBAL\Exception
	 * @throws VieraExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 */
	public function create(
		string $type,
		Uuid\UuidInterface $deviceId,
		string|bool|int|null $value,
		ValuesTypes\DataType $dataType,
		VieraTypes\DevicePropertyIdentifier $identifier,
		string|null $name = null,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
	): void
	{
		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->byDeviceId($deviceId);
		$findDevicePropertyQuery->byIdentifier($identifier);

		$property = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property !== null && $value === null) {
			$this->databaseHelper->transaction(
				function () use ($property): void {
					$this->devicesPropertiesManager->delete($property);
				},
			);

			return;
		}

		if ($value === null && $type === DevicesEntities\Devices\Properties\Variable::class) {
			return;
		}

		if ($property !== null && !$property instanceof $type) {
			$this->databaseHelper->transaction(function () use ($property): void {
				$this->devicesPropertiesManager->delete($property);
			});

			$this->logger->warning(
				'Stored device property was not of valid type',
				[
					'source' => Sources\Connector::VIERA->value,
					'type' => 'device-property-helper',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier->value,
					],
				],
			);

			$property = null;
		}

		if ($property === null) {
			$device = $this->devicesRepository->find(
				$deviceId,
				VieraEntities\Devices\Device::class,
			);

			if ($device === null) {
				$this->logger->error(
					'Device was not found, property could not be configured',
					[
						'source' => Sources\Connector::VIERA->value,
						'type' => 'device-property-helper',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'property' => [
							'identifier' => $identifier->value,
						],
					],
				);

				return;
			}

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->devicesPropertiesManager->create(
					array_merge(
						[
							'entity' => $type,
							'device' => $device,
							'identifier' => $identifier->value,
							'name' => $name,
							'dataType' => $dataType,
							'format' => $format,
						],
						$type === DevicesEntities\Devices\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					),
				),
			);

			$this->logger->debug(
				'Device property was created',
				[
					'source' => Sources\Connector::VIERA->value,
					'type' => 'device-property-helper',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier->value,
					],
				],
			);

		} else {
			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->devicesPropertiesManager->update(
					$property,
					array_merge(
						[
							'dataType' => $dataType,
							'format' => $format,
						],
						$type === DevicesEntities\Devices\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					),
				),
			);

			$this->logger->debug(
				'Device property was updated',
				[
					'source' => Sources\Connector::VIERA->value,
					'type' => 'device-property-helper',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier->value,
					],
				],
			);
		}
	}

}
