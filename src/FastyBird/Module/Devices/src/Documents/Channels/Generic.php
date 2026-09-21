<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           08.06.24
 */

namespace FastyBird\Module\Devices\Documents\Channels;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Generic::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Generic::TYPE)]
class Generic extends Documents\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Generic::TYPE;
	}

}
