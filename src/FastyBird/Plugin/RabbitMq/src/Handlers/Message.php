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
use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Documents\Exchange as ExchangeDocuments;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
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
					'source' => MetadataTypes\Sources\Plugin::RABBITMQ->value,
					'type' => 'messages-handler',
				]);

				return self::MESSAGE_REJECT;
			}
		} catch (Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => MetadataTypes\Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => ToolsHelpers\Logger::buildException($ex),
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
				'source' => MetadataTypes\Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => ToolsHelpers\Logger::buildException($ex),
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
				'source' => MetadataTypes\Sources\Plugin::RABBITMQ->value,
				'type' => 'messages-handler',
				'exception' => ToolsHelpers\Logger::buildException($ex),
			]);

			return self::MESSAGE_REJECT;
		}

		return self::MESSAGE_ACK;
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function validateSource(
		string $source,
	): MetadataTypes\Sources\Source|null
	{
		if (MetadataTypes\Sources\Module::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Module::from($source);
		}

		if (MetadataTypes\Sources\Plugin::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Plugin::from($source);
		}

		if (MetadataTypes\Sources\Connector::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Connector::from($source);
		}

		if (MetadataTypes\Sources\Automator::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Automator::from($source);
		}

		if (MetadataTypes\Sources\Addon::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Addon::from($source);
		}

		if (MetadataTypes\Sources\Bridge::tryFrom($source) !== null) {
			return MetadataTypes\Sources\Bridge::from($source);
		}

		return null;
	}

}
