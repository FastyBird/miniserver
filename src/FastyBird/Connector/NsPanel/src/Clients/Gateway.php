<?php declare(strict_types = 1);

/**
 * Gateway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function in_array;
use function is_string;
use function preg_match;
use function React\Async\async;
use function strval;

/**
 * Connector gateway client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gateway implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string, Documents\Devices\Gateway>  */
	private array $gateways = [];

	/** @var array<string> */
	private array $processedGateways = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedGatewaysCommands = [];

	private API\LanApi $lanApi;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Protocol\Driver $devicesDriver,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly Clock\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->lanApi = $this->lanApiFactory->create($this->connector->getId());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$this->processedGateways = [];
		$this->processedGatewaysCommands = [];

		$this->handlerTimer = null;

		$findDevicesQuery = new Queries\Configuration\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();

		$gateways = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Gateway::class,
		);

		foreach ($gateways as $gateway) {
			$this->gateways[$gateway->getId()->toString()] = $gateway;
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleCommunication(): void
	{
		foreach ($this->gateways as $gateway) {
			if (!in_array($gateway->getId()->toString(), $this->processedGateways, true)) {
				$this->processedGateways[] = $gateway->getId()->toString();

				if ($this->processGateway($gateway)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedGateways = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processGateway(Documents\Devices\Gateway $gateway): bool
	{
		if ($this->readGatewayInformation($gateway)) {
			return true;
		}

		return $this->readGatewayState($gateway);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readGatewayInformation(Documents\Devices\Gateway $gateway): bool
	{
		if (
			$this->gatewayHelper->getIpAddress($gateway) === null
			|| $this->gatewayHelper->getAccessToken($gateway) === null
		) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'device' => $gateway->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			// Remove gateway device from list of devices to process
			unset($this->gateways[$gateway->getId()->toString()]);
			unset($this->processedGateways[$gateway->getId()->toString()]);
			unset($this->processedGatewaysCommands[$gateway->getId()->toString()]);

			return true;
		}

		if (!array_key_exists($gateway->getId()->toString(), $this->processedGatewaysCommands)) {
			$this->processedGatewaysCommands[$gateway->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedGatewaysCommands[$gateway->getId()->toString()])) {
			$cmdResult = $this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $this->gatewayHelper->getHeartbeatDelay(
						$gateway,
					)
				)
			) {
				return false;
			}
		}

		$this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->clock->getNow();

		$gatewayState = $this->deviceConnectionManager->getState($gateway);

		if ($gatewayState === DevicesTypes\ConnectionState::ALERT) {
			// Remove gateway device from list of devices to process
			unset($this->gateways[$gateway->getId()->toString()]);
			unset($this->processedGateways[$gateway->getId()->toString()]);
			unset($this->processedGatewaysCommands[$gateway->getId()->toString()]);

			return false;
		}

		try {
			$this->lanApi->getGatewayInfo($this->gatewayHelper->getIpAddress($gateway))
				->then(function () use ($gateway): void {
					$this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->clock->getNow();

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'device' => $gateway->getId(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);
				})
				->catch(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof NsPanelExceptions\LanApiError) {
						$this->logger->error(
							'Calling NS Panel API failed with error',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'device' => $gateway->getId(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);
					} elseif ($ex instanceof NsPanelExceptions\LanApiCall) {
						$this->logger->error(
							'Could not NS Panel API',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'method' => $ex->getRequest()?->getMethod(),
									'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getResponse()?->getBody()->getContents(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'device' => $gateway->getId(),
									'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					} else {
						$this->logger->error(
							'Calling NS Panel API failed',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								Sources\Connector::NS_PANEL,
								'Unhandled error occur',
							),
						);
					}
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'gateway-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $gateway->getConnector()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readGatewayState(Documents\Devices\Gateway $gateway): bool
	{
		if (
			$this->gatewayHelper->getIpAddress($gateway) === null
			|| $this->gatewayHelper->getAccessToken($gateway) === null
		) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'device' => $gateway->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			// Remove gateway device from list of devices to process
			unset($this->gateways[$gateway->getId()->toString()]);
			unset($this->processedGateways[$gateway->getId()->toString()]);
			unset($this->processedGatewaysCommands[$gateway->getId()->toString()]);

			return true;
		}

		if (!array_key_exists($gateway->getId()->toString(), $this->processedGatewaysCommands)) {
			$this->processedGatewaysCommands[$gateway->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedGatewaysCommands[$gateway->getId()->toString()])) {
			$cmdResult = $this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $this->gatewayHelper->getStateReadingDelay(
						$gateway,
					)
				)
			) {
				return false;
			}
		}

		$this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

		$deviceState = $this->deviceConnectionManager->getState($gateway);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			// Remove gateway device from list of devices to process
			unset($this->gateways[$gateway->getId()->toString()]);
			unset($this->processedGateways[$gateway->getId()->toString()]);
			unset($this->processedGatewaysCommands[$gateway->getId()->toString()]);

			return false;
		}

		try {
			$this->lanApi->getSubDevices(
				$this->gatewayHelper->getIpAddress($gateway),
				$this->gatewayHelper->getAccessToken($gateway),
			)
				->then(function (API\Messages\Response\GetSubDevices $subDevices) use ($gateway): void {
					$this->processedGatewaysCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'device' => $gateway->getId(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);

					foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
						// Ignore third-party devices
						if ($subDevice->getThirdSerialNumber() !== null) {
							continue;
						}

						$protocolDevice = $this->devicesDriver->findDevice($subDevice->getSerialNumber());

						if ($protocolDevice === null) {
							continue;
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $protocolDevice->getConnector(),
									'device' => $protocolDevice->getId(),
									'state' => $subDevice->isOnline()
										? DevicesTypes\ConnectionState::CONNECTED
										: DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);

						$state = [];

						foreach ($subDevice->getState() as $key => $item) {
							$identifier = null;

							if (
								is_string($key)
								&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
								&& array_key_exists('name', $matches) === true
							) {
								$identifier = $matches['name'];
							}

							foreach ($item->getState() as $attribute => $value) {
								$state[] = [
									'capability' => $item->getType()->value,
									'attribute' => $attribute,
									'value' => $value,
									'identifier' => $identifier,
								];
							}
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceState::class,
								[
									'connector' => $protocolDevice->getConnector(),
									'gateway' => $protocolDevice->getParent(),
									'device' => $protocolDevice->getId(),
									'state' => $state,
								],
							),
						);
					}
				})
				->catch(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof NsPanelExceptions\LanApiError) {
						$this->logger->error(
							'Calling NS Panel API failed with error',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'device' => $gateway->getId(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);
					} elseif ($ex instanceof NsPanelExceptions\LanApiCall) {
						$this->logger->warning(
							'Calling NS Panel API failed',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'error' => $ex->getMessage(),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'method' => $ex->getRequest()?->getMethod(),
									'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getResponse()?->getBody()->getContents(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'device' => $gateway->getId(),
									'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					} else {
						$this->logger->error(
							'Calling NS Panel API failed',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								Sources\Connector::NS_PANEL,
								'Unhandled error occur',
							),
						);
					}
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'gateway-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $gateway->getConnector()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
