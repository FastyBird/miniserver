<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit;

use Error;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Ui\DI;
use Nette;
use PHPUnit\Framework\TestCase;
use function constant;
use function defined;
use function in_array;
use function md5;

abstract class BaseTestCase extends TestCase
{

	protected Nette\DI\Container|null $container = null;

	/** @var array<string> */
	protected array $neonFiles = [];

	/**
	 * @param class-string $serviceType
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$container = $this->getContainer();
		$foundServiceNames = $container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	protected function getContainer(): Nette\DI\Container
	{
		if ($this->container === null) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	private function createContainer(): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = ApplicationBoot\Bootstrap::boot();
		// Per package, because several caches under the temp directory use a fixed filename
		// and would otherwise collide now that the directory is shared. The translation
		// catalogue is the clearest case: Symfony names it from a hash of the fallback
		// locales alone, which every package configures identically, and with debugMode off
		// the ConfigCache has no resource checkers and treats any existing file as fresh.
		$config->setTempDirectory(FB_TEMP_DIR . '/' . md5($rootDir));

		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		foreach ($this->neonFiles as $neonFile) {
			$config->addConfig($neonFile);
		}

		$config->setTimeZone('Europe/Prague');

		DI\UiExtension::register($config);

		$this->container = $config->createContainer();

		return $this->container;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	private function replaceContainerService(string $serviceName, object $service): void
	{
		$container = $this->getContainer();

		$container->removeService($serviceName);
		$container->addService($serviceName, $service);
	}

	protected function registerNeonConfigurationFile(string $file): void
	{
		if (!in_array($file, $this->neonFiles, true)) {
			$this->neonFiles[] = $file;
		}
	}

	protected function tearDown(): void
	{
		$this->container = null; // Release the container; the next test builds its own instance

		parent::tearDown();
	}

}
