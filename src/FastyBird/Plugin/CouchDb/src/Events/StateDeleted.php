<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Events;

use FastyBird\Plugin\CouchDb\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is deleted event
 */
class StateDeleted extends EventDispatcher\Event
{

	public function __construct(private readonly States\State $state)
	{
	}

	public function getState(): States\State
	{
		return $this->state;
	}

}
