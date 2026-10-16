<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Helpers;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Exceptions;
use Nette;
use Nette\Utils;
use const DIRECTORY_SEPARATOR;

/**
 * Data structure loader
 */
final class Loader
{

	/** @var array<mixed>|null */
	private array|null $gen2components = null;

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadGen2Components(): array
	{
		if ($this->gen2components === null) {
			$metadata = Shelly\DI\ShellyExtension::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'gen2_components.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->gen2components = (array) Utils\Json::decode($metadata, forceArrays: true);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Generation 2 components metadata could not be loaded');
			}
		}

		return $this->gen2components;
	}

}
