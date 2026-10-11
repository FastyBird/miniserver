<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Characteristics;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function assert;

/**
 * HAP channel variable property characteristic factory
 */
final class VariablePropertyFactory implements CharacteristicFactory
{

	/**
	 * @param array<Types\CharacteristicPermission> $permissions
	 * @param array<int>|null $validValues
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function create(
		Uuid\UuidInterface $typeId,
		string $name,
		Types\DataType $dataType,
		array $permissions,
		Protocol\Services\Service $service,
		DevicesDocuments\Channels\Properties\Property|null $property = null,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		float|int|bool|string|null $default = null,
		Types\CharacteristicUnit|null $unit = null,
	): VariableProperty
	{
		assert($property instanceof DevicesDocuments\Channels\Properties\Variable);

		return new VariableProperty(
			$typeId,
			$name,
			$dataType,
			$permissions,
			$service,
			$property,
			$validValues,
			$maxLength,
			$minValue,
			$maxValue,
			$minStep,
			$default,
			$unit,
		);
	}

	/**
	 * @return class-string<DevicesEntities\Channels\Properties\Variable>
	 */
	#[Override]
	public function getEntityClass(): string
	{
		return DevicesEntities\Channels\Properties\Variable::class;
	}

}
