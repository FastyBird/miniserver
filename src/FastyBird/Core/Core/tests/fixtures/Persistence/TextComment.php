<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Persistence;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping\Attribute;

/**
 * A comment with a to-one relationship of its own, created from values nested two levels deep
 */
#[ORM\Entity]
final class TextComment extends Comment
{

	public const string TYPE = 'text';

	#[Attribute\Crud(writable: true)]
	#[ORM\OneToOne(targetEntity: Attachment::class, cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'attachment_id', referencedColumnName: 'attachment_id', nullable: true)]
	private Attachment|null $attachment = null;

	public function getAttachment(): Attachment|null
	{
		return $this->attachment;
	}

	public function setAttachment(Attachment|null $attachment): void
	{
		$this->attachment = $attachment;
	}

}
