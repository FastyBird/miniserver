<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;

/**
 * An existing entity an article refers to: a to-one reference handed over as an object, the way
 * a JSON:API hydrator hands over a resolved relationship
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_test_mapper_authors')]
final class Author implements Entities\CrudEntity
{

	#[ORM\Id]
	#[ORM\Column(name: 'author_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'author_name', type: 'string', length: 100)]
	private string $name;

	public function __construct(string $name, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();
		$this->name = $name;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

}
