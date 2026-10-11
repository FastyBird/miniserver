<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Module\Ui\Entities;
use function array_merge;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Button extends Display implements Entities\Widgets\Displays\Parameters\Icon
{

	use Entities\Widgets\Displays\Parameters\TIcon;

	public const string TYPE = 'button';

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'icon' => $this->getIcon()?->value,
		]);
	}

}
