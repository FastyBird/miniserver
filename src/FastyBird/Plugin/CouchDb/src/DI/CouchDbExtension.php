<?php declare(strict_types = 1);

/**
 * CouchDbExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.12.20
 */

namespace FastyBird\Plugin\CouchDb\DI;

use FastyBird\Core\Boot;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Models;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * CouchDB state storage extension container
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CouchDbExtension extends DI\CompilerExtension
{

	public const NAME = 'fbCouchDbPlugin';

	public static function register(
		Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'connection' => Schema\Expect::structure([
				'database' => Schema\Expect::string()->default('state_storage'),
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(5_672),
				'username' => Schema\Expect::string('guest')->nullable(),
				'password' => Schema\Expect::string('guest')->nullable(),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('connection'))
			->setType(Connections\Connection::class)
			->setArguments([
				'database' => $configuration->connection->database,
				'host' => $configuration->connection->host,
				'port' => $configuration->connection->port,
				'username' => $configuration->connection->username,
				'password' => $configuration->connection->password,
			]);

		$builder->addDefinition($this->prefix('model.statesManager'))
			->setType(Models\States\StatesManager::class);

		$builder->addDefinition($this->prefix('model.stateRepository'))
			->setType(Models\States\StatesRepository::class);
	}

}
