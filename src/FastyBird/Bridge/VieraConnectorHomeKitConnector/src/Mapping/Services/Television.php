<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Documents;
use Override;
use TypeError;
use ValueError;
use function assert;
use function class_exists;

/**
 * Television service mapping configuration
 */
final readonly class Television implements Service
{

	/**
	 * @param 'Television' $type
	 * @param array<int<0, max>, Mapping\Characteristics\BasicCharacteristic> $characteristics
	 */
	public function __construct(
		private string $type,
		#[Documents\Mapping\RefuseBlank]
		private string $class,
		#[Documents\Mapping\MapBlankToNull]
		private string|null $channel = null,
		#[Documents\Mapping\MapBoolLike]
		private bool $multiple = false,
		private array $characteristics = [],
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function getType(): HomeKitTypes\ServiceType
	{
		return HomeKitTypes\ServiceType::from($this->type);
	}

	#[Override]
	public function getClass(): string
	{
		assert(class_exists($this->class));

		return $this->class;
	}

	#[Override]
	public function getChannel(): string|null
	{
		return $this->channel;
	}

	#[Override]
	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * @return array<Mapping\Characteristics\BasicCharacteristic>
	 */
	#[Override]
	public function getCharacteristics(): array
	{
		return $this->characteristics;
	}

}
