<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DI;

use FastyBird\Module\Devices\DI;
use Nette\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What `fbDevicesModule.history` takes, and refuses (#624)
 *
 * The schema is processed on its own, with no container: a value it refuses stops the container compiling,
 * and the message names the key.
 */
final class HistoryConfigTest extends TestCase
{

	/**
	 * @return array<string, array{array<string, mixed>, string}>
	 */
	public static function refused(): array
	{
		return [
			'flushInterval 0' => [['history' => ['flushInterval' => 0]], 'flushInterval'],
			'flushInterval negative' => [['history' => ['flushInterval' => -5]], 'flushInterval'],
			'flushInterval a string' => [['history' => ['flushInterval' => 'often']], 'flushInterval'],
			'minInterval negative' => [['history' => ['minInterval' => -1]], 'minInterval'],
			'retention samples 0' => [['history' => ['retention' => ['samples' => 0]]], 'samples'],
			'retention fiveMinutes 0' => [['history' => ['retention' => ['fiveMinutes' => 0]]], 'fiveMinutes'],
			'retention hours 0' => [['history' => ['retention' => ['hours' => 0]]], 'hours'],
			'retention hours negative' => [['history' => ['retention' => ['hours' => -365]]], 'hours'],
			'enabled not a bool' => [['history' => ['enabled' => 'sometimes']], 'enabled'],
			'an unknown key' => [['history' => ['flushEvery' => 10]], 'flushEvery'],
			'an unknown retention key' => [['history' => ['retention' => ['days' => 10]]], 'days'],
		];
	}

	/**
	 * @param array<string, mixed> $config
	 *
	 * @throws Schema\ValidationException
	 */
	#[DataProvider('refused')]
	public function testValueOutsideTheBoundsIsRefused(array $config, string $key): void
	{
		$this->expectException(Schema\ValidationException::class);
		$this->expectExceptionMessageMatches('/' . $key . '/');

		(new Schema\Processor())->process((new DI\DevicesExtension())->getConfigSchema(), $config);
	}

	/**
	 * @throws Schema\ValidationException
	 */
	public function testDefaults(): void
	{
		$config = (new Schema\Processor())->process((new DI\DevicesExtension())->getConfigSchema(), []);

		self::assertInstanceOf(DI\Config::class, $config);
		self::assertTrue($config->history->enabled);
		self::assertSame(10, $config->history->flushInterval);
		self::assertSame(10, $config->history->minInterval);
		self::assertSame(7, $config->history->retention->samples);
		self::assertSame(30, $config->history->retention->fiveMinutes);
		self::assertSame(365, $config->history->retention->hours);
	}

	/**
	 * @throws Schema\ValidationException
	 */
	public function testTheLowestValuesAreAccepted(): void
	{
		$config = (new Schema\Processor())->process(
			(new DI\DevicesExtension())->getConfigSchema(),
			[
				'history' => [
					'enabled' => false,
					'flushInterval' => 1,
					'minInterval' => 0,
					'retention' => ['samples' => 1, 'fiveMinutes' => 1, 'hours' => 1],
				],
			],
		);

		self::assertInstanceOf(DI\Config::class, $config);
		self::assertFalse($config->history->enabled);
		self::assertSame(1, $config->history->flushInterval);
		self::assertSame(0, $config->history->minInterval);
		self::assertSame(1, $config->history->retention->samples);
		self::assertSame(1, $config->history->retention->fiveMinutes);
		self::assertSame(1, $config->history->retention->hours);
	}

}
