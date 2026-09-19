<?php declare(strict_types = 1);

/**
 * SocketsBridge.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           06.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Consumers;

use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Documents;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Core\Tools\Utilities as ToolsUtilities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\WebSockets;
use FastyBird\Library\WebSockets\Wamp;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\Utils;
use Throwable;
use function array_merge;
use function in_array;

/**
 * Exchange to sockets bridge consumer
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SocketsBridge implements ExchangeConsumers\Consumer
{

	private const CONSUMER_ROUTING_KEYS = [
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY,

		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY,

		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY,
	];

	public function __construct(
		private readonly UiModels\Configuration\Widgets\DataSources\Repository $configurationDataSourcesRepository,
		private readonly DevicesModuleUiModule\Logger $logger,
		private readonly WebSockets\Router\LinkGenerator $linkGenerator,
		private readonly Wamp\Topics\IStorage $topicsStorage,
	)
	{
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void
	{
		if (
			!in_array($routingKey, self::CONSUMER_ROUTING_KEYS, true)
			|| (
				!$document instanceof DevicesDocuments\States\Connectors\Properties\Property
				&& !$document instanceof DevicesDocuments\States\Devices\Properties\Property
				&& !$document instanceof DevicesDocuments\States\Channels\Properties\Property
			)
		) {
			return;
		}

		if ($document instanceof DevicesDocuments\States\Connectors\Properties\Property) {
			$findDataSources = new Queries\Configuration\FindWidgetConnectorPropertyDataSources();
			$findDataSources->byPropertyId($document->getId());

			$dataSources = $this->configurationDataSourcesRepository->findAllBy(
				$findDataSources,
				Documents\Widgets\DataSources\ConnectorProperty::class,
			);

		} elseif ($document instanceof DevicesDocuments\States\Devices\Properties\Property) {
			$findDataSources = new Queries\Configuration\FindWidgetDevicePropertyDataSources();
			$findDataSources->byPropertyId($document->getId());

			$dataSources = $this->configurationDataSourcesRepository->findAllBy(
				$findDataSources,
				Documents\Widgets\DataSources\DeviceProperty::class,
			);

		} elseif ($document instanceof DevicesDocuments\States\Channels\Properties\Property) {
			$findDataSources = new Queries\Configuration\FindWidgetChannelPropertyDataSources();
			$findDataSources->byPropertyId($document->getId());

			$dataSources = $this->configurationDataSourcesRepository->findAllBy(
				$findDataSources,
				Documents\Widgets\DataSources\ChannelProperty::class,
			);
		}

		foreach ($dataSources as $dataSource) {
			$message = [
				'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
				'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
				'data' => array_merge(
					$dataSource->toArray(),
					[
						'value' => ToolsUtilities\Value::flattenValue($document->getRead()->getActualValue()),
					],
				),
			];

			$result = $this->sendMessage($message);

			if ($result) {
				$this->logger->debug(
					'Successfully published message',
					[
						'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
						'type' => 'state-entities-consumer',
						'message' => $message,
					],
				);

			} else {
				$this->logger->error(
					'Message could not be published to exchange',
					[
						'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
						'type' => 'state-entities-consumer',
						'message' => $message,
					],
				);
			}
		}

		$this->logger->debug(
			'Received message from exchange was pushed to WS clients',
			[
				'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
				'type' => 'state-entities-consumer',
				'message' => [
					'routing_key' => $routingKey,
					'source' => $source->value,
					'entity' => $document->toArray(),
				],
			],
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function sendMessage(array $data): bool
	{
		try {
			$link = $this->linkGenerator->link('UiModule:Exchange:');

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug(
					'Broadcasting message to topic',
					[
						'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
						'type' => 'state-entities-consumer',
						'link' => $link,
					],
				);

				$topic->broadcast(Utils\Json::encode($data));
			}

			return true;
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
					'type' => 'state-entities-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Data could not be broadcasts to clients',
				[
					'source' => MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
					'type' => 'state-entities-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		}

		return false;
	}

}
