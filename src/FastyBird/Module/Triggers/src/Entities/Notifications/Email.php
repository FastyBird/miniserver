<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Entities\Notifications;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_notifications_emails',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Emails notifications',
	],
)]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Email extends Notification
{

	public const string TYPE = 'email';

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'notification_email', type: 'string', nullable: false)]
	private string $email;

	public function __construct(
		string $email,
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->email = $email;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'email' => $this->getEmail(),
		]);
	}

}
