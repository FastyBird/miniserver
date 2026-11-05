<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use function is_bool;

abstract class PropertyState implements States\Property
{

	public const string CREATED_AT_FIELD = 'createdAt';

	public const string UPDATED_AT_FIELD = 'updatedAt';

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey(self::ACTUAL_VALUE_FIELD)]
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly bool|float|int|string|DateTimeInterface|Payloads\Payload|null $actualValue = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey(self::EXPECTED_VALUE_FIELD)]
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly bool|float|int|string|DateTimeInterface|Payloads\Payload|null $expectedValue = null,
		#[Configurator\MapFromKey(self::PENDING_FIELD)]
		private readonly bool|DateTimeInterface $pending = false,
		#[CoreDocuments\Mapping\MapBoolLike]
		#[Configurator\MapFromKey(self::VALID_FIELD)]
		private readonly bool $valid = false,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey(self::CREATED_AT_FIELD)]
		private readonly DateTimeInterface|null $createdAt = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey(self::UPDATED_AT_FIELD)]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	public function getActualValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		return $this->actualValue;
	}

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		return $this->expectedValue;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
