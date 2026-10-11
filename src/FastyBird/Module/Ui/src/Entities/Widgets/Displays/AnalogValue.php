<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Module\Ui\Entities;
use Override;
use function array_merge;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class AnalogValue extends Display implements Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\HasPrecision;

	public const string TYPE = 'analog-value';

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'precision' => $this->getPrecision(),
		]);
	}

}
