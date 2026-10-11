<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use function sprintf;

/**
 * Cloud client
 */
final readonly class Cloud implements Client
{

	public function __construct(
		private Documents\Connectors\Connector $connector,
		private PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	#[Override]
	public function connect(): void
	{
		$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
			Sources\Connector::SHELLY,
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

	#[Override]
	public function disconnect(): void
	{
		$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
			Sources\Connector::SHELLY,
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

}
