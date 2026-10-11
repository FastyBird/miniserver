<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Documents\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Battery::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Battery::TYPE)]
final class Battery extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Battery::TYPE;
	}

}
