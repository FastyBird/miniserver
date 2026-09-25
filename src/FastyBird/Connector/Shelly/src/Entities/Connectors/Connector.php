<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Entities\Connectors;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use TypeError;
use ValueError;
use function is_string;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Connector extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'shelly-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Connector
	{
		return Sources\Connector::SHELLY;
	}

	/**
	 * @return array<Entities\Devices\Device>
	 */
	public function getDevices(): array
	{
		$devices = [];

		foreach (parent::getDevices() as $device) {
			if ($device instanceof Entities\Devices\Device) {
				$devices[] = $device;
			}
		}

		return $devices;
	}

	/**
	 * @throws ShellyExceptions\InvalidArgument
	 */
	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		if (!$device instanceof Entities\Devices\Device) {
			throw new ShellyExceptions\InvalidArgument('Provided device type is not valid');
		}

		parent::addDevice($device);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getClientMode(): Types\ClientMode
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLIENT_MODE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& Types\ClientMode::tryFrom(Utilities\Value::toString($property->getValue(), true)) !== null
		) {
			return Types\ClientMode::from(Utilities\Value::toString($property->getValue(), true));
		}

		throw new ShellyExceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudServerAddress(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLOUD_SERVER->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudAuthKey(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLOUD_AUTH_KEY->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

}
