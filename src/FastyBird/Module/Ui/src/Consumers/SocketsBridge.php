<?php declare(strict_types = 1);

/**
 * SocketsBridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           17.4.23
 */

namespace FastyBird\Module\Ui\Consumers;

use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\WebSockets;
use FastyBird\Library\WebSockets\Wamp;
use FastyBird\Module\Ui;
use Nette\Utils;
use Throwable;
use function in_array;

/**
 * Exchange to sockets bridge consumer
 *
 * @package        FastyBird:UiModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SocketsBridge implements ExchangeConsumers\Consumer
{

	public function __construct(
		private Ui\Logger $logger,
		private WebSockets\Router\LinkGenerator $linkGenerator,
		private Wamp\Topics\IStorage $topicsStorage,
	)
	{
	}

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
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
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
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
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
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
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
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
			$link = $this->linkGenerator->link('DevicesModule:Exchange:');

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug(
					'Broadcasting message to topic',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
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
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Data could not be broadcasts to clients',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		}

		return false;
	}

}
