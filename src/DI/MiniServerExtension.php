<?php declare(strict_types = 1);

/**
 * MiniServerExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\DI;

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\MiniServer\Commands;
use Nette;
use Nette\DI;

/**
 * MiniServer application
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MiniServerExtension extends DI\CompilerExtension
{

	public const NAME = 'fbFbMiniServer';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new MiniServerExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('command.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);
	}

}
