<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;

/**
 * The target of a comment's to-one relationship: relationship values nested inside the values of
 * a to-many item
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_test_mapper_attachments')]
final class Attachment implements Entities\CrudEntity
{

	#[ORM\Id]
	#[ORM\Column(name: 'attachment_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'attachment_file', type: 'string', length: 100)]
	private string $file;

	public function __construct(string $file, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();
		$this->file = $file;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function setFile(string $file): void
	{
		$this->file = $file;
	}

}
