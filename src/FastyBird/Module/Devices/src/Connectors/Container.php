<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Override;
use React\Promise;
use function array_key_exists;

/**
 * Devices connectors container
 */
final class Container implements Connector
{

	private Connector|null $service = null;

	/**
	 * @param array<string, ConnectorFactory> $factories
	 */
	public function __construct(
		private readonly array $factories,
		private readonly Documents\Connectors\Connector $connector,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->execute();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function discover(): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->discover();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function terminate(): void
	{
		$this->getService($this->connector)->terminate();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function hasUnfinishedTasks(): bool
	{
		return $this->getService($this->connector)->hasUnfinishedTasks();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getService(Documents\Connectors\Connector $connector): Connector
	{
		if ($this->service === null) {
			$factory = $this->getServiceFactory($connector);

			$this->service = $factory->create($connector);
		}

		return $this->service;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getServiceFactory(Documents\Connectors\Connector $connector): ConnectorFactory
	{
		if (array_key_exists($connector::getType(), $this->factories)) {
			return $this->factories[$connector::getType()];
		}

		throw new Exceptions\InvalidState('Connector service factory is not registered');
	}

}
