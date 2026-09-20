<?php declare(strict_types = 1);

/**
 * TIcon.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Module\Ui\Types;
use function is_string;

/**
 * Display icon parameter
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TIcon
{

	#[IPubDoctrine\Crud(writable: true)]
	protected Types\WidgetIcon|null $icon = null;

	public function getIcon(): Types\WidgetIcon|null
	{
		$value = $this->getParam('icon');

		return is_string($value) && $value !== '' ? Types\WidgetIcon::tryFrom($value) : null;
	}

	public function setIcon(Types\WidgetIcon|null $icon): void
	{
		$this->icon = $icon;

		$this->setParam('icon', $icon?->value ?? null);
	}

}
