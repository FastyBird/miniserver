<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Exchange;

/**
 * An exchange factory that starts nothing, used to see which exchange factories a command
 * receives from the container
 */
final class DummyExchangeFactory implements Exchange\Factory
{

	public function create(): void
	{
		// Nothing to start
	}

}
