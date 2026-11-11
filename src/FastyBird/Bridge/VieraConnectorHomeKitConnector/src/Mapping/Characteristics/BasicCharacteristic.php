<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Characteristics;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

/**
 * Basic characteristic mapping configuration
 */
final readonly class BasicCharacteristic implements Mapping\Mapping
{

	/**
	 * @param array<int<0, max>, HomeKitTypes\CharacteristicType> $require
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 */
	public function __construct(
		private HomeKitTypes\CharacteristicType $type,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private string|null $channel = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private string|null $property = null,
		#[CoreDocuments\Mapping\MapBoolLike]
		private bool $nullable = false,
		private array $require = [],
		#[DevicesDocuments\Mapping\MapFormat]
		private string|array|null $format = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private bool|float|int|string|null $value = null,
	)
	{
	}

	public function getType(): HomeKitTypes\CharacteristicType
	{
		return $this->type;
	}

	public function getChannel(): string|null
	{
		return $this->channel;
	}

	public function getProperty(): string|null
	{
		return $this->property;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}

	/**
	 * @return array<HomeKitTypes\CharacteristicType>
	 */
	public function getRequire(): array
	{
		return $this->require;
	}

	/**
	 * @return string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null
	 */
	public function getFormat(): string|array|null
	{
		return $this->format;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

}
