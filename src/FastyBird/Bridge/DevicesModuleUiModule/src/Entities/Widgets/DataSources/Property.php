<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Entities\Widgets\DataSources;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Library\DoctrineCrud;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Entities as UiEntities;
use function array_merge;
use function in_array;

#[ORM\MappedSuperclass]
abstract class Property extends UiEntities\Widgets\DataSources\DataSource
{

	abstract public function getProperty(): DevicesEntities\Property;

	public function hasRelation(string $relation): bool
	{
		return in_array(
			$relation,
			[
				Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_WIDGET,
				Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_PROPERTY,
			],
			true,
		);
	}

	public function getRelation(string $relation): DoctrineCrud\Entities\IEntity|null
	{
		if ($relation === Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_WIDGET) {
			return $this->getWidget();
		} elseif ($relation === Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_PROPERTY) {
			return $this->getProperty();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'property' => $this->getProperty()->getId()->toString(),
		]);
	}

}
