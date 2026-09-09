<?php declare(strict_types = 1);

namespace FastyBird\Core\Application\Tests\Cases\Unit;

use Error;
use FastyBird\Core\Application\Boot;
use FastyBird\Core\Application\Exceptions;
use Nette;
use PHPUnit\Framework\TestCase;
use function constant;
use function defined;
use function file_exists;
use function getmypid;
use function md5;
use function time;

abstract class BaseTestCase extends TestCase
{

	protected Nette\DI\Container $container;

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 */
	protected function createContainer(string|null $additionalConfig = null): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = Boot\Bootstrap::boot();
		$config->setForceReloadContainer();
		$config->setTempDirectory(FB_TEMP_DIR);

		$config->addStaticParameters(
			['container' => ['class' => 'SystemContainer_' . getmypid() . md5((string) time())]],
		);
		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		if ($additionalConfig !== null && file_exists($additionalConfig)) {
			$config->addConfig($additionalConfig);
		}

		$config->setTimeZone('Europe/Prague');

		return $config->createContainer();
	}

	/**
	 * @param class-string $serviceType
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$foundServiceNames = $this->container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	private function replaceContainerService(string $serviceName, object $service): void
	{
		$this->container->removeService($serviceName);
		$this->container->addService($serviceName, $service);
	}

}
