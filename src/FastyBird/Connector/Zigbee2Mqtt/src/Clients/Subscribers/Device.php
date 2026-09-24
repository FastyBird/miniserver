<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients\Subscribers;

use BinSoul\Net\Mqtt as NetMqtt;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_merge;
use function is_array;
use function strval;

/**
 * Zigbee2MQTT MQTT devices messages subscriber
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Device
{

	public function __construct(
		private Documents\Connectors\Connector|Entities\Connectors\Connector $connector,
		private Zigbee2Mqtt\Logger $logger,
		private Queue\Queue $queue,
		private Helpers\MessageBuilder $messageBuilder,
	)
	{
	}

	public function subscribe(API\Client $client): void
	{
		$client->onMessage[] = function (NetMqtt\Message $message): void {
			$this->onMessage($message);
		};
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function onMessage(NetMqtt\Message $message): void
	{
		if (API\MqttValidator::validateTopic($message->getTopic())) {
			// Check if message is sent from broker
			if (!API\MqttValidator::validate($message->getTopic())) {
				return;
			}

			// Skip messages related to bridge
			if (API\MqttValidator::validateBridge($message->getTopic())) {
				return;
			}

			try {
				if (API\MqttValidator::validateDevice($message->getTopic())) {
					$data = API\MqttParser::parse(
						$this->connector->getId(),
						$message->getTopic(),
						$message->getPayload(),
					);

					if (array_key_exists('type', $data)) {
						if (Types\DeviceMessageType::tryFrom(strval($data['type'])) === null) {
							throw new Exceptions\ParseMessage('Received unsupported device message type');
						}

						$type = Types\DeviceMessageType::from(strval($data['type']));

						if ($type === Types\DeviceMessageType::AVAILABILITY) {
							if (
								$message->getPayload() !== ''
								&& Types\ConnectionState::tryFrom($message->getPayload()) !== null
							) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										array_merge($data, ['state' => $message->getPayload()]),
									),
								);

							} else {
								$payload = $this->parsePayload($message);

								if ($payload === null) {
									$this->logger->warning(
										'Received message payload is not valid for device connection state message',
										[
											'source' => Sources\Connector::ZIGBEE2MQTT->value,
											'type' => 'device-messages-subscriber',
											'connector' => [
												'id' => $this->connector->getId()->toString(),
											],
											'message' => [
												'topic' => $message->getTopic(),
												'payload' => $message->getPayload(),
												'qos' => $message->getQosLevel(),
											],
										],
									);

									return;
								}

								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										array_merge($data, $payload),
									),
								);
							}
						} elseif ($type === Types\DeviceMessageType::GET) {
							// This topic could be ignored
							// It is used only for requesting state from device
							$this->logger->debug(
								'Received GET request to receive device status',
								[
									'source' => Sources\Connector::ZIGBEE2MQTT->value,
									'type' => 'device-messages-subscriber',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'message' => [
										'topic' => $message->getTopic(),
										'payload' => $message->getPayload(),
										'qos' => $message->getQosLevel(),
									],
								],
							);

							return;
						}
					} else {
						$payload = $this->parsePayload($message);

						if ($payload === null) {
							$this->logger->warning(
								'Received message payload is not valid for device state message',
								[
									'source' => Sources\Connector::ZIGBEE2MQTT->value,
									'type' => 'device-messages-subscriber',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'message' => [
										'topic' => $message->getTopic(),
										'payload' => $message->getPayload(),
										'qos' => $message->getQosLevel(),
									],
								],
							);

							return;
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceState::class,
								array_merge($data, ['states' => $this->convertStatePayload($payload)]),
							),
						);
					}
				}
			} catch (Exceptions\ParseMessage | Exceptions\InvalidArgument $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => Sources\Connector::ZIGBEE2MQTT->value,
						'type' => 'device-messages-subscriber',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'message' => [
							'topic' => $message->getTopic(),
							'payload' => $message->getPayload(),
							'qos' => $message->getQosLevel(),
						],
					],
				);
			}
		}
	}

	/**
	 * @param array<mixed> $payload
	 *
	 * @return array<mixed>
	 */
	private function convertStatePayload(array $payload): array
	{
		$converted = [];

		foreach ($payload as $key => $value) {
			$converted[] = is_array($value) ? [
				'type' => Types\ExposeDataType::COMPOSITE->value,
				'identifier' => $key,
				'states' => $this->convertStatePayload($value),
			] : [
				'type' => Types\ExposeDataType::SINGLE->value,
				'identifier' => $key,
				'value' => $value,
			];
		}

		return $converted;
	}

	/**
	 * @return array<mixed>|null
	 */
	private function parsePayload(NetMqtt\Message $message): array|null
	{
		try {
			$payload = Utils\Json::decode($message->getPayload(), forceArrays: true);

			return $payload !== null ? (array) $payload : null;
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received device message payload is not valid JSON message',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'device-messages-subscriber',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'message' => [
						'topic' => $message->getTopic(),
						'payload' => $message->getPayload(),
						'qos' => $message->getQosLevel(),
					],
				],
			);

			return null;
		}
	}

}
