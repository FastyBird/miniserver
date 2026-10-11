<?php declare(strict_types = 1);

/**
 * Actuator.php
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

namespace FastyBird\Module\Ui\Entities\Widgets;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class Actuator extends Widget
{

	public const string WIDGET_GROUP = 'actuator';

}
