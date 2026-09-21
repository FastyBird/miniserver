<?php declare(strict_types = 1);

/**
 * DigitalActuator.php
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
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Module\Ui\Entities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class DigitalActuator extends Actuator
{

	public const TYPE = 'digital-actuator';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getAllowedDisplayTypes(): array
	{
		return [
			Entities\Widgets\Displays\Button::class,
		];
	}

}
