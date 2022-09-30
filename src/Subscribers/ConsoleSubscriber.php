<?php declare(strict_types = 1);

/**
 * ConsoleSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           30.09.22
 */

namespace FastyBird\MiniServer\Subscribers;

use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Console;
use Symfony\Component\EventDispatcher;

/**
 * Console commands subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConsoleSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var SymfonyMonolog\Handler\ConsoleHandler */
	private SymfonyMonolog\Handler\ConsoleHandler $consoleHandler;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			Console\ConsoleEvents::COMMAND  => 'command',
		];
	}

	/**
	 * @param SymfonyMonolog\Handler\ConsoleHandler $consoleHandler
	 */
	public function __construct(
		SymfonyMonolog\Handler\ConsoleHandler $consoleHandler
	) {
		$this->consoleHandler = $consoleHandler;
	}

	/**
	 * @param Console\Event\ConsoleCommandEvent $event
	 *
	 * @return void
	 */
	public function command(Console\Event\ConsoleCommandEvent $event): void
	{
		$this->consoleHandler->setOutput($event->getOutput());
	}

}
