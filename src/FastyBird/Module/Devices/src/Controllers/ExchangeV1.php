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

use FastyBird\Core\Constants;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Entities\Topics;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
final class ExchangeV1 extends Controllers\Controller
{

	/**
	 * The roles the HTTP API requires to change anything in this module: every create, update
	 * and delete of the property controllers (ConnectorPropertiesV1, DevicePropertiesV1,
	 * ChannelPropertiesV1) is limited to them.
	 */
	private const array WRITE_ROLES = [
		Constants::ROLE_MANAGER,
		Constants::ROLE_ADMINISTRATOR,
	];

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
	)
	{
		parent::__construct();
	}

	/**
	 * @param Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => Sources\Module::DEVICES->value,
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
					$deviceProperty instanceof DevicesDocuments\Devices\Properties\Dynamic
					|| $deviceProperty instanceof DevicesDocuments\Devices\Properties\Mapped
				) {
					$state = $this->devicePropertiesStatesManager->readState($deviceProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							Controllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => Sources\Module::DEVICES->value,
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
					$channelProperty instanceof DevicesDocuments\Channels\Properties\Dynamic
					|| $channelProperty instanceof DevicesDocuments\Channels\Properties\Mapped
				) {
					$state = $this->channelPropertiesStatesManager->readState($channelProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							Controllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => Sources\Module::DEVICES->value,
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
				if ($connectorProperty instanceof DevicesDocuments\Connectors\Properties\Dynamic) {
					$state = $this->connectorPropertiesStatesManager->readState($connectorProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							Controllers\WampApplication::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
								'source' => Sources\Module::DEVICES->value,
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
					'source' => Sources\Module::DEVICES->value,
					'type' => 'exchange-controller',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param Topics\ITopic<mixed> $topic
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 */
	public function actionCall(
		array $args,
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => Sources\Module::DEVICES->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		// Every call needs an authenticated client, as every HTTP controller of this module does
		$this->authorize($client);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new DevicesExceptions\InvalidArgument('Provided message has invalid format');
		}

		/** @var array<string, mixed>|null $data */
		$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;

		switch ($args['routing_key']) {
			case Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY:
			case Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY:
				// No HTTP endpoint runs a control; it changes state, so it takes the rule for a change
				$this->authorize($client, ...self::WRITE_ROLES);

				break;
			case Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY:
				$document = $data !== null
					? $this->documentFactory->create(
						DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
						$data,
					)
					: null;

				$this->authorizePropertyAction($client, $document?->getAction());

				if ($document !== null) {
					$this->handleConnectorAction($client, $topic, $document);
				}

				break;
			case Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
				$document = $data !== null
					? $this->documentFactory->create(
						DevicesDocuments\States\Devices\Properties\Actions\Action::class,
						$data,
					)
					: null;

				$this->authorizePropertyAction($client, $document?->getAction());

				if ($document !== null) {
					$this->handleDeviceAction($client, $topic, $document);
				}

				break;
			case Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
				$document = $data !== null
					? $this->documentFactory->create(
						DevicesDocuments\States\Channels\Properties\Actions\Action::class,
						$data,
					)
					: null;

				$this->authorizePropertyAction($client, $document?->getAction());

				if ($document !== null) {
					$this->handleChannelAction($client, $topic, $document);
				}

				break;
			default:
				throw new DevicesExceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->getPayload()->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * A GET reads a property state, which the property state controllers
	 * (ConnectorPropertyStateV1, DevicePropertyStateV1, ChannelPropertyStateV1) serve to any
	 * authenticated user -- and the caller already is one. A SET, or an action that cannot be
	 * read, changes state and takes the rule for a change.
	 *
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 */
	private function authorizePropertyAction(
		Entities\ConnectedClient $client,
		Types\PropertyAction|null $action,
	): void
	{
		if ($action !== Types\PropertyAction::GET) {
			$this->authorize($client, ...self::WRITE_ROLES);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleConnectorAction(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
		DevicesDocuments\States\Connectors\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if (!$property instanceof DevicesDocuments\Connectors\Properties\Dynamic) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof DevicesDocuments\Connectors\Properties\Dynamic
				? $this->connectorPropertiesStatesManager->readState($property)
				: null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				Controllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDeviceAction(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
		DevicesDocuments\States\Devices\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof DevicesDocuments\Devices\Properties\Dynamic
				&& !$property instanceof DevicesDocuments\Devices\Properties\Mapped
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof DevicesDocuments\Devices\Properties\Dynamic
			|| $property instanceof DevicesDocuments\Devices\Properties\Mapped
				? $this->devicePropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				Controllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleChannelAction(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
		DevicesDocuments\States\Channels\Properties\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\PropertyAction::SET) {
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof DevicesDocuments\Channels\Properties\Dynamic
				&& !$property instanceof DevicesDocuments\Channels\Properties\Mapped
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getExpectedValue() !== Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						Sources\Module::DEVICES,
					);
				}
			}
		} elseif ($entity->getAction() === Types\PropertyAction::GET) {
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof DevicesDocuments\Channels\Properties\Dynamic
			|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
				? $this->channelPropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				Controllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => Sources\Module::DEVICES->value,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

}
