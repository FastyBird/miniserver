<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\DataSources;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Schemas;
use Override;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets_generic_data_sources',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Widget generic data source',
	],
)]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Generic extends DataSource
{

	public const string TYPE = 'generic';

	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Module
	{
		return Sources\Module::UI;
	}

	public function hasRelation(string $relation): bool
	{
		return $relation === Schemas\Widgets\DataSources\Generic::RELATIONSHIPS_WIDGET;
	}

	public function getRelation(string $relation): Entities\CrudEntity|null
	{
		if ($relation === Schemas\Widgets\DataSources\Generic::RELATIONSHIPS_WIDGET) {
			return $this->getWidget();
		}

		return null;
	}

}
