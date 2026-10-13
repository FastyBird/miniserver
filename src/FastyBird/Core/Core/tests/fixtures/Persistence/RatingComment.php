<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping\Attribute;

/**
 * A comment that carries a score
 */
#[ORM\Entity]
final class RatingComment extends Comment
{

	public const string TYPE = 'rating';

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'comment_score', type: 'integer', nullable: true)]
	private int|null $score = null;

	public function getScore(): int|null
	{
		return $this->score;
	}

	public function setScore(int|null $score): void
	{
		$this->score = $score;
	}

}
