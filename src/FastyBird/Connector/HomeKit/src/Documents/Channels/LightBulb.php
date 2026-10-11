<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Documents\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\LightBulb::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\LightBulb::TYPE)]
final class LightBulb extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\LightBulb::TYPE;
	}

}
