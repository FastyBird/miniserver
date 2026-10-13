<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;

/**
 * The abstract target of an article's to-many relationship. The mapper picks the concrete class
 * from each item's `entity` value, and finds the back-reference to the article by its type, so
 * the shared fields are protected: reflection on a subclass does not see a private one
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_test_mapper_comments')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'comment_type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([TextComment::TYPE => TextComment::class, RatingComment::TYPE => RatingComment::class])]
abstract class Comment implements Entities\CrudEntity
{

	#[ORM\Id]
	#[ORM\Column(name: 'comment_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'comment_body', type: 'string', length: 255)]
	protected string $body;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
	#[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'article_id', nullable: false, onDelete: 'CASCADE')]
	protected Article $article;

	public function __construct(string $body, Article $article, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();
		$this->body = $body;
		$this->article = $article;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	public function setBody(string $body): void
	{
		$this->body = $body;
	}

	public function getArticle(): Article
	{
		return $this->article;
	}

	public function setArticle(Article $article): void
	{
		$this->article = $article;
	}

}
