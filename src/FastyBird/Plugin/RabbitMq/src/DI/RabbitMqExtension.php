<?php declare(strict_types = 1);

/**
 * RabbitMqExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           19.06.20
 */

namespace FastyBird\Plugin\RabbitMq\DI;

use FastyBird\Core\Boot;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Handlers;
use FastyBird\Plugin\RabbitMq\Publishers;
use FastyBird\Plugin\RabbitMq\Subscribers;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RabbitMqExtension extends DI\CompilerExtension
{

	public const EXCHANGE_NAME = 'fb.exchange.bus';

	public const NAME = 'fbRabbitMqPlugin';

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
			'client' => Schema\Expect::structure([
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(5_672),
				'vhost' => Schema\Expect::string('/'),
				'username' => Schema\Expect::string('guest'),
				'password' => Schema\Expect::string('guest'),
			]),
			'exchange' => Schema\Expect::structure([
				'name' => Schema\Expect::string()->default(self::EXCHANGE_NAME),
			]),
			'queue' => Schema\Expect::structure([
				'name' => Schema\Expect::string()->default(null),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$publisher = $builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'exchangeName' => $configuration->exchange->name,
			]);

		$builder->addDefinition($this->prefix('rabbit.connection'), new DI\Definitions\ServiceDefinition())
			->setType(Connections\Connection::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'vhost' => $configuration->client->vhost,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			]);

		$builder->addDefinition($this->prefix('channels.sync'), new DI\Definitions\ServiceDefinition())
			->setType(Channels\Channel::class);

		$builder->addDefinition($this->prefix('channels.async.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Channels\Factory::class)
			->setArguments([
				'exchangeName' => $configuration->exchange->name,
				'queueName' => $configuration->queue->name,
			]);

		$builder->addDefinition($this->prefix('handlers.message'), new DI\Definitions\ServiceDefinition())
			->setType(Handlers\Message::class);

		$builder->addDefinition($this->prefix('utilities.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\IdentifierGenerator::class);

		$builder->addDefinition($this->prefix('subscribers.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Channel::class)
			->setArguments([
				'publisher' => $publisher,
			]);
	}

}
