<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use Closure;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Events as HomeKitEvents;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Middleware;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Socket;
use Symfony\Component\EventDispatcher;
use Throwable;
use TypeError;
use ValueError;
use function hex2bin;
use function is_string;

/**
 * HTTP connector communication server
 */
final class Http implements Server
{

	public const string REQUEST_ATTRIBUTE_CONNECTOR = 'connector';

	public const string PAIRING_CONTENT_TYPE = 'application/pairing+tlv8';

	public const string JSON_CONTENT_TYPE = 'application/hap+json';

	private const string LISTENING_ADDRESS = '0.0.0.0';

	private SecureServer|null $socket = null;

	/** @var (Closure(HomeKitEvents\SharedKeyChanged $event): void)|null */
	private Closure|null $sharedKeyListener = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Middleware\Router $routerMiddleware,
		private readonly SecureServerFactory $secureServerFactory,
		private readonly Clients\Subscriber $subscriber,
		private readonly Protocol\Driver $accessoriesDriver,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly HomeKit\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	#[Override]
	public function initialize(): void
	{
		if ($this->sharedKeyListener !== null) {
			return;
		}

		$this->sharedKeyListener = function (HomeKitEvents\SharedKeyChanged $event): void {
			$this->setSharedKey($event->getProperty());
		};

		$this->dispatcher?->addListener(HomeKitEvents\SharedKeyChanged::class, $this->sharedKeyListener);
	}

	/**
	 * @throws HomeKitExceptions\Runtime
	 */
	#[Override]
	public function connect(): void
	{
		try {
			$this->logger->debug(
				'Creating HAP web server',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'server' => [
						'address' => self::LISTENING_ADDRESS,
						'port' => $this->connectorHelper->getPort($this->connector),
					],
				],
			);

			$this->socket = $this->secureServerFactory->create(
				$this->connector,
				new Socket\SocketServer(
					self::LISTENING_ADDRESS . ':' . $this->connectorHelper->getPort($this->connector),
					[],
					$this->eventLoop,
				),
			);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Socket server could not be created',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				Sources\Connector::HOMEKIT,
				'Socket server could not be created',
				$ex,
			));

			return;
		}

		$this->socket->on('connection', function (Socket\ConnectionInterface $connection): void {
			$this->logger->debug(
				'New client has connected to server',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'client' => [
						'address' => $connection->getRemoteAddress(),
					],
				],
			);

			$this->subscriber->registerConnection($connection);

			$connection->on('close', function () use ($connection): void {
				$this->logger->debug(
					'Connected client has closed connection',
					[
						'source' => Sources\Connector::HOMEKIT->value,
						'type' => 'http-server',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'client' => [
							'address' => $connection->getRemoteAddress(),
						],
					],
				);

				$this->subscriber->unregisterConnection($connection);
			});
		});

		$this->socket->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during socket handling',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				Sources\Connector::HOMEKIT,
				'HTTP server was terminated',
				$ex,
			));
		});

		$this->socket->on('close', function (): void {
			$this->logger->info(
				'Server was closed',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);
		});

		$server = new ReactHttp\HttpServer(
			$this->eventLoop,
			function (ServerRequestInterface $request, callable $next): ResponseInterface {
				$request = $request->withAttribute(
					self::REQUEST_ATTRIBUTE_CONNECTOR,
					$this->connector->getId()->toString(),
				);

				return $next($request);
			},
			$this->routerMiddleware,
		);
		$server->listen($this->socket);

		$server->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during server handling',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				Sources\Connector::HOMEKIT,
				'HTTP server was terminated',
				$ex,
			));
		});

		foreach ($this->accessoriesDriver->getAccessories() as $accessory) {
			if ($accessory instanceof Protocol\Accessories\Generic) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $accessory->getDevice()->getConnector(),
							'device' => $accessory->getDevice()->getId(),
							'state' => DevicesTypes\ConnectionState::CONNECTED,
						],
					),
				);
			}
		}
	}

	/**
	 * @throws HomeKitExceptions\Runtime
	 */
	#[Override]
	public function disconnect(): void
	{
		$this->logger->debug(
			'Closing HAP web server',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'http-server',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		if ($this->sharedKeyListener !== null) {
			$this->dispatcher?->removeListener(HomeKitEvents\SharedKeyChanged::class, $this->sharedKeyListener);

			$this->sharedKeyListener = null;
		}

		$this->socket?->close();

		$this->socket = null;

		foreach ($this->accessoriesDriver->getAccessories() as $accessory) {
			if ($accessory instanceof Protocol\Accessories\Generic) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $accessory->getDevice()->getConnector(),
							'device' => $accessory->getDevice()->getId(),
							'state' => DevicesTypes\ConnectionState::DISCONNECTED,
						],
					),
				);
			}
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function setSharedKey(
		DevicesEntities\Connectors\Properties\Variable $property,
	): void
	{
		if (
			$property->getConnector()->getId()->equals($this->connector->getId())
			&& $property->getIdentifier() === HomeKitTypes\ConnectorPropertyIdentifier::SHARED_KEY->value
		) {
			$this->logger->debug(
				'Shared key has been updated',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->socket?->setSharedKey(
				is_string($property->getValue()) ? (string) hex2bin($property->getValue()) : null,
			);
		}
	}

}
