<?php declare(strict_types = 1);

/**
 * Message.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Handlers
 * @since          1.0.0
 *
 * @date           26.03.23
 */

namespace FastyBird\Plugin\RabbitMq\Handlers;

use Bunny;
use FastyBird\Core\Documents;
use FastyBird\Core\Logging;
use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Exceptions;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use Throwable;
use TypeError;
use ValueError;
use function assert;
use function is_array;
use function strval;

/**
 * RabbitMQ message handler
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Handlers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Message
{

	public const MESSAGE_ACK = 1;

	public const MESSAGE_NACK = 2;

	public const MESSAGE_REJECT = 3;

	public const MESSAGE_REJECT_AND_TERMINATE = 4;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly Documents\RoutingDocumentFactory $documentFactory,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function handle(Bunny\Message $message): int
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageHandled($message));

		try {
			$data = Utils\Json::decode($message->content, forceArrays: true);

			if (is_array($data) && $message->hasHeader('source')) {
				return $this->consume(
					strval($message->getHeader('source')),
					$message->routingKey,
					Utils\Json::encode($data),
					$message->hasHeader('sender_id') ? strval($message->getHeader('sender_id')) : null,
				);
			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => Sources\Plugin::RABBITMQ->value,
					'type' => 'messages-handler',
				]);

				return self::MESSAGE_REJECT;
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => Logging\Logger::buildException($ex),
			]);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageHandled($message));

		return self::MESSAGE_REJECT;
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
	): int
	{
		if ($senderId === $this->identifier->getIdentifier()) {
			return self::MESSAGE_NACK;
		}

		$source = $this->validateSource($source);

		if ($source === null) {
			return self::MESSAGE_REJECT;
		}

		try {
			$data = Utils\Json::decode($data, forceArrays: true);
			assert(is_array($data));
			$data = Utils\ArrayHash::from($data);

			$entity = $this->documentFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error('Message could not be transformed into entity', [
				'source' => Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => Logging\Logger::buildException($ex),
				'data' => $data,
			]);

			return self::MESSAGE_REJECT;
		}

		try {
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

		} catch (Exceptions\UnprocessableMessage $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source' => Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => Logging\Logger::buildException($ex),
			]);

			return self::MESSAGE_REJECT;
		}

		return self::MESSAGE_ACK;
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
