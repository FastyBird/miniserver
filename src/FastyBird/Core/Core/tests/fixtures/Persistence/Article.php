<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;

/**
 * The root of the graph the entity mapper fills: a plain field, a JSON field with nested values,
 * a to-one reference, a to-one relationship created from nested values, and a to-many
 * relationship to an abstract entity created from nested values
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_test_mapper_articles')]
final class Article implements Entities\CrudEntity
{

	#[ORM\Id]
	#[ORM\Column(name: 'article_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'article_title', type: 'string', length: 100)]
	private string $title;

	/** @var array<mixed>|null */
	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'article_params', type: 'json', nullable: true)]
	private array|null $params = null;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\ManyToOne(targetEntity: Author::class)]
	#[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'author_id', nullable: false)]
	private Author $author;

	#[Attribute\Crud(writable: true)]
	#[ORM\OneToOne(targetEntity: Summary::class, cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'summary_id', referencedColumnName: 'summary_id', nullable: true)]
	private Summary|null $summary = null;

	/** @var Common\Collections\Collection<int, Comment> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'article',
		targetEntity: Comment::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	private Common\Collections\Collection $comments;

	public function __construct(string $title, Author $author, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();
		$this->title = $title;
		$this->author = $author;

		$this->comments = new Common\Collections\ArrayCollection();
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): void
	{
		$this->title = $title;
	}

	/**
	 * @return array<mixed>|null
	 */
	public function getParams(): array|null
	{
		return $this->params;
	}

	/**
	 * @param array<mixed>|null $params
	 */
	public function setParams(array|null $params): void
	{
		$this->params = $params;
	}

	public function getAuthor(): Author
	{
		return $this->author;
	}

	public function setAuthor(Author $author): void
	{
		$this->author = $author;
	}

	public function getSummary(): Summary|null
	{
		return $this->summary;
	}

	public function setSummary(Summary|null $summary): void
	{
		$this->summary = $summary;
	}

	/**
	 * @return array<Comment>
	 */
	public function getComments(): array
	{
		return $this->comments->toArray();
	}

	/**
	 * @param array<Comment> $comments
	 */
	public function setComments(array $comments): void
	{
		$this->comments->clear();

		foreach ($comments as $comment) {
			$this->comments->add($comment);
		}
	}

}
