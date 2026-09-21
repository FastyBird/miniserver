<?php declare(strict_types = 1);

/**
 * AnalogActuator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Documents\Widgets;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Widgets\AnalogActuator::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\AnalogActuator::TYPE)]
class AnalogActuator extends Documents\Widgets\Widget
{

	public static function getType(): string
	{
		return Entities\Widgets\AnalogActuator::TYPE;
	}

}
