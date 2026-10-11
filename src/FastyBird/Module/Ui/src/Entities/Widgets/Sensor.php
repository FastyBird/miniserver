<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class Sensor extends Widget
{

	public const string WIDGET_GROUP = 'sensor';

}
