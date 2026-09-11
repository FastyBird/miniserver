<?php declare(strict_types = 1);

/**
 * Configurator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Boot
 * @since          1.0.0
 *
 * @date           25.10.22
 */

namespace FastyBird\Core\Application\Boot;

use Composer\Autoload\ClassLoader;
use Nette\Bootstrap;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use ReflectionClass;
use function array_keys;
use function assert;
use function boolval;
use function class_exists;
use function filemtime;
use function is_file;
use function is_subclass_of;
use function strval;
use function unlink;
use const DIRECTORY_SEPARATOR;
use const PHP_RELEASE_VERSION;
use const PHP_VERSION_ID;

/**
 * Extended container configurator
 *
 * @package        FastyBird:Application!
 * @subpackage     Boot
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Configurator extends Bootstrap\Configurator
{

	private bool $forceReloadContainer = false;

	public function setForceReloadContainer(bool $force = true): void
	{
		$this->forceReloadContainer = $force;
	}

	/**
	 * @return class-string<Container>
	 */
	public function loadContainer(): string
	{
		/** @infection-ignore-all */
		$buildDir = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . 'fb.di.configurator';

		/** @infection-ignore-all */
		$loader = new ContainerLoader(
			$buildDir,
			boolval($this->staticParameters['debugMode']),
		);

		// @phpstan-ignore argument.type (Nette Bootstrap\Configurator's real @var list<string|array<string, mixed>> for $configs is wider than this method's own array<string|array<string>> $configFiles declaration)
		$containerKey = $this->getContainerKey($this->configs);

		$this->reloadContainerOnDemand($loader, $containerKey, $buildDir);

		$containerClass = $loader->load(
			// @phpstan-ignore argument.type (Nette\DI\ContainerLoader::load()'s real PHPDoc expects callable(Compiler): ?string, but the parent Configurator::generateContainer() called here returns void)
			fn (Compiler $compiler) => $this->generateContainer($compiler),
			$containerKey,
		);
		assert(is_subclass_of($containerClass, Container::class));

		return $containerClass;
	}

	/**
	 * @param array<string|array<string>> $configFiles
	 * @return array<int|string, mixed>
	 */
	private function getContainerKey(array $configFiles): array
	{
		/** @infection-ignore-all */
		return [
			$this->staticParameters,
			array_keys($this->dynamicParameters),
			$configFiles,
			PHP_VERSION_ID - PHP_RELEASE_VERSION,
			class_exists(ClassLoader::class)
				? filemtime(
					strval((new ReflectionClass(ClassLoader::class))->getFileName()),
				)
				: null,
		];
	}

	/**
	 * @param array<int|string, mixed> $containerKey
	 */
	private function reloadContainerOnDemand(ContainerLoader $loader, array $containerKey, string $buildDir): void
	{
		$this->forceReloadContainer
		&& !class_exists($containerClass = $loader->getClassName($containerKey), false)
		&& is_file($file = $buildDir . DIRECTORY_SEPARATOR . $containerClass . '.php')
		&& unlink($file);
	}

}
