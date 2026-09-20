<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use function sprintf;

/**
 * Cloud client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud implements Client
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function connect(): void
	{
		$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
			MetadataTypes\Sources\Connector::SHELLY,
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

	public function disconnect(): void
	{
		$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
			MetadataTypes\Sources\Connector::SHELLY,
			sprintf('Cloud client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

}
