<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use function sprintf;

/**
 * MQTT client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mqtt implements Client
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
			Sources\Connector::SHELLY,
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

	public function disconnect(): void
	{
		$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
			Sources\Connector::SHELLY,
			sprintf('MQTT client is not implemented for connector %s', $this->connector->getIdentifier()),
		));
	}

}
