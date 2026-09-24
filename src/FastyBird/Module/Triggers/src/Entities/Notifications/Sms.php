<?php declare(strict_types = 1);

/**
 * Sms.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Entities\Notifications;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Phone\Entities as PhoneEntities;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_notifications_sms',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'SMS notifications',
	],
)]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Sms extends Notification
{

	public const TYPE = 'sms';

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'notification_phone', type: 'phone', length: 150, nullable: false)]
	private PhoneEntities\Phone $phone;

	public function __construct(
		PhoneEntities\Phone $phone,
		TriggersEntities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->phone = $phone;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getPhone(): PhoneEntities\Phone
	{
		return $this->phone;
	}

	public function setPhone(PhoneEntities\Phone $phone): void
	{
		$this->phone = $phone;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'phone' => $this->getPhone()->getInternationalNumber(),
		]);
	}

}
