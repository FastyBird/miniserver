<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Events;

use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after a connector's shared key variable property has been created or updated
 */
final class SharedKeyChanged extends EventDispatcher\Event
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
