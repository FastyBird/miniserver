<?php declare(strict_types = 1);

/**
 * ConsoleLogger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           06.10.22
 */

namespace FastyBird\MiniServer\Subscribers;

use Monolog;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Console;
use Symfony\Component\EventDispatcher;

/**
 * Console subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConsoleSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Monolog\Handler\AbstractProcessingHandler[]  */
	private array $loggerHandlers;

	/**
	 * @param Monolog\Handler\AbstractProcessingHandler[] $loggerHandlers
	 */
	public function __construct(
		array $loggerHandlers,
	) {
		$this->loggerHandlers = $loggerHandlers;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Console\ConsoleEvents::COMMAND => 'command',
		];
	}

	public function command(Console\Event\ConsoleCommandEvent $event): void
	{
		foreach ($this->loggerHandlers as $handler) {
			if (!$handler instanceof SymfonyMonolog\Handler\ConsoleHandler) {
				if ($handler->getLevel() < Monolog\Logger::INFO) {
					$handler->setLevel(Monolog\Logger::INFO);
				}
			}
		}
	}

}
