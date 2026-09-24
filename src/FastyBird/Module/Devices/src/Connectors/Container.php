<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 * @since          1.0.0
 *
 * @date           21.01.24
 */

namespace FastyBird\Module\Devices\Connectors;

use Closure;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use Nette\Utils;
use React\Promise;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_key_exists;

/**
 * Devices connectors container
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Connector, EventDispatcher\EventSubscriberInterface
{

	/** @var array<Closure(Sources\Source $source, string|null $reason, Throwable|null $ex): void> */
	public array $onTerminate = [];

	/** @var array<Closure(Sources\Source $source, string|null $reason, Throwable|null $ex): void> */
	public array $onRestart = [];

	private Connector|null $service = null;

	/**
	 * @param array<string, ConnectorFactory> $factories
	 */
	public function __construct(
		private readonly array $factories,
		private readonly Documents\Connectors\Connector $connector,
		EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$dispatcher?->addSubscriber($this);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\TerminateConnector::class => 'processTermination',
			Events\RestartConnector::class => 'processRestart',
		];
	}

	public function processTermination(Events\TerminateConnector $event): void
	{
		Utils\Arrays::invoke($this->onTerminate, $event->getSource(), $event->getReason(), $event->getException());
	}

	public function processRestart(Events\RestartConnector $event): void
	{
		Utils\Arrays::invoke($this->onRestart, $event->getSource(), $event->getReason(), $event->getException());
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->execute();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function discover(): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->discover();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function terminate(): void
	{
		$this->getService($this->connector)->terminate();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
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
