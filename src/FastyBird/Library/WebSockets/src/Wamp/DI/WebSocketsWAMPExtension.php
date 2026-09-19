<?php declare(strict_types = 1);

/**
 * WebSocketsWAMPExtension.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           01.03.17
 */

namespace FastyBird\Library\WebSockets\Wamp\DI;

use FastyBird\Library\WebSockets\Clients as WebSocketsClients;
use FastyBird\Library\WebSockets\Server as WebSocketsServer;
use FastyBird\Library\WebSockets\Wamp\Application;
use FastyBird\Library\WebSockets\Wamp\Clients;
use FastyBird\Library\WebSockets\Wamp\Events;
use FastyBird\Library\WebSockets\Wamp\PushMessages;
use FastyBird\Library\WebSockets\Wamp\Serializers;
use FastyBird\Library\WebSockets\Wamp\Subscribers;
use FastyBird\Library\WebSockets\Wamp\Topics;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Symfony\Component\EventDispatcher;
use function assert;
use function interface_exists;

/**
 * WebSockets WAMP extension container
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class WebSocketsWAMPExtension extends DI\CompilerExtension
{

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'storage' => Schema\Expect::structure([
				'topics' => Schema\Expect::structure([
					'driver' => Schema\Expect::string('@topics.driver.memory'),
					'ttl' => Schema\Expect::int(0),
				]),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();

		$storageDriver = $configuration->storage->topics->driver === '@topics.driver.memory' ? $builder->addDefinition(
			$this->prefix('topics.driver.memory'),
		)
			->setType(Topics\Drivers\InMemory::class) : $builder->getDefinition(
				$this->prefix('topics.driver.memory'),
			);

		$builder->addDefinition($this->prefix('topics.storage'))
			->setType(Topics\Storage::class)
			->setArguments([
				'ttl' => $configuration->storage->topics->ttl,
			])
			->addSetup('?->setStorageDriver(?)', ['@' . $this->prefix('topics.storage'), $storageDriver]);

		$builder->addDefinition($this->prefix('application'))
			->setType(Application\Application::class);

		$builder->addDefinition($this->prefix('serializer'))
			->setType(Serializers\PushMessageSerializer::class);

		/**
		 * PUSH NOTIFICATION
		 */

		$builder->addDefinition($this->prefix('push.registry'))
			->setType(PushMessages\ConsumersRegistry::class);

		/**
		 * CLIENTS
		 */

		if ($builder->getByType(WebSocketsClients\IClientFactory::class) !== null) {
			$builder->removeDefinition($builder->getByType(WebSocketsClients\IClientFactory::class));
		}

		$builder->addDefinition($this->prefix('clients.factory'))
			->setType(Clients\ClientFactory::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.onServerStart'))
			->setType(Subscribers\OnServerStartHandler::class);
	}

	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$registry = $builder->getDefinition($builder->getByType(PushMessages\ConsumersRegistry::class));

		$consumers = $builder->findByType(PushMessages\IConsumer::class);

		foreach ($consumers as $consumer) {
			$registry->addSetup('?->addConsumer(?)', [$registry, $consumer]);
		}

		$server = $builder->getDefinitionByType(WebSocketsServer\Server::class);
		$server->addSetup('$service->onStart[] = ?', ['@' . $this->prefix('subscribers.onServerStart')]);

		/**
		 * EVENTS
		 */

		if (
			interface_exists('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			&& $builder->getByType(EventDispatcher\EventDispatcherInterface::class) !== null
		) {
			$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

			$application = $builder->getDefinition($builder->getByType(Application\Application::class));
			assert($application instanceof DI\Definitions\ServiceDefinition);

			$application->addSetup('?->onPush[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self',
				$dispatcher,
				new PhpGenerator\Literal(Events\Application\PushEvent::class),
			]);
		}
	}

	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'webSocketsWAMP',
	): void
	{
		$config->onCompile[] = static function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

}
