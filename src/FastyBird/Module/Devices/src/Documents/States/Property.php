<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States;

use DateTimeInterface;
use FastyBird\Core\Documents;
use Override;
use Ramsey\Uuid;
use function is_bool;

/**
 * Channel property state document
 */
#[Documents\Mapping\MappedSuperclass]
abstract class Property implements Documents\Document, Documents\CreatedAt, Documents\UpdatedAt
{

	use Documents\HasCreatedAt;
	use Documents\HasUpdatedAt;

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly StateValues $read,
		private readonly StateValues $get,
		private readonly bool|DateTimeInterface $pending = false,
		private readonly bool $valid = false,
		protected readonly DateTimeInterface|null $createdAt = null,
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
