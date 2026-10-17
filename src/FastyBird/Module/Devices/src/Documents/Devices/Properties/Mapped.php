<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Devices\Properties;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Override;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Device mapped property document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Properties\Mapped::class)]
final class Mapped extends Property
{

	/**
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $device,
		private readonly Uuid\UuidInterface $parent,
		DevicesTypes\PropertyCategory $category,
		#[CoreDocuments\Mapping\RefuseBlank]
		string $identifier,
		#[CoreDocuments\Mapping\MapBlankToNull]
		string|null $name,
		#[Configurator\MapFromKey('data_type')]
		ValuesTypes\DataType $dataType,
		#[CoreDocuments\Mapping\MapBlankToNull]
		string|null $unit = null,
		#[DevicesDocuments\Mapping\MapFormat]
		string|array|null $format = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		float|int|string|null $invalid = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		int|null $scale = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		int|float|null $step = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		bool|float|int|string|null $default = null,
		#[DevicesDocuments\Mapping\MapValueTransformer]
		#[Configurator\MapFromKey('value_transformer')]
		Uuid\UuidInterface|string|null $valueTransformer = null,
		private readonly bool|null $settable = null,
		private readonly bool|null $queryable = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private readonly bool|float|int|string|null $value = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct(
			$id,
			$device,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$default,
			$valueTransformer,
			$owner,
			$createdAt,
			$updatedAt,
		);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Devices\Properties\Mapped::getType();
	}

	public function getParent(): Uuid\UuidInterface
	{
		return $this->parent;
	}

	public function isSettable(): bool
	{
		return $this->settable ?? false;
	}

	public function isQueryable(): bool
	{
		return $this->queryable ?? false;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		try {
			return Utilities\Value::normalizeValue(
				$this->value,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'value' => Utilities\Value::flattenValue($this->getValue()),

			'parent' => $this->getParent()->toString(),
		]);
	}

}
