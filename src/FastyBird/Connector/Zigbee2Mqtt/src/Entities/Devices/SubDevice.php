<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Zigbee2Mqtt\Entities as Zigbee2MqttEntities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function assert;
use function count;
use function is_bool;
use function is_string;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class SubDevice extends Device
{

	public const string TYPE = 'zigbee2mqtt-connector-sub-device';

	/**
	 * @throws Zigbee2MqttExceptions\InvalidState
	 */
	public function __construct(
		string $identifier,
		Bridge $parent,
		Zigbee2MqttEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$this->setParents([$parent]);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Zigbee2MqttExceptions\InvalidState
	 */
	public function getBridge(): Bridge
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof Bridge) {
				return $parent;
			}
		}

		throw new Zigbee2MqttExceptions\InvalidState('Sub-device have to have parent bridge defined');
	}

	/**
	 * @throws Zigbee2MqttExceptions\InvalidState
	 */
	#[Override]
	public function setParents(array $parents): void
	{
		if (count($parents) !== 1 || !$parents[0] instanceof Bridge) {
			throw new Zigbee2MqttExceptions\InvalidState(
				'Sub-device could have only one parent and it have to be gateway',
			);
		}

		parent::setParents($parents);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getIeeeAddress(): string
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::IEEE_ADDRESS->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		throw new CoreExceptions\InvalidState('Device IEEE addrress is not configured');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHardwareType(): Types\DeviceType
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::TYPE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
			&& Types\DeviceType::tryFrom(Utilities\Value::toString($property->getValue(), true)) !== null
		) {
			return Types\DeviceType::tryFrom(Utilities\Value::toString($property->getValue(), true));
		}

		throw new CoreExceptions\InvalidState('Device hardware type is not configured');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHardwareModel(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MODEL->value,
			)
			->first();

		if (!$property instanceof DevicesEntities\Devices\Properties\Variable || $property->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHardwareManufacturer(): string|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MANUFACTURER->value,
			)
			->first();

		if (!$property instanceof DevicesEntities\Devices\Properties\Variable || $property->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function isSupported(): bool
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::SUPPORTED->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function isDisabled(): bool
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::DISABLED->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

}
