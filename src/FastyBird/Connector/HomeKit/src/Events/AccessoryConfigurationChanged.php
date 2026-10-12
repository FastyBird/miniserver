<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Events;

use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after a connector's paired or configuration version variable property has changed
 */
final class AccessoryConfigurationChanged extends EventDispatcher\Event
{

	public function __construct(
		private readonly Entities\Connectors\Properties\Variable $property,
	)
	{
	}

	public function getProperty(): Entities\Connectors\Properties\Variable
	{
		return $this->property;
	}

}
