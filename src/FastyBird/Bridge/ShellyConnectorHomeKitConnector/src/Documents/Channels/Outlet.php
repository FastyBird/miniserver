<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Outlet::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Outlet::TYPE)]
final class Outlet extends Shelly
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Outlet::TYPE;
	}

}
