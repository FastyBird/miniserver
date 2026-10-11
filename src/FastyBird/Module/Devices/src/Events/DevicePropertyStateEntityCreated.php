<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Device property state entity was created event
 */
class DevicePropertyStateEntityCreated extends EventDispatcher\Event
{

	public function __construct(
		private readonly Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
		private readonly States\DeviceProperty $read,
		private readonly States\DeviceProperty $get,
		private readonly Sources\Source $source,
	)
	{
	}

	public function getProperty(): Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped
	{
		return $this->property;
	}

	public function getRead(): States\DeviceProperty
	{
		return $this->read;
	}

	public function getGet(): States\DeviceProperty
	{
		return $this->get;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

}
