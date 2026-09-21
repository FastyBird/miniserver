<?php declare(strict_types = 1);

/**
 * ConsoleLogger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           06.10.22
 */

namespace FastyBird\Core\Subscribers\Application;

use Monolog;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\EventDispatcher;

/**
 * Console subscriber
 *
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Console implements EventDispatcher\EventSubscriberInterface
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
