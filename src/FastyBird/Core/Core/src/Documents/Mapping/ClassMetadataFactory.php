<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use FastyBird\Core\Documents;
use FastyBird\Core\Events;
use FastyBird\Core\Exceptions;
use Nette\Caching;
use Psr\EventDispatcher;
use ReflectionClass;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_unshift;
use function array_values;
use function assert;
use function class_exists;
use function class_parents;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_subclass_of;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * Class metadata factory
 */
final class ClassMetadataFactory
{

	/** @var array<class-string<Documents\Document>, Documents\Mapping\ClassMetadata<Documents\Document>> */
	private array $loadedMetadata = [];

	private string $cacheSalt = '__CLASS_METADATA__';

	public function __construct(
		private readonly Documents\Mapping\Driver\MappingDriver $driver,
		private readonly Caching\Cache $cache,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $className
	 *
	 * @return Documents\Mapping\ClassMetadata<T>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Logic
	 */
	public function getMetadataFor(string $className): Documents\Mapping\ClassMetadata
	{
		$className = $this->normalizeClassName($className);

		if (isset($this->loadedMetadata[$className])) {
			/** @var ClassMetadata<T> $metadata */
			$metadata = $this->loadedMetadata[$className];

			return $metadata;
		}

		if (class_exists($className, false) && (new ReflectionClass($className))->isAnonymous()) {
			throw new Exceptions\InvalidArgument(sprintf('Provided document class: "%s" is anonymous', $className));
		}

		if (!class_exists($className, false) && str_contains($className, ':')) {
			throw new Exceptions\InvalidArgument(sprintf('Provided document class: "%s" is non existing', $className));
		}

		/** @var array<class-string<Documents\Document>, Documents\Mapping\ClassMetadata<Documents\Document>> $loadedMetadata */
		$loadedMetadata = $this->cache->load(
			$this->getCacheKey($className),
			fn () => $this->loadMetadata($className),
		);

		$this->loadedMetadata = [
			...$this->loadedMetadata,
			...$loadedMetadata,
		];

		/** @var ClassMetadata<T> $metadata */
		$metadata = $this->loadedMetadata[$className];

		return $metadata;
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $name
	 *
	 * @return array<class-string<Documents\Document>, Documents\Mapping\ClassMetadata<Documents\Document>>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Logic
	 */
	private function loadMetadata(string $name): array
	{
		$loaded = [];

		$parentClasses = $this->getParentClasses($name);
		$parentClasses[] = $name;

		// Move down the hierarchy of parent classes, starting from the topmost class
		$parent = null;
		$rootDocumentFound = false;
		$visited = [];

		foreach ($parentClasses as $className) {
			if (isset($this->loadedMetadata[$className])) {
				$parent = $this->loadedMetadata[$className];

				if (!$parent->isMappedSuperclass()) {
					$rootDocumentFound = true;

					array_unshift($visited, $className);
				}

				continue;
			}

			$class = new Documents\Mapping\ClassMetadata($className);

			$this->doLoadMetadata($class, $parent, $rootDocumentFound, $visited);

			$this->loadedMetadata[$className] = $class;
			$loaded[$className] = $class;

			$parent = $class;

			if (!$class->isMappedSuperclass()) {
				$rootDocumentFound = true;

				array_unshift($visited, $className);
			}
		}

		return $loaded;
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $class
	 * @param Documents\Mapping\ClassMetadata<T>|null $parent
	 * @param array<class-string<T>> $nonSuperclassParents
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Logic
	 */
	private function doLoadMetadata(
		Documents\Mapping\ClassMetadata $class,
		Documents\Mapping\ClassMetadata|null $parent,
		bool $rootDocumentFound,
		array $nonSuperclassParents,
	): void
	{
		if ($parent !== null) {
			$class->setInheritanceType($parent->getInheritanceType());
			$class->setDiscriminatorColumn($parent->getDiscriminatorColumn());
			$class->setDiscriminatorMap($parent->getDiscriminatorMap());
			$class->addSubClasses($parent->getSubClasses());
		}

		$this->driver->loadMetadataForClass($class);

		if (!$class->isMappedSuperclass()) {
			if ($rootDocumentFound && $class->isInheritanceTypeNone()) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Document class "%s" is a subclass of the root document class "%s", but no inheritance mapping type was declared',
					$class->getName(),
					end($nonSuperclassParents),
				));
			}
		}

		$class->setParentClasses($nonSuperclassParents);

		$this->dispatcher?->dispatch(new Events\LoadClassMetadata($class));

		if (
			$class->isRootDocument()
			&& $class->isInheritanceTypeSingleTable()
			&& $class->getDiscriminatorMap() === []
		) {
			$this->tryToFindDiscriminators($class);
		}

		if (
			$class->isRootDocument()
			&& !$class->isInheritanceTypeNone()
			&& $class->getDiscriminatorMap() === []
		) {
			$this->addDefaultDiscriminatorMap($class);
		}

		$this->findAbstractDocumentClassesNotListedInDiscriminatorMap($class);

		$this->validateRuntimeMetadata($class, $parent);
	}

	/**
	 * Adds a default discriminator map if no one is given
	 *
	 * If a document is of any inheritance type and does not contain a
	 * discriminator map, then the map is generated automatically.
	 * This process is expensive computation wise.
	 *
	 * The automatically generated discriminator map contains the lowercase short name of
	 * each class as key.
	 *
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $class
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Logic
	 */
	private function addDefaultDiscriminatorMap(Documents\Mapping\ClassMetadata $class): void
	{
		$allClasses = $this->driver->getAllClassNames();
		$fqcn = $class->getName();

		$map = [
			$this->getShortName($class->getName()) => $fqcn,
		];

		$duplicates = [];

		/** @var class-string<T> $subClassCandidate */
		foreach ($allClasses as $subClassCandidate) {
			if (is_subclass_of($subClassCandidate, $fqcn)) {
				$shortName = $this->getShortName($subClassCandidate);

				if (isset($map[$shortName])) {
					$duplicates[] = $shortName;
				}

				$map[$shortName] = $subClassCandidate;
			}
		}

		if ($duplicates !== []) {
			throw new Exceptions\InvalidArgument(sprintf(
				'The entries %s in discriminator map of class "%s" is duplicated. '
				. 'If the discriminator map is automatically generated you have to convert it to an explicit discriminator map now. '
				. 'The entries of the current map are: @DiscriminatorMap({%s})',
				implode(', ', $duplicates),
				$class->getName(),
				implode(', ', array_map(
					static fn ($a, $b) => sprintf("'%s': '%s'", $a, $b),
					array_keys($map),
					array_values($map),
				)),
			));
		}

		$class->setDiscriminatorMap($map);
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $class
	 *
	 * @throws Exceptions\Logic
	 */
	private function tryToFindDiscriminators(ClassMetadata $class): void
	{
		$discriminators = [];

		/** @var class-string<T> $className */
		foreach ($this->driver->getAllClassNames() as $className) {
			$childrenRc = new ReflectionClass($className);

			if ($childrenRc->getParentClass() === false) {
				continue;
			}

			if (!$childrenRc->isSubclassOf($class->getName())) {
				continue;
			}

			if ($childrenRc->isAbstract()) {
				continue;
			}

			$attributes = $childrenRc->getAttributes(DiscriminatorEntry::class);

			if ($attributes !== []) {
				$discriminatorEntry = $attributes[0]->newInstance();

				if (in_array($discriminatorEntry->name, array_keys($discriminators), true)) {
					throw new Exceptions\Logic(sprintf(
						'Found duplicate discriminator map entry "%s" in "%s".',
						$discriminatorEntry->name,
						$childrenRc->getName(),
					));
				}

				$discriminators[$discriminatorEntry->name] = $className;
			}
		}

		$class->setDiscriminatorMap($discriminators);
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $rootDocumentClass
	 */
	private function findAbstractDocumentClassesNotListedInDiscriminatorMap(
		Documents\Mapping\ClassMetadata $rootDocumentClass,
	): void
	{
		// Only root classes in inheritance hierarchies need contain a discriminator map,
		// so skip for other classes.
		if (!$rootDocumentClass->isRootDocument() || $rootDocumentClass->isInheritanceTypeNone()) {
			return;
		}

		$processedClasses = [$rootDocumentClass->getName() => true];

		foreach ($rootDocumentClass->getSubClasses() as $knownSubClass) {
			$processedClasses[$knownSubClass] = true;
		}

		foreach ($rootDocumentClass->getDiscriminatorMap() as $declaredClassName) {
			// This fetches non-transient parent classes only
			$parentClasses = $this->getParentClasses($declaredClassName);

			foreach ($parentClasses as $parentClass) {
				if (isset($processedClasses[$parentClass])) {
					continue;
				}

				$processedClasses[$parentClass] = true;

				// All non-abstract document classes must be listed in the discriminator map, and
				// this will be validated/enforced at runtime (possibly at a later time, when the
				// subclass is loaded, but anyways). Also, subclasses is about document classes only.
				// That means we can ignore non-abstract classes here. The (expensive) driver
				// check for mapped superclasses need only be run for abstract candidate classes.
				if (
					!(new ReflectionClass($parentClass))->isAbstract()
					|| $this->peekIfIsMappedSuperclass($parentClass)
				) {
					continue;
				}

				// We have found a non-transient, non-mapped-superclass = a document class (possibly abstract, but that does not matter)
				$rootDocumentClass->addSubClass($parentClass);
			}
		}
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $className
	 */
	private function peekIfIsMappedSuperclass(string $className): bool
	{
		$class = new Documents\Mapping\ClassMetadata($className);

		$this->driver->loadMetadataForClass($class);

		return $class->isMappedSuperclass();
	}

	/**
	 * Validate runtime metadata is correctly defined
	 *
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $class
	 * @param Documents\Mapping\ClassMetadata<T>|null $parent
	 *
	 * @throws Exceptions\Logic
	 */
	private function validateRuntimeMetadata(
		Documents\Mapping\ClassMetadata $class,
		Documents\Mapping\ClassMetadata|null $parent,
	): void
	{
		// Verify inheritance
		if (!$class->isMappedSuperclass() && !$class->isInheritanceTypeNone()) {
			if ($parent === null) {
				if ($class->getDiscriminatorMap() === []) {
					throw new Exceptions\Logic(sprintf(
						'Document class "%s" is using inheritance but no discriminator map was defined',
						$class->getName(),
					));
				}

				if ($class->getDiscriminatorColumn() === null) {
					throw new Exceptions\Logic(sprintf(
						'Document class "%s" is using inheritance but no discriminator column was defined',
						$class->getName(),
					));
				}

				foreach ($class->getSubClasses() as $subClass) {
					if ((new ReflectionClass($subClass))->name !== $subClass) {
						throw new Exceptions\Logic(sprintf(
							'Document class "%s" used in the discriminator map of class "%s" does not exist',
							$subClass,
							$class->getName(),
						));
					}
				}
			} else {
				if (
					!$class->getReflectionClass()->isAbstract()
					&& !in_array($class->getName(), $class->getDiscriminatorMap(), true)
				) {
					throw new Exceptions\Logic(sprintf(
						'Document "%s" has to be part of the discriminator map of "%s" '
						. 'to be properly mapped in the inheritance hierarchy. Alternatively you can make "%s" an abstract class '
						. 'to avoid this exception from occurring',
						$class->getName(),
						$class->getReflectionClass(),
						$class->getName(),
					));
				}
			}
		} elseif (
			$class->isMappedSuperclass()
			&& $class->getName() === $class->getRootDocumentName()
			&& (
				$class->getDiscriminatorMap() !== []
				|| $class->getDiscriminatorColumn() !== null
			)
		) {
			// Second condition is necessary for mapped superclasses in the middle of an inheritance hierarchy
			throw new Exceptions\Logic(sprintf(
				'It is not supported to define inheritance information on a mapped superclass "%s"',
				$class->getName(),
			));
		}
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $name
	 *
	 * @return array<class-string<T>>
	 */
	private function getParentClasses(string $name): array
	{
		// Collect parent classes, ignoring transient (not-mapped) classes
		$parentClasses = [];

		/** @var array<class-string<T>>|false $parents */
		$parents = class_parents($name);
		assert($parents !== false);

		foreach (array_reverse($parents) as $parentClass) {
			if ($this->driver->isTransient($parentClass)) {
				continue;
			}

			$parentClasses[] = $parentClass;
		}

		return $parentClasses;
	}

	/**
	 * Removes the prepended backslash of a class string to conform with how php outputs class names
	 *
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $className
	 *
	 * @return class-string<T>
	 */
	private function normalizeClassName(string $className): string
	{
		// @phpstan-ignore-next-line
		return ltrim($className, '\\');
	}

	/**
	 * Gets the lower-case short name of a class
	 *
	 * @param class-string $className
	 */
	private function getShortName(string $className): string
	{
		if (!str_contains($className, '\\')) {
			return strtolower($className);
		}

		$parts = explode('\\', $className);

		return strtolower(end($parts));
	}

	private function getCacheKey(string $realClassName): string
	{
		return str_replace('\\', '__', $realClassName) . $this->cacheSalt;
	}

}
