<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Devices\Documents\Devices\Properties;

use DateTimeInterface;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Device mapped property document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Properties\Mapped::class)]
final class Mapped extends Property
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $parent,
		Types\PropertyCategory $category,
		string $identifier,
		string|null $name,
		MetadataTypes\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		bool|float|int|string|null $default = null,
		Uuid\UuidInterface|string|null $valueTransformer = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $settable = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $queryable = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $value = null,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
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
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		try {
			return ToolsUtilities\Value::normalizeValue(
				$this->value,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'value' => ToolsUtilities\Value::flattenValue($this->getValue()),

			'parent' => $this->getParent()->toString(),
		]);
	}

}
