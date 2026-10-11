<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Notifications;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
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
		string $type,
		bool $enabled,
		#[ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true)]
		private readonly string $email,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $owner);
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'email' => $this->getEmail(),
		]);
	}

}
