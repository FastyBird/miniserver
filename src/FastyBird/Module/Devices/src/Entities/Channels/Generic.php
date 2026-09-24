<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           08.06.24
 */

namespace FastyBird\Module\Devices\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Generic extends Entities\Channels\Channel
{

	public const TYPE = 'generic';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

}
