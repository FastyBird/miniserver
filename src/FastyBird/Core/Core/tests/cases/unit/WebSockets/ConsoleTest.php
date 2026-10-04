<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\WebSockets\Helpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log;

/**
 * Characterization of WebSockets\Helpers\Console, the logger the WebSockets capability registers
 * when no other PSR-3 logger exists (census X9, T12-4).
 *
 * Until #635 this pinned a KNOWN DEFECT: Console::$formatter was a typed property that nothing
 * ever set -- setFormatter() had no caller -- and every log method read it first, so the very
 * first log call threw instead of reaching the `echo` fallback. #635 removed the formatter
 * branch (census X9), and this now pins the fallback's output. No compiled container registers
 * Console today, so nothing else reaches it.
 */
final class ConsoleTest extends TestCase
{

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function levels(): array
	{
		return [
			'emergency' => ['emergency', 'CAUTION! '],
			'alert' => ['alert', 'ERROR! '],
			'critical' => ['critical', 'ERROR! '],
			'error' => ['error', 'ERROR! '],
			'warning' => ['warning', 'WARNING! '],
			'notice' => ['notice', 'NOTICE! '],
			'info' => ['info', 'INFO: '],
			'debug' => ['debug', 'DEBUG: '],
		];
	}

	#[DataProvider('levels')]
	public function testEveryLevelEchoesItsPrefixAndTheMessage(string $level, string $prefix): void
	{
		$this->expectOutputString($prefix . "e5 probe\r\n");

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
	public function testLogEchoesItsPrefixAndTheMessage(): void
	{
		$this->expectOutputString("LOG: e5 probe\r\n");

		(new Helpers\Console())->log('info', 'e5 probe');
	}

}
