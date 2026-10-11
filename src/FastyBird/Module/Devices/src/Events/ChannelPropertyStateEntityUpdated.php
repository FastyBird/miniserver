<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Channel property state entity was updated event
 */
class ChannelPropertyStateEntityUpdated extends EventDispatcher\Event
{

	public function __construct(
		private readonly Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped $property,
		private readonly States\ChannelProperty $read,
		private readonly States\ChannelProperty $get,
		private readonly Sources\Source $source,
	)
	{
	}

	public function getProperty(): Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped
	{
		return $this->property;
	}

	public function getRead(): States\ChannelProperty
	{
		return $this->read;
	}

	public function getGet(): States\ChannelProperty
	{
		return $this->get;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

}
