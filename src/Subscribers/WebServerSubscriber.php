<?php declare(strict_types = 1);

/**
 * WebServerSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           15.04.20
 */

namespace FastyBird\MiniServer\Subscribers;

use FastyBird\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\MiniServer\Exceptions;
use FastyBird\WebServerPlugin\Events as WebServerPluginEvents;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Database check subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebServerSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var BootstrapHelpers\Database */
	private BootstrapHelpers\Database $database;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			WebServerPluginEvents\StartupEvent::class  => 'check',
			WebServerPluginEvents\RequestEvent::class  => 'request',
			WebServerPluginEvents\ResponseEvent::class => 'response',
		];
	}

	public function __construct(
		BootstrapHelpers\Database $database
	) {
		$this->database = $database;
	}

	/**
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function check(): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new Exceptions\InvalidStateException('Connection to database could not be established');
			}
		}
	}

	/**
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function request(): void
	{
		if (!$this->database->ping()) {
			$this->database->reconnect();
		}

		// Make sure we don't work with outdated entities
		$this->database->clear();
	}

	/**
	 * @return void
	 */
	public function response(): void
	{
		// Clearing Doctrine's entity manager allows
		// for more memory to be released by PHP
		$this->database->clear();

		// Just in case PHP would choose not to run garbage collection,
		// we run it manually at the end of each batch so that memory is
		// regularly released
		gc_collect_cycles();
	}

}