<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Tests\Cases\Unit\Models;

use Exception;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Models;
use FastyBird\Bridge\RedisDbPluginDevicesModule\States as RedisDbPluginDevicesModuleStates;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Tests;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;

/**
 * A value of a state which holds a NUL byte is a text, as it was when orisai mapped the state. The union of the value
 * has a date in it, and DateTimeImmutable::createFromFormat() throws a ValueError for such a text, which the
 * repository answers by deleting the stored key and throwing: a row that was read before is lost on its first read
 */
final class StatesWithNulByteTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws Exception
	 */
	#[DataProvider('texts')]
	public function testTheRepositoryReturnsTheRowAndKeepsTheKey(string $field, string $text): void
	{
		$id = Uuid\Uuid::uuid4();

		$redisDbClient = $this->createMock(Clients\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode(['id' => $id->toString(), $field => $text]));

		$redisDbClient
			->expects(self::never())
			->method('del');

		$this->mockContainerService(Clients\Client::class, $redisDbClient);

		$state = $this->container->getByType(Models\States\ChannelPropertiesRepository::class)->find($id);

		self::assertInstanceOf(RedisDbPluginDevicesModuleStates\ChannelProperty::class, $state);
		self::assertSame($text, $field === 'actual_value' ? $state->getActualValue() : $state->getExpectedValue());
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	#[DataProvider('texts')]
	public function testTheFactoryMapsTheStoredJson(string $field, string $text): void
	{
		$state = $this->container->getByType(RedisDbStates\StateFactory::class)->create(
			RedisDbPluginDevicesModuleStates\ChannelProperty::class,
			Utils\Json::encode(['id' => Uuid\Uuid::uuid4()->toString(), $field => $text]),
		);

		self::assertSame($text, $field === 'actual_value' ? $state->getActualValue() : $state->getExpectedValue());
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function texts(): array
	{
		$cases = [];

		foreach (
			[
				'a NUL byte at the end' => "a\0",
				'two NUL bytes' => "abc\0\0",
				'a NUL byte only' => "\0",
				'a date followed by a NUL byte' => "2020-04-01T12:00:00+00:00\0",
				'a NUL byte in a date' => "2020-04-01T12:00:00\0+00:00",
			] as $label => $text
		) {
			foreach (['actual_value', 'expected_value'] as $field) {
				$cases[$field . ': ' . $label] = [$field, $text];
			}
		}

		return $cases;
	}

}
