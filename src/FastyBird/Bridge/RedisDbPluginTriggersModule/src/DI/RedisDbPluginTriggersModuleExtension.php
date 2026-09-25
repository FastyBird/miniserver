<?php declare(strict_types = 1);

/**
 * RedisDbPluginTriggersModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           02.11.22
 */

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\DI;

use FastyBird\Bridge\RedisDbPluginTriggersModule\Models;
use FastyBird\Core\Boot;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB triggers module bridge extension
 *
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbPluginTriggersModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbPluginTriggersModuleBridge';

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
			'database' => Schema\Expect::int(1),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition(
			$this->prefix('models.actionsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ActionsRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.conditionsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConditionsRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.actionsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ActionsManager::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.conditionsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConditionsManager::class)
			->setArguments(['database' => $configuration->database]);
	}

}
