<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Consumers;

use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Core\WebSockets\Routing;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\Utils;
use Override;
use Throwable;
use function array_merge;
use function in_array;

/**
 * Exchange to sockets bridge consumer
 */
final readonly class SocketsBridge implements Consumers\Consumer
{

	private const array CONSUMER_ROUTING_KEYS = [
		Devices\Types\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED->value,
		Devices\Types\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED->value,
		Devices\Types\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED->value,
		Devices\Types\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED->value,

		Devices\Types\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED->value,
		Devices\Types\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_CREATED->value,
		Devices\Types\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED->value,
		Devices\Types\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_DELETED->value,

		Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED->value,
		Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED->value,
		Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED->value,
		Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED->value,
	];

	public function __construct(
		private UiModels\Configuration\Widgets\DataSources\Repository $configurationDataSourcesRepository,
		private DevicesModuleUiModule\Logger $logger,
		private Routing\LinkGenerator $linkGenerator,
		private Topics\Storage $topicsStorage,
	)
	{
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	#[Override]
	public function consume(
		Sources\Source $source,
		string $routingKey,
		CoreDocuments\Document|null $document,
	): void
	{
		// A document with the Devices source is a command (a WAMP SET or an API write) addressed to
		// its target, not a report: the state it writes is not pushed to the widgets, which see the
		// result when the target reports it, under its own source. The connection state and the
		// validity resets are reported under the connector's source for this reason. Same rule as
		// the Devices bridge.
		if ($source === Sources\Module::DEVICES) {
			return;
		}

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
				DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty::class,
			);

		} elseif ($document instanceof DevicesDocuments\States\Devices\Properties\Property) {
			$findDataSources = new Queries\Configuration\FindWidgetDevicePropertyDataSources();
			$findDataSources->byPropertyId($document->getId());

			$dataSources = $this->configurationDataSourcesRepository->findAllBy(
				$findDataSources,
				DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty::class,
			);

		} elseif ($document instanceof DevicesDocuments\States\Channels\Properties\Property) {
			$findDataSources = new Queries\Configuration\FindWidgetChannelPropertyDataSources();
			$findDataSources->byPropertyId($document->getId());

			$dataSources = $this->configurationDataSourcesRepository->findAllBy(
				$findDataSources,
				DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::class,
			);
		}

		foreach ($dataSources as $dataSource) {
			$message = [
				'routing_key' => Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_REPORTED->value,
				'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
				'data' => array_merge(
					$dataSource->toArray(),
					[
						'value' => Utilities\Value::flattenValue($document->getRead()->getActualValue()),
					],
				),
			];

			$result = $this->sendMessage($message);

			if ($result) {
				$this->logger->debug(
					'Successfully published message',
					[
						'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
						'type' => 'state-entities-consumer',
						'message' => $message,
					],
				);

			} else {
				$this->logger->error(
					'Message could not be published to exchange',
					[
						'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
						'type' => 'state-entities-consumer',
						'message' => $message,
					],
				);
			}
		}

		$this->logger->debug(
			'Received message from exchange was pushed to WS clients',
			[
				'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
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
						'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
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
					'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
					'type' => 'state-entities-consumer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Data could not be broadcasts to clients',
				[
					'source' => Sources\Bridge::DEVICES_MODULE_UI_MODULE->value,
					'type' => 'state-entities-consumer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}

		return false;
	}

}
