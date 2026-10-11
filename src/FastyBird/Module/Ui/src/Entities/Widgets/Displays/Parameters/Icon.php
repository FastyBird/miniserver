<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Module\Ui\Types;

/**
 * Display icon parameter interface
 */
interface Icon
{

	public function setIcon(Types\WidgetIcon $icon): void;

	public function getIcon(): Types\WidgetIcon|null;

}
