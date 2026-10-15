<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States;

use DateTimeInterface;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function is_bool;

/**
 * Channel property state document
 */
#[Documents\Mapping\MappedSuperclass]
abstract class Property implements Documents\Document, ObjectMapper\MappedObject, Documents\CreatedAt, Documents\UpdatedAt
{

	use Documents\HasCreatedAt;
	use Documents\HasUpdatedAt;

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\MappedObjectValue(class: StateValues::class)]
		private readonly StateValues $read,
		#[ObjectMapper\Rules\MappedObjectValue(class: StateValues::class)]
		private readonly StateValues $get,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
		])]
		private readonly bool|DateTimeInterface $pending = false,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $valid = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getRead(): StateValues
	{
		return $this->read;
	}

	public function getGet(): StateValues
	{
		return $this->get;
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'read' => $this->getRead()->toArray(),
			'get' => $this->getGet()->toArray(),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),

			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
