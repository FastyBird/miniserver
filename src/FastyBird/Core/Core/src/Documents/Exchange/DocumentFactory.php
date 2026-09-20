<?php declare(strict_types = 1);

/**
 * DocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Core\Documents\Exchange;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Documents\Exchange as Documents;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions;
use Nette\Utils;
use ReflectionClass;
use function array_key_exists;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Exchange document factory
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DocumentFactory
{

	/** @var array<string, class-string<ApplicationDocuments\Document>>|null */
	private array|null $routingMap = null;

	/** @var Documents\Mapping\Driver\AttributeReader<Documents\Mapping\MappingAttribute> */
	private Documents\Mapping\Driver\AttributeReader $reader;

	public function __construct(
		private readonly ApplicationDocuments\Mapping\Driver\MappingDriver $mappingDriver,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
	)
	{
		$this->reader = new Documents\Mapping\Driver\AttributeReader();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Mapping
	 */
	public function create(
		Utils\ArrayHash $data,
		string $routingKey,
	): ApplicationDocuments\Document
	{
		return $this->documentFactory->create(
			$this->loadDocument($routingKey),
			$data,
		);
	}

	/**
	 * @return class-string<ApplicationDocuments\Document>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function loadDocument(string $routingKey): string
	{
		if ($this->routingMap === null) {
			$this->initialize();
		}

		if ($this->routingMap !== null && array_key_exists($routingKey, $this->routingMap)) {
			return $this->routingMap[$routingKey];
		}

		throw new Exceptions\InvalidState(
			sprintf('Document class was not found for provided message and routing key: %s', $routingKey),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function initialize(): void
	{
		$this->routingMap = [];

		foreach ($this->mappingDriver->getAllClassNames() as $className) {
			if (!is_subclass_of($className, ApplicationDocuments\Document::class)) {
				continue;
			}

			$classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

			if (isset($classAttributes[Documents\Mapping\RoutingMap::class])) {
				$routingMapAttribute = $classAttributes[Documents\Mapping\RoutingMap::class];
				assert($routingMapAttribute instanceof Documents\Mapping\RoutingMap);

				foreach ($routingMapAttribute->value as $route) {
					if (array_key_exists($route, $this->routingMap)) {
						throw new Exceptions\InvalidState(sprintf(
							'Found duplicate route definition: "%s" for document class: "%s"',
							$route,
							$className,
						));
					}

					$this->routingMap[$route] = $className;
				}
			}
		}
	}

}
