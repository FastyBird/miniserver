<?php declare(strict_types = 1);

/**
 * FbMqttV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use InvalidArgumentException;
use Throwable;
use TypeError;
use ValueError;
use function assert;
use function explode;
use function sprintf;
use function str_contains;

/**
 * FastyBird MQTT v1 client
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttV1 implements Client
{

	public const MQTT_SYSTEM_TOPIC = '$SYS/broker/log/#';

	// MQTT api topics subscribe format
	public const DEVICES_TOPICS = [
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+/+/+',
	];

	// When new client is connected, broker send specific payload
	private const NEW_CLIENT_MESSAGE_PAYLOAD = 'New client connected from';

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly FbMqtt\Logger $logger,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$client = $this->connectionManager->getConnection($this->connector);

		$client->onConnect[] = function (Mqtt\Connection $connection): void {
			$this->onConnect($connection);
		};
		$client->onMessage[] = function (Mqtt\Message $message): void {
			$this->onMessage($message);
		};

		$client->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		$client = $this->connectionManager->getConnection($this->connector);

		$client->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function onConnect(Mqtt\Connection $connection): void
	{
		$systemTopic = new Mqtt\DefaultSubscription(self::MQTT_SYSTEM_TOPIC);

		// Subscribe to system topic
		$this->connectionManager->getConnection($this->connector)
			->subscribe($systemTopic)
			->then(
				function (mixed $subscription): void {
					assert($subscription instanceof Mqtt\Subscription);
					$this->logger->info(
						sprintf('Subscribed to: %s', $subscription->getFilter()),
						[
							'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
							'type' => 'fb-mqtt-v1-client',
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);
				},
				function (Throwable $ex): void {
					$this->logger->error(
						$ex->getMessage(),
						[
							'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
							'type' => 'fb-mqtt-v1-client',
							'exception' => Logging\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);
				},
			);

		// Get all device topics...
		foreach (self::DEVICES_TOPICS as $topic) {
			$topic = new Mqtt\DefaultSubscription($topic);

			// ...& subscribe to them
			$this->connectionManager->getConnection($this->connector)
				->subscribe($topic)
				->then(
					function (mixed $subscription): void {
						assert($subscription instanceof Mqtt\Subscription);
						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
				);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function onMessage(Mqtt\Message $message): void
	{
		// Check for broker system topic
		if (str_contains($message->getTopic(), '$SYS')) {
			[,
				$param1,
				$param2,
				$param3,
			] = explode(FbMqtt\Constants::MQTT_TOPIC_DELIMITER, $message->getTopic()) + [
				null,
				null,
				null,
				null,
			];

			$payload = $message->getPayload();

			// Broker log
			if ($param1 === 'broker' && $param2 === 'log') {
				switch ($param3) {
					// Notice
					case 'N':
						$this->logger->notice(
							$payload,
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						// Nev device connected message
						if (str_contains($message->getPayload(), self::NEW_CLIENT_MESSAGE_PAYLOAD)) {
							[,,,,,
								$ipAddress,,
								$deviceId,,,
								$username,
							] = explode(' ', $message->getPayload()) + [
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
							];

							// Check for correct data
							if ($username !== null && $deviceId !== null && $ipAddress !== null) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\DeviceProperty::class,
										[
											'connector' => $this->connector->getId(),
											'device' => $deviceId,
											'property' => 'ip-address',
											'value' => $ipAddress,
										],
									),
								);
							}
						}

						break;

					// Error
					case 'E':
						$this->logger->error(
							$payload,
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						break;

					// Information
					case 'I':
						$this->logger->info(
							$payload,
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						break;
					default:
						$this->logger->debug(
							$param3 . ': ' . $payload,
							[
								'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						break;
				}
			}

			return;
		}

		// Connected device topic
		if (
			API\V1Validator::validateConvention($message->getTopic())
			&& API\V1Validator::validateVersion($message->getTopic())
		) {
			// Check if message is sent from broker
			if (!API\V1Validator::validate($message->getTopic())) {
				return;
			}

			try {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\DeviceProperty::class,
						API\V1Parser::parse(
							$this->connector->getId(),
							$message->getTopic(),
							$message->getPayload(),
							$message->isRetained(),
						),
					),
				);
			} catch (Exceptions\ParseMessage $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
						'type' => 'fb-mqtt-v1-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			}
		}
	}

}
