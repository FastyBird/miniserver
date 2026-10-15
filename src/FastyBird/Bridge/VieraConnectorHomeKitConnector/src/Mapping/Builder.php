<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
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

	private Mapping\Services|null $mapping = null;

	public function __construct(
		private readonly Documents\Mapping\Mapper $mapper,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getServicesMapping(): Mapping\Services
	{
		if ($this->mapping === null) {
			try {
				$mapping = self::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'mapping.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Services mapping could not be loaded');
			}

			$this->mapping = $this->create(Mapping\Services::class, $data);
		}

		return $this->mapping;
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
