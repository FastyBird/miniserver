<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Consumers;

use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Routing;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Ui;
use Nette\Utils;
use Override;
use Throwable;
use function in_array;

/**
 * Exchange to sockets bridge consumer
 */
final readonly class SocketsBridge implements Consumers\Consumer
{

	private const array MESSAGE_BUS_ROUTING_KEYS
		= [
			Ui\Types\RoutingKey::WIDGET_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::WIDGET_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::WIDGET_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::WIDGET_DOCUMENT_DELETED->value,
			Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_DELETED->value,
			Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_DELETED->value,
			Ui\Types\RoutingKey::GROUP_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::GROUP_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::GROUP_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::GROUP_DOCUMENT_DELETED->value,
			Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_DELETED->value,
			Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_REPORTED->value,
			Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_CREATED->value,
			Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_UPDATED->value,
			Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_DELETED->value,
		];

	public function __construct(
		private Ui\Logger $logger,
		private Routing\LinkGenerator $linkGenerator,
		private Topics\Storage $topicsStorage,
	)
	{
	}

	#[Override]
	public function consume(
		Sources\Source $source,
		string $routingKey,
		Documents\Document|null $document,
	): void
	{
		if (!in_array($routingKey, Ui\Constants::MESSAGE_BUS_ROUTING_KEYS, true)) {
			return;
		}

		$result = $this->sendMessage(
			[
				'routing_key' => $routingKey,
				'source' => $source->value,
				'data' => $document?->toArray(),
			],
		);

		if ($result) {
			$this->logger->debug(
				'Successfully published message',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'message' => [
						'routing_key' => $routingKey,
						'source' => $source->value,
						'data' => $document?->toArray(),
					],
				],
			);

		} else {
			$this->logger->error(
				'Message could not be published to exchange',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'message' => [
						'routing_key' => $routingKey,
						'source' => $source->value,
						'data' => $document?->toArray(),
					],
				],
			);
		}

		$this->logger->debug(
			'Received message from exchange was pushed to WS clients',
			[
				'source' => Sources\Module::DEVICES->value,
				'type' => 'sockets-consumer',
				'message' => [
					'routing_key' => $routingKey,
					'source' => $source->value,
					'entity' => $document?->toArray(),
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
						'source' => Sources\Module::DEVICES->value,
						'type' => 'sockets-consumer',
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
					'source' => Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Data could not be broadcasts to clients',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}

		return false;
	}

}
