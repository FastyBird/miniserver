<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Publishers;

use DateTime;
use DateTimeInterface;
use FastyBird\Library\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Tests;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;

final class PublisherTest extends TestCase
{

	/**
	 * @throws Utils\JsonException
	 */
	public function testPublish(): void
	{
		$now = new DateTime();

		$client = $this->createMock(Clients\Client::class);
		$client
			->expects(self::once())
			->method('publish')
			->with('exchange_channel', Nette\Utils\Json::encode([
				'sender_id' => 'redis_client_identifier',
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'routing_key' => 'testing.routing.key',
				'created' => $now->format(DateTimeInterface::ATOM),
				'data' => [
					'attribute' => 'someAttribute',
					'value' => 10,
				],
			]))
			->willReturn(true);

		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->expects(self::once())
			->method('getNow')
			->willReturn($now);

		$identifierGenerator = $this->createMock(Utilities\IdentifierGenerator::class);
		$identifierGenerator
			->expects(self::once())
			->method('getIdentifier')
			->willReturn('redis_client_identifier');

		$publisher = new Publishers\Publisher(
			$identifierGenerator,
			'exchange_channel',
			$client,
			$systemClock,
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
