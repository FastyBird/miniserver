<?php declare(strict_types = 1);

namespace FastyBird\Core\Subscribers\Application;

use Monolog;
use Override;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\EventDispatcher;

/**
 * Console subscriber
 */
final readonly class Console implements EventDispatcher\EventSubscriberInterface
{

	/**
	 * @param int|string|Monolog\Level|LogLevel::* $level
	 *
	 * @phpstan-param value-of<Monolog\Level::VALUES>|value-of<Monolog\Level::NAMES>|Monolog\Level|LogLevel::* $level
	 */
	public function __construct(
		private Monolog\Logger $logger,
		private SymfonyMonolog\Handler\ConsoleHandler $handler,
		private int|string|Monolog\Level $level,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			SymfonyConsole\ConsoleEvents::COMMAND => 'command',
		];
	}

	public function command(): void
	{
		$this->handler->setLevel($this->level);
		$this->logger->pushHandler($this->handler);
	}

}
