<?php declare(strict_types = 1);

namespace FastyBird\Core\Subscribers\HttpServer;

use Doctrine\DBAL;
use FastyBird\Core\Events;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use Symfony\Component\EventDispatcher;

/**
 * Database check subscriber
 */
readonly class Server implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(private ToolsHelpers\Database $database)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\HttpServerStartup::class => 'check',
			Events\HttpServerRequest::class => 'request',
			Events\HttpServerResponse::class => 'response',
		];
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 */
	public function check(): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new Exceptions\InvalidState('Connection to database could not be established');
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 */
	public function request(): void
	{
		$this->database->reconnect();

		// Make sure we don't work with outdated entities
		$this->database->clear();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function response(): void
	{
		// Clearing Doctrine's entity manager allows
		// for more memory to be released by PHP
		$this->database->clear();
	}

}
