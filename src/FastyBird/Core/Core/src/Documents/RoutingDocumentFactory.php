<?php declare(strict_types = 1);

/**
 * RoutingDocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Core\Documents;

use FastyBird\Core\Exceptions;
use Nette\Utils;
use ReflectionClass;
use function array_key_exists;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Routing-key based document factory resolver, delegates the actual document construction
 * to DocumentFactory once it has found the document class matching a given routing key
 *
 * @package        FastyBird:Core!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RoutingDocumentFactory
{

	/** @var array<string, class-string<Document>>|null */
	private array|null $routingMap = null;

	/** @var Mapping\Driver\AttributeReader<Mapping\MappingAttribute> */
	private Mapping\Driver\AttributeReader $reader;

	public function __construct(
		private readonly Mapping\Driver\MappingDriver $mappingDriver,
		private readonly DocumentFactory $documentFactory,
	)
	{
		$this->reader = new Mapping\Driver\AttributeReader();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 * @throws Exceptions\Logic
	 */
	public function create(Utils\ArrayHash $data, string $routingKey): Document
	{
		return $this->documentFactory->create(
			$this->loadDocument($routingKey),
			$data,
		);
	}

	/**
	 * @return class-string<Document>
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
			if (!is_subclass_of($className, Document::class)) {
				continue;
			}

			$classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

			if (isset($classAttributes[Mapping\RoutingMap::class])) {
				$routingMapAttribute = $classAttributes[Mapping\RoutingMap::class];
				assert($routingMapAttribute instanceof Mapping\RoutingMap);

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
