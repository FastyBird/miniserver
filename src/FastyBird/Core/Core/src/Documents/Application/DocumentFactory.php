<?php declare(strict_types = 1);

/**
 * DocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Core\Documents\Application;

use FastyBird\Core\Documents\Application as Documents;
use FastyBird\Core\Events\Application as Events;
use FastyBird\Core\Exceptions;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher;
use function array_key_exists;
use function assert;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Data document factory
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DocumentFactory
{

	public function __construct(
		private Documents\Mapping\ClassMetadataFactory $classMetadataFactory,
		private ObjectMapper\Processing\Processor $documentMapper,
		private EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $documentClass
	 * @param array<string, mixed>|string|object $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\MalformedInput
	 * @throws Exceptions\Logic
	 */
	public function create(string $documentClass, array|string|object $data): Documents\Document
	{
		if (is_string($data)) {
			try {
				/** @var array<string, mixed> $data */
				$data = Utils\Json::decode($data, forceArrays: true);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
			}
		} elseif (is_object($data)) {
			try {
				/** @var array<string, mixed> $data */
				$data = Utils\Json::decode(Utils\Json::encode($data), forceArrays: true);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
			}
		}

		$metadata = $this->classMetadataFactory->getMetadataFor($documentClass);

		if (!$metadata->isInheritanceTypeNone()) {
			$discriminatorColumnSettings = $metadata->getDiscriminatorColumn();

			if (
				$discriminatorColumnSettings === null
				|| !array_key_exists('name', $discriminatorColumnSettings)
				|| !array_key_exists('type', $discriminatorColumnSettings)
			) {
				throw new Exceptions\InvalidState(sprintf(
					'Discriminator column configuration is missing on class: "%s"',
					$metadata->getName(),
				));
			}

			$discriminatorColumn = $discriminatorColumnSettings['name'];

			if (!array_key_exists($discriminatorColumn, $data)) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Discriminator column: "%s" is missing in data',
					$discriminatorColumn,
				));
			}

			$type = $data[$discriminatorColumn];
			assert(is_string($type) || is_int($type));

			$discriminatorMap = $metadata->getDiscriminatorMap();

			if (!array_key_exists($type, $discriminatorMap)) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Missing discriminator map record for key: "%s" in class: "%s"',
					$type,
					$metadata->getName(),
				));
			}

			if ($metadata->isRootDocument() || $metadata->isAbstract()) {
				$documentClass = $discriminatorMap[$type];
			} elseif ($metadata->getDiscriminatorValue() !== $type) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Provided document class is different than discriminator value: "%s"',
					$metadata->getName(),
				));
			}
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			$preLoadEvent = new Events\PreLoad($data, $documentClass);
			$this->dispatcher?->dispatch($preLoadEvent);

			$document = $this->documentMapper->process($preLoadEvent->getData(), $documentClass, $options);

			$postLoadEvent = new Events\PostLoad($document);
			$this->dispatcher?->dispatch($postLoadEvent);

			return $postLoadEvent->getDocument();
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to document: ' . $errorPrinter->printError($ex));
		}
	}

}
