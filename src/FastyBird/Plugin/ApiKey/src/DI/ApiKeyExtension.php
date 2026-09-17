<?php declare(strict_types = 1);

/**
 * ApiKeyExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\DI;

use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Plugin\ApiKey\Commands;
use FastyBird\Plugin\ApiKey\Middleware;
use FastyBird\Plugin\ApiKey\Models;
use Nette;
use Nette\Bootstrap;
use Nette\DI;
use Nettrine\ORM as NettrineORM;
use const DIRECTORY_SEPARATOR;

/**
 * API key plugin
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiKeyExtension extends DI\CompilerExtension
{

	public const NAME = 'fbApiKeyPlugin';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('models.keysRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\KeyRepository::class);

		$builder->addDefinition($this->prefix('models.keysManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\KeysManager::class);

		$builder->addDefinition($this->prefix('commands.create'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Create::class);

		$builder->addDefinition($this->prefix('middlewares.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Validator::class);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		/**
		 * DOCTRINE ENTITIES
		 */

		// nettrine/orm 0.10 tags the per-manager MappingDriverChain, not an AttributeDriver, so
		// there is no service left to call addPaths() on. MappingHelper is nettrine's own
		// entry point for contributing a mapping to a manager from another extension.
		NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
			'default',
			'FastyBird\Plugin\ApiKey\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);
	}

}
