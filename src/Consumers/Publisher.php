<?php declare(strict_types = 1);

/**
 * Sender.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Publishers
 * @since          0.2.0
 *
 * @date           08.10.21
 */

namespace FastyBird\MiniServer\Consumers;

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Metadata;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette;
use Nette\Utils;
use Psr\Log;
use Throwable;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangeConsumer\IConsumer
{

	use Nette\SmartObject;

	private const ROUTING_KEYS = [
		Metadata\Constants::MESSAGE_BUS_ACCOUNTS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_ACCOUNTS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_ACCOUNTS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_EMAILS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_EMAILS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_EMAILS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_IDENTITIES_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_IDENTITIES_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_IDENTITIES_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_DEVICES_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_CHANNELS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_CONNECTORS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CONNECTORS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_CONNECTORS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONTROL_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONTROL_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONTROL_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_DELETED_ENTITY_ROUTING_KEY,

		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_CREATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_UPDATED_ENTITY_ROUTING_KEY,
		Metadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_DELETED_ENTITY_ROUTING_KEY,
	];

	/** @var WebSockets\Router\LinkGenerator */
	private WebSockets\Router\LinkGenerator $linkGenerator;

	/** @var WebSocketsWAMP\Topics\IStorage<WebSocketsWAMP\Entities\Topics\Topic> */
	private WebSocketsWAMP\Topics\IStorage $topicsStorage;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/**
	 * @param WebSockets\Router\LinkGenerator $linkGenerator
	 * @param WebSocketsWAMP\Topics\IStorage<WebSocketsWAMP\Entities\Topics\Topic> $topicsStorage
	 * @param Log\LoggerInterface|null $logger
	 */
	public function __construct(
		WebSockets\Router\LinkGenerator $linkGenerator,
		WebSocketsWAMP\Topics\IStorage $topicsStorage,
		?Log\LoggerInterface $logger
	) {
		$this->linkGenerator = $linkGenerator;
		$this->topicsStorage = $topicsStorage;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function consume(
		$origin,
		MetadataTypes\RoutingKeyType $routingKey,
		?Utils\ArrayHash $data
	): void {
		if (!in_array($routingKey->getValue(), self::ROUTING_KEYS, true)) {
			$this->logger->warning('Provided routing key is not support by this publisher', [
				'source'  => 'ws-server-plugin-publisher',
				'type'    => 'publish',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin'      => $origin->getValue(),
					'data'        => $data !== null ? $this->dataToArray($data) : null,
				],
			]);

			return;
		}

		$result = $this->sendMessage(
			'Exchange:',
			[
				'routing_key' => $routingKey->getValue(),
				'origin'      => $origin->getValue(),
				'data'        => $data !== null ? $this->dataToArray($data) : null,
			]
		);

		if ($result) {
			$this->logger->debug('Successfully published message', [
				'source'  => 'ws-server-plugin-publisher',
				'type'    => 'publish',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin'      => $origin->getValue(),
					'data'        => $data !== null ? $this->dataToArray($data) : null,
				],
			]);

		} else {
			$this->logger->error('Message could not be published to exchange', [
				'source'  => 'ws-server-plugin-publisher',
				'type'    => 'publish',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin'      => $origin->getValue(),
					'data'        => $data !== null ? $this->dataToArray($data) : null,
				],
			]);
		}
	}

	/**
	 * @param Utils\ArrayHash $data
	 *
	 * @return mixed[]
	 */
	private function dataToArray(Utils\ArrayHash $data): array
	{
		$transformed = (array) $data;

		foreach ($transformed as $key => $value) {
			if ($value instanceof Utils\ArrayHash) {
				$transformed[$key] = $this->dataToArray($value);
			}
		}

		return $transformed;
	}

	/**
	 * @param string $destination
	 * @param mixed[] $data
	 *
	 * @return bool
	 */
	private function sendMessage(
		string $destination,
		array $data
	): bool {
		try {
			$link = $this->linkGenerator->link($destination);

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug('Broadcasting message to topic', [
					'source' => 'ws-server-plugin-publisher',
					'type'   => 'broadcast',
					'link'   => $link,
				]);

				$topic->broadcast(Nette\Utils\Json::encode($data));

				return true;
			}
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error('Data could not be converted to message', [
				'source'    => 'ws-server-plugin-publisher',
				'type'      => 'broadcast',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

		} catch (Throwable $ex) {
			var_dump($ex->getMessage());
			$this->logger->error('Data could not be broadcasts to clients', [
				'source'  => 'ws-server-plugin-publisher',
				'type'    => 'broadcast',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);
		}

		return false;
	}

}
