<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Publishers;

use DateTime;
use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Publishers;
use FastyBird\Plugin\RabbitMq\Tests;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log;

final class PublisherTest extends TestCase
{

	/**
	 * @throws Utils\JsonException
	 */
	public function testPublishMessage(): void
	{
		$now = new DateTime();

		$channel = $this->createMock(Channels\Channel::class);
		$channel
			->expects(self::once())
			->method('publish')
			->with(
				Nette\Utils\Json::encode([
					'attribute' => 'someAttribute',
					'value' => 10,
				]),
				[
					'sender_id' => 'rabbitmq_client_identifier',
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'created' => $now->format(DateTimeInterface::ATOM),
				],
				'exchange_name',
				'testing.routing.key',
			)
			->willReturn(true);

		$systemClock = $this->createMock(Clock\SystemClock::class);
		$systemClock
			->expects(self::once())
			->method('getNow')
			->willReturn($now);

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger
			->expects(self::once())
			->method('debug')
			->with(self::callback(static function ($message): bool {
				self::assertSame('Received message was pushed into data exchange', $message);

				return true;
			}));

		$identifierGenerator = $this->createMock(Utilities\IdentifierGenerator::class);
		$identifierGenerator
			->expects(self::once())
			->method('getIdentifier')
			->willReturn('rabbitmq_client_identifier');

		$publisher = new Publishers\Publisher(
			'exchange_name',
			$channel,
			$identifierGenerator,
			$systemClock,
			$logger,
		);

		$publisher->publish(
			MetadataTypes\Sources\Module::DEVICES,
			'testing.routing.key',
			new Tests\Fixtures\Dummy\DummyDocument(
				'someAttribute',
				10,
			),
		);
	}

}
