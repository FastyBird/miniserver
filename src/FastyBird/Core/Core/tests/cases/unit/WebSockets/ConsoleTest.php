<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use Error;
use FastyBird\Core\WebSockets\Helpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log;

/**
 * Characterization of WebSockets\Helpers\Console, the logger the WebSockets capability registers
 * when no other PSR-3 logger exists (census X9, T12-4).
 *
 * A KNOWN DEFECT, pinned as it is: Console::$formatter is a typed property that nothing ever
 * sets -- setFormatter() has no caller -- and every log method reads it first, so the very first
 * log call throws instead of reaching the `echo` fallback. No compiled container registers
 * Console today, so nothing hits it. #635 removes the formatter branch and flips this test to
 * the fallback's output.
 */
final class ConsoleTest extends TestCase
{

	/**
	 * @return array<string, array{string}>
	 */
	public static function levels(): array
	{
		return [
			'emergency' => ['emergency'],
			'alert' => ['alert'],
			'critical' => ['critical'],
			'error' => ['error'],
			'warning' => ['warning'],
			'notice' => ['notice'],
			'info' => ['info'],
			'debug' => ['debug'],
		];
	}

	#[DataProvider('levels')]
	public function testTheFirstLogCallThrowsBecauseTheFormatterWasNeverSet(string $level): void
	{
		$this->expectException(Error::class);
		$this->expectExceptionMessage(
			'Typed property FastyBird\Core\WebSockets\Helpers\Console::$formatter must not be accessed before initialization',
		);

		$console = new Helpers\Console();

		match ($level) {
			'emergency' => $console->emergency('e5 probe'),
			'alert' => $console->alert('e5 probe'),
			'critical' => $console->critical('e5 probe'),
			'error' => $console->error('e5 probe'),
			'warning' => $console->warning('e5 probe'),
			'notice' => $console->notice('e5 probe'),
			'info' => $console->info('e5 probe'),
			default => $console->debug('e5 probe'),
		};
	}

	/**
	 * @throws Log\InvalidArgumentException
	 */
	public function testLogThrowsTheSameWay(): void
	{
		$this->expectException(Error::class);
		$this->expectExceptionMessage('must not be accessed before initialization');

		(new Helpers\Console())->log('info', 'e5 probe');
	}

}
