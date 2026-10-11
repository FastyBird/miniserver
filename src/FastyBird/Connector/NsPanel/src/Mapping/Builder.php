<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Mapping;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Mapping;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use const DIRECTORY_SEPARATOR;

/**
 * Mapping builder
 */
final class Builder
{

	private Mapping\Categories|null $categories = null;

	private Mapping\Capabilities|null $capabilities = null;

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $processor,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getCategoriesMapping(): Mapping\Categories
	{
		if ($this->categories === null) {
			try {
				$mapping = NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'categories.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Categories mapping could not be loaded');
			}

			$this->categories = $this->create(Mapping\Categories::class, ['categories' => $data]);
		}

		return $this->categories;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getCapabilitiesMapping(): Mapping\Capabilities
	{
		if ($this->capabilities === null) {
			try {
				$mapping = NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'capabilities.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Capabilities mapping could not be loaded');
			}

			$this->capabilities = $this->create(Mapping\Capabilities::class, ['groups' => $data]);
		}

		return $this->capabilities;
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
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->processor->process($data, $mapping, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to mapping: ' . $errorPrinter->printError($ex));
		}
	}

}
