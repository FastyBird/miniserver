<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Entities\Triggers;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_triggers_manual',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Manual triggers',
	],
)]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Manual extends Trigger
{

	public const string TYPE = 'manual';

	public static function getType(): string
	{
		return self::TYPE;
	}

}
