<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Events;

use FastyBird\Module\Triggers\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after automator has been terminated
 */
final class AfterAutomatorTerminate extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Triggers\Trigger $trigger)
	{
	}

	public function getTrigger(): Entities\Triggers\Trigger
	{
		return $this->trigger;
	}

}
