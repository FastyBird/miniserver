<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           17.09.21
 */

namespace FastyBird\Plugin\RedisDb\Publishers;

use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Logging;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Psr\Log;

/**
 * Redis DB exchange publisher
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePublisher\Publisher
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly string $channel,
		private readonly Clients\Client $client,
		private readonly Clock\Clock $clock,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): bool
	{
		try {
			$result = $this->client->publish(
				$this->channel,
				Nette\Utils\Json::encode([
					'sender_id' => $this->identifier->getIdentifier(),
					'source' => $source->value,
					'routing_key' => $routingKey,
					'created' => $this->clock->getNow()->format(DateTimeInterface::ATOM),
					'data' => $entity?->toArray(),
				]),
			);

			if ($result === true) {
				$this->logger->debug(
					'Received message was pushed into data exchange',
					[
						'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
						'type' => 'messages-publisher',
						'message' => [
							'routing_key' => $routingKey,
							'source' => $source->value,
							'data' => $entity?->toArray(),
						],
					],
				);

				return true;
			} else {
				$this->logger->error(
					'Received message could not be pushed into data exchange',
					[
						'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
						'type' => 'messages-publisher',
						'message' => [
							'routing_key' => $routingKey,
							'source' => $source->value,
							'data' => $entity?->toArray(),
						],
					],
				);

				return false;
			}
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
					'type' => 'messages-publisher',
					'exception' => Logging\Logger::buildException($ex),
					'message' => [
						'routing_key' => $routingKey,
						'source' => $source->value,
						'data' => $entity?->toArray(),
					],
				],
			);

			return false;
		}
	}

}
