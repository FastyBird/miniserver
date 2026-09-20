<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Entities\Widgets\DataSources;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Entities\DoctrineCrud;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui\Schemas;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets_generic_data_sources',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Widget generic data source',
	],
)]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Generic extends DataSource
{

	public const TYPE = 'generic';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Module
	{
		return MetadataTypes\Sources\Module::UI;
	}

	public function hasRelation(string $relation): bool
	{
		return $relation === Schemas\Widgets\DataSources\Generic::RELATIONSHIPS_WIDGET;
	}

	public function getRelation(string $relation): DoctrineCrud\IEntity|null
	{
		if ($relation === Schemas\Widgets\DataSources\Generic::RELATIONSHIPS_WIDGET) {
			return $this->getWidget();
		}

		return null;
	}

}
