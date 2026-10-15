<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Core\Documents;
use Nette;
use Nette\Utils;
use const DIRECTORY_SEPARATOR;

/**
 * Mapping builder
 */
final class Builder
{

	private const string RESOURCES_FOLDER = __DIR__ . '/../../resources';

	private Mapping\Gen1|null $gen1 = null;

	private Mapping\Gen2|null $gen2 = null;

	public function __construct(
		private readonly Documents\Mapping\Mapper $mapper,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getGen1Mapping(): Mapping\Gen1
	{
		if ($this->gen1 === null) {
			try {
				$mapping = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'gen1.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Generation 1 mapping could not be loaded');
			}

			$this->gen1 = $this->create(Mapping\Gen1::class, ['accessories' => $data]);
		}

		return $this->gen1;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getGen2Mapping(): Mapping\Gen2
	{
		if ($this->gen2 === null) {
			try {
				$mapping = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'gen2.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Generation 2 mapping could not be loaded');
			}

			$this->gen2 = $this->create(Mapping\Gen2::class, ['accessories' => $data]);
		}

		return $this->gen2;
	}

	/**
	 * @template T of Mapping\Mapping
	 *
	 * @param class-string<T> $mapping
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	private function create(string $mapping, array $data): Mapping\Mapping
	{
		try {
			return $this->mapper->map(
				$data,
				$mapping,
				new Documents\Mapping\MappingOptions(allowUnknownFields: true),
			);
		} catch (Documents\Exceptions\MappingFailed $ex) {
			throw new Exceptions\Runtime('Could not map data to mapping: ' . $ex->getMessage());
		}
	}

}
