<?php declare(strict_types = 1);

/**
 * AttributeDriver.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Core\Documents\Mapping\Driver;

use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use function array_keys;
use function implode;
use function spl_object_hash;
use function sprintf;
use function str_starts_with;

/**
 * Document mapping attribute driver
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MappingDriverChain implements MappingDriver
{

	private AttributeDriver|null $defaultDriver = null;

	/** @var array<string, AttributeDriver> */
	private array $drivers = [];

	/**
	 * Gets the default driver
	 */
	public function getDefaultDriver(): AttributeDriver|null
	{
		return $this->defaultDriver;
	}

	/**
	 * Set the default driver
	 */
	public function setDefaultDriver(AttributeDriver|null $defaultDriver): void
	{
		$this->defaultDriver = $defaultDriver;
	}

	/**
	 * Adds a nested driver
	 */
	public function addDriver(AttributeDriver $driver, string $namespace): void
	{
		$this->drivers[$namespace] = $driver;
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $metadata
	 *
	 * @throws Exceptions\Logic
	 */
	public function loadMetadataForClass(Documents\Mapping\ClassMetadata $metadata): void
	{
		foreach ($this->drivers as $namespace => $driver) {
			if (str_starts_with($metadata->getName(), $namespace)) {
				$driver->loadMetadataForClass($metadata);

				return;
			}
		}

		if ($this->defaultDriver !== null) {
			$this->defaultDriver->loadMetadataForClass($metadata);

			return;
		}

		throw new Exceptions\Logic(sprintf(
			'The class: "%s" was not found in the chain configured namespaces %s',
			$metadata->getName(),
			implode(', ', array_keys($this->drivers)),
		));
	}

	/**
	 * Gets the names of all mapped classes known to this driver.
	 *
	 * @return array<int, class-string<object>>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getAllClassNames(): array
	{
		$classNames = [];
		$driverClasses = [];

		foreach ($this->drivers as $namespace => $driver) {
			$oid = spl_object_hash($driver);

			if (!isset($driverClasses[$oid])) {
				$driverClasses[$oid] = $driver->getAllClassNames();
			}

			foreach ($driverClasses[$oid] as $className) {
				if (!str_starts_with($className, $namespace)) {
					continue;
				}

				$classNames[$className] = true;
			}
		}

		if ($this->defaultDriver !== null) {
			foreach ($this->defaultDriver->getAllClassNames() as $className) {
				$classNames[$className] = true;
			}
		}

		return array_keys($classNames);
	}

	/**
	 * Returns whether the class with the specified name should have its metadata loaded.
	 * This is only the case if it is either mapped as a Document or a MappedSuperclass.
	 *
	 * @param class-string $className
	 */
	public function isTransient(string $className): bool
	{
		foreach ($this->drivers as $namespace => $driver) {
			if (str_starts_with($className, $namespace)) {
				return $driver->isTransient($className);
			}
		}

		if ($this->defaultDriver !== null) {
			return $this->defaultDriver->isTransient($className);
		}

		return true;
	}

}
