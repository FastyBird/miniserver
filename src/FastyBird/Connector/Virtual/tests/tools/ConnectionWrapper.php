<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Tools;

use Doctrine\Common\EventManager;
use Doctrine\DBAL;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use WeakReference;
use function bin2hex;
use function getmypid;
use function random_bytes;
use function register_shutdown_function;
use function sprintf;

class ConnectionWrapper extends DBAL\Connection
{

	private string $dbName;

	/**
	 * @param array<mixed> $params
	 *
	 * @throws DBAL\Exception
	 */
	public function __construct(
		array $params,
		Driver $driver,
		Configuration|null $config = null,
		EventManager|null $eventManager = null,
	)
	{
		// Unique per connection, not per process-second.
		//
		// This used to be getmypid() plus md5(time()), which is a single value for a whole
		// second within one process. Two connections opened in the same second in the same
		// process therefore shared a name, and the second one's connect() would DROP and
		// re-CREATE the first one's database out from under it. That cannot happen while
		// every DbTestCase runs in its own forked process, and becomes live the moment they
		// share one. The pid is kept so a stray database is still traceable to a process.
		$this->dbName = 'fb_test_' . getmypid() . '_' . bin2hex(random_bytes(6));

		unset($params['dbname']);

		parent::__construct($params, $driver, $config, $eventManager);
	}

	public function connect(): bool
	{
		if (parent::connect()) {
			$this->executeStatement(sprintf('DROP DATABASE IF EXISTS `%s`', $this->dbName));
			$this->executeStatement(sprintf('CREATE DATABASE `%s`', $this->dbName));
			$this->executeStatement(sprintf('USE `%s`', $this->dbName));

			// Drop on shutdown, through a weak reference.
			//
			// This closure used to capture $this. A shutdown function cannot be unregistered,
			// so that pinned the connection -- and the connection holds Nettrine's
			// ContainerAwareEventManager, which holds the whole Nette DI container -- for the
			// life of the process. Invisible while every DbTestCase runs in its own forked
			// process, because exactly one container exists per child, but it would leak one
			// container per test the moment tests share a process.
			//
			// WeakReference does not keep the connection alive, so nothing is pinned. While
			// the connection outlives the test, as it does today, the cleanup is unchanged.
			$connection = WeakReference::create($this);
			$dbName = $this->dbName;

			register_shutdown_function(
				static function () use ($connection, $dbName): void {
					$connection->get()?->executeStatement(
						sprintf('DROP DATABASE IF EXISTS `%s`', $dbName),
					);
				},
			);

			return true;
		}

		return false;
	}

}
