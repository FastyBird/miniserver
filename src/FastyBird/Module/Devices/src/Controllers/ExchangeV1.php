<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           17.04.23
 */

namespace FastyBird\Module\Devices\Controllers;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Controllers\WebSockets as WebSocketsControllers;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Entities\WsServer as WsServerEntities;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette\Utils;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function is_array;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExchangeV1 extends WebSocketsControllers\Controller\Controller
{

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
	)
	{
		parent::__construct();
	}

	/**
	 * @param WsServerEntities\Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
			],
		);

		try {
			$findDevicesProperties = new Queries\Configuration\FindDeviceProperties();

			$devicesProperties = $this->devicePropertiesConfigurationRepository->findAllBy(
				$findDevicesProperties,
			);

			foreach ($devicesProperties as $deviceProperty) {
				if (
					$deviceProperty instanceof Documents\Devices\Properties\Dynamic
					|| $deviceProperty instanceof Documents\Devices\Properties\Mapped
				) {
					$state = $this->devicePropertiesStatesManager->readState($deviceProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsControllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}

			$findChannelsProperties = new Queries\Configuration\FindChannelProperties();

			$channelsProperties = $this->channelPropertiesConfigurationRepository->findAllBy(
				$findChannelsProperties,
			);

			foreach ($channelsProperties as $channelProperty) {
				if (
					$channelProperty instanceof Documents\Channels\Properties\Dynamic
					|| $channelProperty instanceof Documents\Channels\Properties\Mapped
				) {
					$state = $this->channelPropertiesStatesManager->readState($channelProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsControllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}

			$findConnectorsProperties = new Queries\Configuration\FindConnectorProperties();

			$connectorsProperties = $this->connectorPropertiesConfigurationRepository->findAllBy(
				$findConnectorsProperties,
			);

			foreach ($connectorsProperties as $connectorProperty) {
				if ($connectorProperty instanceof Documents\Connectors\Properties\Dynamic) {
					$state = $this->connectorPropertiesStatesManager->readState($connectorProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsControllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be sent to subscriber',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'exchange-controller',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param WsServerEntities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function actionCall(
		array $args,
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgument('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
				/** @var array<string, mixed>|null $data */
				$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;

				if ($data !== null) {
					if ($args['routing_key'] === Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY) {
						$document = $this->documentFactory->create(
							Documents\States\Connectors\Properties\Actions\Action::class,
							$data,
						);

						$this->handleConnectorAction($client, $topic, $document);
					} elseif ($args['routing_key'] === Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY) {
						$document = $this->documentFactory->create(
							Documents\States\Devices\Properties\Actions\Action::class,
							$data,
						);

						$this->handleDeviceAction($client, $topic, $document);
					} elseif ($args['routing_key'] === Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY) {
						$document = $this->documentFactory->create(
							Documents\States\Channels\Properties\Actions\Action::class,
							$data,
						);

						$this->handleChannelAction($client, $topic, $document);
					}
				}

				break;
			default:
				throw new Exceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->getPayload()->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleConnectorAction(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
		Documents\States\Connectors\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if (!$property instanceof Documents\Connectors\Properties\Dynamic) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Documents\Connectors\Properties\Dynamic
				? $this->connectorPropertiesStatesManager->readState($property)
				: null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsControllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDeviceAction(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
		Documents\States\Devices\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof Documents\Devices\Properties\Dynamic
				&& !$property instanceof Documents\Devices\Properties\Mapped
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Documents\Devices\Properties\Dynamic
			|| $property instanceof Documents\Devices\Properties\Mapped
				? $this->devicePropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsControllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleChannelAction(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
		Documents\States\Channels\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof Documents\Channels\Properties\Dynamic
				&& !$property instanceof Documents\Channels\Properties\Mapped
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Documents\Channels\Properties\Dynamic
			|| $property instanceof Documents\Channels\Properties\Mapped
				? $this->channelPropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsControllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

}
