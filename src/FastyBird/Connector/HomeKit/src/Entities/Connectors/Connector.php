<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.03.22
 */

namespace FastyBird\Connector\HomeKit\Entities\Connectors;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use TypeError;
use ValueError;
use function is_bool;
use function is_int;
use function is_string;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Connector extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'homekit-connector';

	/** @var Common\Collections\Collection<int, Entities\Clients\Client> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'connector',
		targetEntity: Entities\Clients\Client::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $clients;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Connector
	{
		return Sources\Connector::HOMEKIT;
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
	 * @throws HomeKitExceptions\InvalidArgument
	 */
	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		if (!$device instanceof Entities\Devices\Device) {
			throw new HomeKitExceptions\InvalidArgument('Provided device type is not valid');
		}

		parent::addDevice($device);
	}

	/**
	 * @return array<Entities\Clients\Client>
	 */
	public function getClients(): array
	{
		return $this->clients->toArray();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPort(): int
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::PORT->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_int($property->getValue())
		) {
			return $property->getValue();
		}

		return HomeKit\Constants::DEFAULT_PORT;
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMacAddress(): string
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::MAC_ADDRESS->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector MAC address is not configured');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getVersion(): int
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_int($property->getValue())
		) {
			return $property->getValue();
		}

		return 1;
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getSetupId(): string
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::SETUP_ID->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector setup ID is not configured');
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPinCode(): string
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::PIN_CODE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector PIN code is not configured');
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getXhmUri(): string
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::XHM_URI->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector XHM uri is not created');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getServerSecret(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::SERVER_SECRET->value,
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
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHashingKey(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::HASHING_KEY->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& (is_string($property->getValue()) || $property->getValue() === null)
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector hashing key is not configured');
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getClientPublicKey(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLIENT_PUBLIC_KEY->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& (is_string($property->getValue()) || $property->getValue() === null)
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector client public key is not configured');
	}

	/**
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getSharedKey(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& (is_string($property->getValue()) || $property->getValue() === null)
		) {
			return $property->getValue();
		}

		throw new HomeKitExceptions\InvalidState('Connector shared key is not configured');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function isPaired(): bool
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

}
