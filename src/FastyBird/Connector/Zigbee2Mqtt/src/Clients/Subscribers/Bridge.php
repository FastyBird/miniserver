<?php declare(strict_types = 1);

/**
 * Bridge.php
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
use function strval;

/**
 * Zigbee2MQTT MQTT bridge messages subscriber
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Bridge
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

			try {
				if (API\MqttValidator::validateBridge($message->getTopic())) {
					$data = API\MqttParser::parse(
						$this->connector->getId(),
						$message->getTopic(),
						$message->getPayload(),
					);

					if (array_key_exists('type', $data)) {
						if (Types\BridgeMessageType::tryFrom(strval($data['type'])) === null) {
							throw new Exceptions\ParseMessage('Received unsupported bridge message type');
						}

						$type = Types\BridgeMessageType::from(strval($data['type']));

						if ($type === Types\BridgeMessageType::INFO) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge info message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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
									Queue\Messages\StoreBridgeInfo::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type === Types\BridgeMessageType::STATE) {
							if (
								$message->getPayload() !== ''
								&& Types\ConnectionState::tryFrom($message->getPayload()) !== null
							) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreBridgeConnectionState::class,
										array_merge($data, ['state' => $message->getPayload()]),
									),
								);

							} else {
								$payload = $this->parsePayload($message);

								if ($payload === null) {
									$this->logger->warning(
										'Received message payload is not valid for bridge state message',
										[
											'source' => Sources\Connector::ZIGBEE2MQTT->value,
											'type' => 'bridge-messages-subscriber',
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
										Queue\Messages\StoreBridgeConnectionState::class,
										array_merge($data, $payload),
									),
								);
							}
						} elseif ($type === Types\BridgeMessageType::LOGGING) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge log',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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
									Queue\Messages\StoreBridgeLog::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type === Types\BridgeMessageType::DEFINITIONS) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge extensions message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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

							// This message could be ignored

						} elseif ($type === Types\BridgeMessageType::DEVICES) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge devices message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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
									Queue\Messages\StoreBridgeDevices::class,
									array_merge($data, ['devices' => $payload]),
								),
							);

						} elseif ($type === Types\BridgeMessageType::GROUPS) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge groups message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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
									Queue\Messages\StoreBridgeGroups::class,
									array_merge($data, ['groups' => $payload]),
								),
							);

						} elseif ($type === Types\BridgeMessageType::EVENT) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge event message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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
									Queue\Messages\StoreBridgeEvent::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type === Types\BridgeMessageType::EXTENSIONS) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge extensions message',
									[
										'source' => Sources\Connector::ZIGBEE2MQTT->value,
										'type' => 'bridge-messages-subscriber',
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

							// This message could be ignored
						}
					} elseif (array_key_exists('request', $data)) {
						// TODO: Handle request messages
					} elseif (array_key_exists('response', $data)) {
						// TODO: Handle response messages
					}
				}
			} catch (Exceptions\ParseMessage | Exceptions\InvalidArgument $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => Sources\Connector::ZIGBEE2MQTT->value,
						'type' => 'bridge-messages-subscriber',
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
	 * @return array<mixed>|null
	 */
	private function parsePayload(NetMqtt\Message $message): array|null
	{
		try {
			$payload = Utils\Json::decode($message->getPayload(), forceArrays: true);

			return $payload !== null ? (array) $payload : null;
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received bridge message payload is not valid JSON message',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'bridge-messages-subscriber',
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
