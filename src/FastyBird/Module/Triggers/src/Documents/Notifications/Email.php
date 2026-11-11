<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Notifications;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Email notification document
 */
#[Documents\Mapping\Document(entity: Entities\Notifications\Email::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Notifications\Email::TYPE)]
final class Email extends Notification
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		#[Documents\Mapping\RefuseBlank]
		string $type,
		bool $enabled,
		#[Documents\Mapping\RequirePattern('/^[\w\-\.]+@[\w\-\.]+\.[\w-]{2,63}$/')]
		private readonly string $email,
		#[Documents\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $owner);
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'email' => $this->getEmail(),
		]);
	}

}
