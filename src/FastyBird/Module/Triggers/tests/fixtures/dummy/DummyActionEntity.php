<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DummyActionEntity extends Entities\Actions\Action
{

	public const TYPE = 'dummy';

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'action_do_item', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $doItem;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'action_value', type: 'string', length: 100, nullable: true)]
	private string $value;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function setDoItem(Uuid\UuidInterface $doItem): void
	{
		$this->doItem = $doItem;
	}

	public function getDoItem(): Uuid\UuidInterface
	{
		return $this->doItem;
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function validate(string $value): bool
	{
		return $this->value === $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'do_item' => $this->getDoItem()->toString(),
			'value' => $this->getValue(),
		]);
	}

}
