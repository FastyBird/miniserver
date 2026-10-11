<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Core\Values\Types\Sources;
use Override;
use function sprintf;

/**
 * Gateway client
 */
final readonly class Gateway implements Client
{

	public function __construct(
		private Documents\Connectors\Connector $connector,
		private Sonoff\Logger $logger,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	#[Override]
	public function connect(): void
	{
		$this->logger->error(
			'Trying to connect with gateway client',
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'gateway-client',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		throw new Exceptions\NotImplemented(
			sprintf('Gateway client is not implemented: %s', $this->connector->getId()->toString()),
		);
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	#[Override]
	public function disconnect(): void
	{
		$this->logger->error(
			'Trying to disconnect with gateway client',
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'gateway-client',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		throw new Exceptions\NotImplemented(
			sprintf('Gateway client is not implemented: %s', $this->connector->getId()->toString()),
		);
	}

}
