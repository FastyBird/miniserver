<?php declare(strict_types = 1);

/**
 * RedisDbCacheExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           16.08.24
 */

namespace FastyBird\Plugin\RedisDbCache\DI;

use FastyBird\Plugin\RedisDbCache\Caching as RedisDbCacheCaching;
use FastyBird\Plugin\RedisDbCache\Clients;
use FastyBird\Plugin\RedisDbCache\Connections;
use Nette\Bootstrap;
use Nette\Caching as NetteCaching;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB cache extension container
 *
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbCacheExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbCachePlugin';

	public static function register(
		Bootstrap\Configurator $config,
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
			'client' => Schema\Expect::structure([
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(6_379),
				'username' => Schema\Expect::string()->nullable(),
				'password' => Schema\Expect::string()->nullable(),
				'database' => Schema\Expect::int(0),
			]),
			'autowired' => Schema\Expect::bool(true),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('redis.configuration'), new DI\Definitions\ServiceDefinition())
			->setType(Connections\Configuration::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
				'database' => $configuration->client->database,
			]);

		$builder->addDefinition($this->prefix('clients'), new DI\Definitions\ServiceDefinition())
			->setType(Clients\Client::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->getDefinitionByType(NetteCaching\Storage::class)
			->setAutowired(false);

		$journal = $builder->addDefinition($this->prefix('cache.journal'), new DI\Definitions\ServiceDefinition())
			->setType(RedisDbCacheCaching\Journal::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('cache.storage'), new DI\Definitions\ServiceDefinition())
			->setType(RedisDbCacheCaching\Storage::class)
			->setArguments([
				'journal' => $journal,
			])
			->setAutowired($configuration->autowired);
	}

}
