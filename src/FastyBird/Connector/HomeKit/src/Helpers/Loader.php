<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Helpers;

use FastyBird\Connector\HomeKit;
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

	private Utils\ArrayHash|null $accessories = null;

	private Utils\ArrayHash|null $services = null;

	private Utils\ArrayHash|null $characteristics = null;

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadAccessories(): Utils\ArrayHash
	{
		if ($this->accessories === null) {
			$metadata = HomeKit\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'accessories.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->accessories = Utils\ArrayHash::from(
					(array) Utils\Json::decode($metadata, forceArrays: true),
				);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Accessories metadata could not be loaded');
			}
		}

		return $this->accessories;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadServices(): Utils\ArrayHash
	{
		if ($this->services === null) {
			$metadata = HomeKit\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'services.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->services = Utils\ArrayHash::from((array) Utils\Json::decode($metadata, forceArrays: true));
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Services metadata could not be loaded');
			}
		}

		return $this->services;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadCharacteristics(): Utils\ArrayHash
	{
		if ($this->characteristics === null) {
			$metadata = HomeKit\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'characteristics.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->characteristics = Utils\ArrayHash::from(
					(array) Utils\Json::decode($metadata, forceArrays: true),
				);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Characteristics metadata could not be loaded');
			}
		}

		return $this->characteristics;
	}

}
