<?php declare(strict_types = 1);

/**
 * DigitalValue.php
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

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Widgets\Displays\DigitalValue::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\DigitalValue::TYPE)]
class DigitalValue extends Documents\Widgets\Displays\Display
{

	public static function getType(): string
	{
		return Entities\Widgets\Displays\DigitalValue::TYPE;
	}

}
