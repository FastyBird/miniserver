<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;

/**
 * The target of an article's to-one relationship, created from nested values
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_test_mapper_summaries')]
final class Summary implements Entities\CrudEntity
{

	#[ORM\Id]
	#[ORM\Column(name: 'summary_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'summary_text', type: 'string', length: 255)]
	private string $text;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'summary_words', type: 'integer', nullable: true)]
	private int|null $words = null;

	public function __construct(string $text, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();
		$this->text = $text;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function setText(string $text): void
	{
		$this->text = $text;
	}

	public function getWords(): int|null
	{
		return $this->words;
	}

	public function setWords(int|null $words): void
	{
		$this->words = $words;
	}

}
