<?php declare(strict_types = 1);

/**
 * RedisDbExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           25.02.21
 */

namespace FastyBird\Plugin\RedisDb\DI;

use FastyBird\Core\Boot as ApplicationBoot;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Exchange;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbPlugin';

	public static function register(
		ApplicationBoot\Configurator $config,
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
			]),
			'exchange' => Schema\Expect::structure([
				'channel' => Schema\Expect::string()->default(Metadata\Constants::EXCHANGE_CHANNEL_NAME),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition($this->prefix('publisher.async'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Async\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition($this->prefix('redis.configuration'), new DI\Definitions\ServiceDefinition())
			->setType(Connections\Configuration::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			]);

		$builder->addDefinition($this->prefix('clients.sync'), new DI\Definitions\ServiceDefinition())
			->setType(Clients\Client::class);

		$builder->addDefinition($this->prefix('clients.async'), new DI\Definitions\ServiceDefinition())
			->setType(Clients\Async\Client::class);

		$builder->addDefinition($this->prefix('states.factory'), new DI\Definitions\ServiceDefinition())
			->setType(States\StateFactory::class);

		$builder->addDefinition($this->prefix('models.statesManagerFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\StatesManagerFactory::class);

		$builder->addDefinition(
			$this->prefix('models.statesManagerFactory.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Async\StatesManagerFactory::class);

		$builder->addDefinition($this->prefix('models.statesRepositoryFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\StatesRepositoryFactory::class);

		$builder->addDefinition(
			$this->prefix('models.statesRepositoryFactory.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Async\StatesRepositoryFactory::class);

		$builder->addDefinition($this->prefix('exchange.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Exchange\Factory::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition($this->prefix('exchange.handler'), new DI\Definitions\ServiceDefinition())
			->setType(Exchange\Handler::class);

		$builder->addDefinition($this->prefix('utilities.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\IdentifierGenerator::class);
	}

}
