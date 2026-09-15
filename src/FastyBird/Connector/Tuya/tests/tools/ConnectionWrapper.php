<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Tools;

use Doctrine\Common\EventManager;
use Doctrine\DBAL;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use WeakReference;
use function assert;
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

	/**
	 * Widened to public deliberately, and typed to the driver connection.
	 *
	 * DBAL 3 declares connect() public with no return type; DBAL 4 declares it protected
	 * returning the driver connection. A subclass may widen visibility but not narrow it, and
	 * may add a return type where the parent declares none -- so this one declaration is legal
	 * under both. Public is not a choice here: DBAL 3's parent is public and PHP forbids
	 * reducing visibility, so protected would be a fatal today. Nothing outside calls it --
	 * DBAL itself does, on the way to every statement.
	 *
	 * @throws DBAL\Exception
	 */
	public function connect(): Driver\Connection
	{
		// Taken BEFORE the parent call, because the two majors signal "was already connected"
		// differently and neither signal survives the call. DBAL 3's connect() returns false
		// when $_conn is already set; DBAL 4's returns the existing connection and has no
		// boolean at all. Getting this wrong would DROP and re-CREATE the database on every
		// reconnect, losing the fixtures mid-test.
		//
		// It also makes the method safely re-entrant, which it has to be: the statements below
		// go through executeStatement(), and on DBAL 4 every statement calls connect() again.
		$alreadyConnected = $this->isConnected();

		parent::connect();

		$connection = $this->_conn;
		assert($connection !== null);

		if ($alreadyConnected) {
			return $connection;
		}

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
		$wrapper = WeakReference::create($this);
		$dbName = $this->dbName;

		register_shutdown_function(
			static function () use ($wrapper, $dbName): void {
				$wrapper->get()?->executeStatement(
					sprintf('DROP DATABASE IF EXISTS `%s`', $dbName),
				);
			},
		);

		return $connection;
	}

}
