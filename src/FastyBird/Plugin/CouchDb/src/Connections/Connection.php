<?php declare(strict_types = 1);

/**
 * Connection.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Connections
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\Connections;

use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\CouchDb\Exceptions;
use Nette;
use PHPOnCouch;
use Psr\Log;
use Throwable;
use function str_replace;

/**
 * Couch DB connection configuration
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connection
{

	use Nette\SmartObject;

	private PHPOnCouch\CouchClient|null $client = null;

	public function __construct(
		private readonly string $database,
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 5_984,
		private readonly string|null $username = null,
		private readonly string|null $password = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getUsername(): string|null
	{
		return $this->username;
	}

	public function getPassword(): string|null
	{
		return $this->password;
	}

	public function getDatabase(): string
	{
		return $this->database;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getClient(): PHPOnCouch\CouchClient
	{
		if ($this->client !== null) {
			return $this->client;
		}

		try {
			$this->client = new PHPOnCouch\CouchClient($this->buildDsn(), $this->database);

			if (!$this->client->databaseExists()) {
				$this->client->createDatabase();
			}

			return $this->client;
		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error('Could not connect do database', [
				'source' => MetadataTypes\Sources\Plugin::COUCHDB->value,
				'type' => 'connection',
				'exception' => Logging\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidState('Connection could not be established', 0, $ex);
		}
	}

	private function buildDsn(): string
	{
		$credentials = null;

		if ($this->username !== null) {
			$credentials .= $this->username . ':';
		}

		if ($this->password !== null) {
			$credentials .= ':' . $this->password;
		}

		if ($credentials !== null) {
			$credentials = str_replace('::', ':', $credentials) . '@';
		}

		return 'http://' . $credentials . $this->host . ':' . $this->port;
	}

}
