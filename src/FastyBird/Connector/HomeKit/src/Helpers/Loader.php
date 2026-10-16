<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Helpers;

use FastyBird\Connector\HomeKit\Exceptions;
use Nette;
use Nette\Utils;
use const DIRECTORY_SEPARATOR;

/**
 * Data structure loader
 */
final class Loader
{

	private const string RESOURCES_FOLDER = __DIR__ . '/../../resources';

	/** @var array<mixed>|null */
	private array|null $accessories = null;

	/** @var array<mixed>|null */
	private array|null $services = null;

	/** @var array<mixed>|null */
	private array|null $characteristics = null;

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadAccessories(): array
	{
		if ($this->accessories === null) {
			$metadata = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'accessories.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->accessories = (array) Utils\Json::decode($metadata, forceArrays: true);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Accessories metadata could not be loaded');
			}
		}

		return $this->accessories;
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadServices(): array
	{
		if ($this->services === null) {
			$metadata = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'services.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->services = (array) Utils\Json::decode($metadata, forceArrays: true);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Services metadata could not be loaded');
			}
		}

		return $this->services;
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadCharacteristics(): array
	{
		if ($this->characteristics === null) {
			$metadata = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'characteristics.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->characteristics = (array) Utils\Json::decode($metadata, forceArrays: true);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Characteristics metadata could not be loaded');
			}
		}

		return $this->characteristics;
	}

}
