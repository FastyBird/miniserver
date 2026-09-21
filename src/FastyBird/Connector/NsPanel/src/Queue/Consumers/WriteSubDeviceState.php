<?php declare(strict_types = 1);

/**
 * WriteSubDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Write state to sub-device message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteSubDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	private API\LanApi|null $lanApiApi = null;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\LanApiFactory $lanApiApiFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Protocol\Driver $devicesDriver,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Clock $clock,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteSubDeviceState) {
			return false;
		}

		$protocolDevice = $this->devicesDriver->findDevice($message->getDevice());

		if (
			!$protocolDevice instanceof Protocol\Devices\SubDevice
			|| !$protocolDevice->getConnector()->equals($message->getConnector())
		) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new Queries\Configuration\FindGatewayDevices();
		$findDeviceQuery->byId($protocolDevice->getParent());

		$gateway = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Gateway::class,
		);

		if ($gateway === null) {
			$this->logger->error(
				'Device assigned gateway could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
		$accessToken = $this->gatewayHelper->getAccessToken($gateway);

		if ($ipAddress === null || $accessToken === null) {
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

			$this->logger->error(
				'Device owning NS Panel is not configured',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$protocolCapability = $protocolDevice->findCapability($message->getChannel());

		if ($protocolCapability === null) {
			$this->logger->error(
				'Device capability could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if (
			$protocolCapability->getPermission() !== NsPanel\Types\Permission::READ_WRITE
			&& $protocolCapability->getPermission() !== NsPanel\Types\Permission::WRITE
		) {
			$this->logger->error(
				'Device capability is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$protocolAttribute = $protocolCapability->findAttribute($message->getProperty());

		if ($protocolAttribute === null) {
			$this->logger->error(
				'Device capability attribute could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$propertyToUpdate = $this->channelsPropertiesConfigurationRepository->find($protocolAttribute->getId());

		if ($propertyToUpdate === null) {
			$this->logger->error(
				'Device capability attribute mapped property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if (
			$propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic
			&& !$propertyToUpdate->isSettable()
		) {
			$this->logger->warning(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if (!$propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$this->logger->warning(
				'Channel property type is not supported to write value',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$state = $message->getState();

		if ($state === null) {
			return true;
		}

		if ($state->getExpectedValue() === null) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$propertyToUpdate,
				false,
				MetadataTypes\Sources\Connector::NS_PANEL,
			));

			return true;
		}

		$now = $this->clock->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= self::WRITE_PENDING_DELAY
			)
		) {
			return true;
		}

		await($this->channelPropertiesStatesManager->setPendingState(
			$propertyToUpdate,
			true,
			MetadataTypes\Sources\Connector::NS_PANEL,
		));

		$protocolAttribute->setExpectedValue(
			ToolsUtilities\Value::flattenValue($state->getExpectedValue()),
		);

		$mapped = $protocolCapability->toState();

		try {
			$this->getApiClient($protocolDevice->getConnector())->setSubDeviceState(
				$protocolDevice->getIdentifier(),
				$mapped,
				$ipAddress,
				$accessToken,
			)
				->then(function () use ($propertyToUpdate, $state, $message): void {
					await($this->channelPropertiesStatesManager->set(
						$propertyToUpdate,
						Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
							DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
						]),
						MetadataTypes\Sources\Connector::NS_PANEL,
					));

					$this->logger->debug(
						'Channel state was successfully sent to device',
						[
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'write-sub-device-state-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'device' => [
								'id' => $message->getDevice()->toString(),
							],
							'channel' => [
								'id' => $message->getChannel()->toString(),
							],
							'property' => [
								'id' => $message->getProperty()->toString(),
							],
							'data' => $message->toArray(),
						],
					);
				})
				->catch(
					async(
						function (Throwable $ex) use ($gateway, $message, $propertyToUpdate): void {
							await($this->channelPropertiesStatesManager->setPendingState(
								$propertyToUpdate,
								false,
								MetadataTypes\Sources\Connector::NS_PANEL,
							));

							$extra = [];

							if ($ex instanceof Exceptions\LanApiCall) {
								$extra = [
									'request' => [
										'method' => $ex->getRequest()?->getMethod(),
										'url' => $ex->getRequest() !== null ? strval(
											$ex->getRequest()->getUri(),
										) : null,
										'body' => $ex->getRequest()?->getBody()->getContents(),
									],
									'response' => [
										'body' => $ex->getResponse()?->getBody()->getContents(),
									],
								];

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
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'device' => $gateway->getId(),
											'state' => DevicesTypes\ConnectionState::LOST,
										],
									),
								);
							}

							$this->logger->error(
								'Could write state to sub-device',
								array_merge(
									[
										'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
										'type' => 'write-sub-device-state-message-consumer',
										'exception' => ToolsHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $message->getConnector()->toString(),
										],
										'device' => [
											'id' => $message->getDevice()->toString(),
										],
										'channel' => [
											'id' => $message->getChannel()->toString(),
										],
										'property' => [
											'id' => $message->getProperty()->toString(),
										],
										'data' => $message->toArray(),
									],
									$extra,
								),
							);
						},
					),
				);
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);
		}

		$this->logger->debug(
			'Consumed write sub-device state message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'write-sub-device-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $message->getDevice()->toString(),
				],
				'channel' => [
					'id' => $message->getChannel()->toString(),
				],
				'property' => [
					'id' => $message->getProperty()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	private function getApiClient(Uuid\UuidInterface $connector): API\LanApi
	{
		if ($this->lanApiApi === null) {
			$this->lanApiApi = $this->lanApiApiFactory->create($connector);
		}

		return $this->lanApiApi;
	}

}
