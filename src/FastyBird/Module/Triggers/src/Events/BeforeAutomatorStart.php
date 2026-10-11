<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Events;

use FastyBird\Module\Triggers\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired before automator has been started
 */
final class BeforeAutomatorStart extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Triggers\Trigger $trigger)
	{
	}

	public function getTrigger(): Entities\Triggers\Trigger
	{
		return $this->trigger;
	}

}
