<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Events;

use FastyBird\Plugin\CouchDb\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is updated event
 */
final class StateUpdated extends EventDispatcher\Event
{

	public function __construct(
		private readonly States\State $newState,
		private readonly States\State $previousState,
	)
	{
	}

	public function getNewState(): States\State
	{
		return $this->newState;
	}

	public function getPreviousState(): States\State
	{
		return $this->previousState;
	}

}
