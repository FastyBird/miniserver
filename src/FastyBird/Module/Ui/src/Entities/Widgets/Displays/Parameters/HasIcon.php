<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Ui\Types;
use function is_string;

/**
 * Display icon parameter
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait HasIcon
{

	#[Attribute\Crud(writable: true)]
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
