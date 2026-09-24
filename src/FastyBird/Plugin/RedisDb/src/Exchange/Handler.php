<?php declare(strict_types = 1);

/**
 * Handler.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @since          1.0.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Plugin\RedisDb\Exchange;

use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Logging;
use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function is_array;
use function strval;

/**
 * Redis client message handler
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Handler
{

	public function __construct(
		private Utilities\IdentifierGenerator $identifier,
		private ExchangeDocuments\RoutingDocumentFactory $documentFactory,
		private ExchangeConsumers\Container $consumer,
		private EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function handle(string $payload): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageHandled($payload));

		try {
			$data = Utils\Json::decode($payload, forceArrays: true);

			if (
				is_array($data)
				&& array_key_exists('source', $data)
				&& array_key_exists('routing_key', $data)
				&& array_key_exists('data', $data)
			) {
				$this->consume(
					strval($data['source']),
					$data['routing_key'],
					Utils\Json::encode($data['data']),
					array_key_exists('sender_id', $data) ? $data['sender_id'] : null,
				);

			} else {
				// Log error action reason
				$this->logger->warning(
					'Received message is not in valid format',
					[
						'source' => Sources\Plugin::REDISDB->value,
						'type' => 'messages-handler',
					],
				);
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning(
				'Received message is not valid json',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'messages-handler',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageHandled($payload));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function consume(
		string $source,
		string $routingKey,
		string $data,
		string|null $senderId = null,
	): void
	{
		if ($senderId === $this->identifier->getIdentifier()) {
			return;
		}

		$source = $this->validateSource($source);

		if ($source === null) {
			return;
		}

		try {
			$data = Utils\Json::decode($data, forceArrays: true);
			assert(is_array($data));
			$data = Utils\ArrayHash::from($data);

			$entity = $this->documentFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Message could not be transformed into entity',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'messages-handler',
					'exception' => Logging\Logger::buildException($ex),
					'data' => $data,
				],
			);

			return;
		}

		$this->dispatcher?->dispatch(new Events\MessageReceived(
			$source,
			$routingKey,
			$entity,
		));

		$this->consumer->consume($source, $routingKey, $entity);

		$this->dispatcher?->dispatch(new Events\MessageConsumed(
			$source,
			$routingKey,
			$entity,
		));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function validateSource(string $source): Sources\Source|null
	{
		if (Sources\Module::tryFrom($source) !== null) {
			return Sources\Module::from($source);
		}

		if (Sources\Plugin::tryFrom($source) !== null) {
			return Sources\Plugin::from($source);
		}

		if (Sources\Connector::tryFrom($source) !== null) {
			return Sources\Connector::from($source);
		}

		if (Sources\Automator::tryFrom($source) !== null) {
			return Sources\Automator::from($source);
		}

		if (Sources\Addon::tryFrom($source) !== null) {
			return Sources\Addon::from($source);
		}

		if (Sources\Bridge::tryFrom($source) !== null) {
			return Sources\Bridge::from($source);
		}

		return null;
	}

}
