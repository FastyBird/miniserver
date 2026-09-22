<?php declare(strict_types = 1);

/**
 * DiscriminatorColumn.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Core\Documents\Mapping;

use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use ReflectionClass;
use function array_pop;
use function assert;
use function class_exists;
use function get_object_vars;
use function in_array;
use function interface_exists;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function property_exists;
use function sprintf;
use function str_contains;

/**
 * @template T of Documents\Document
 */
class ClassMetadata
{

	public const int INHERITANCE_TYPE_NONE = 0;

	public const int INHERITANCE_TYPE_SINGLE_TABLE = 1;

	public const int INHERITANCE_TYPE_JOINED_TABLE = 2;

	/** @var class-string<T> */
	private string $name;

	/** @var class-string<T> */
	private string $rootDocumentName;

	private string $namespace;

	/** @var ReflectionClass<T> */
	private ReflectionClass $reflectionClass;

	/** @var class-string<object>|null */
	private string|null $owningEntity = null;

	private bool $isMappedSuperclass = false;

	/** @var array<class-string<T>> */
	private array $subClasses = [];

	/** @var array<class-string<T>> */
	private array $parentClasses = [];

	private int $inheritanceType = self::INHERITANCE_TYPE_NONE;

	/** @var array<string, string>|null */
	private array|null $discriminatorColumn = null;

	/**
	 * READ-ONLY: The discriminator value of this class
	 *
	 * This does only apply to the SINGLE_TABLE or JOINED_TABLE inheritance mapping strategies
	 * where a discriminator column is used.
	 *
	 * @see discriminatorColumn
	 */
	private int|string|null $discriminatorValue = null;

	/** @var array<int|string, class-string<T>> */
	private array $discriminatorMap = [];

	/**
	 * @param class-string<T> $name
	 */
	public function __construct(string $name)
	{
		$this->reflectionClass = new ReflectionClass($name);

		$this->namespace = $this->reflectionClass->getNamespaceName();
		$this->name = $this->rootDocumentName = $this->reflectionClass->getName();
	}

	/**
	 * @return class-string<T>
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return class-string<T>
	 */
	public function getRootDocumentName(): string
	{
		return $this->rootDocumentName;
	}

	/**
	 * @return ReflectionClass<T>
	 */
	public function getReflectionClass(): ReflectionClass
	{
		return $this->reflectionClass;
	}

	/**
	 * Checks if this document is the root in any document-inheritance-hierarchy
	 */
	public function isRootDocument(): bool
	{
		return $this->name === $this->rootDocumentName;
	}

	public function isAbstract(): bool
	{
		return $this->reflectionClass->isAbstract();
	}

	/**
	 * @param class-string<object> $owner
	 */
	public function setOwningEntity(string $owner): void
	{
		$this->owningEntity = $owner;
	}

	/**
	 * @return class-string<object>|null
	 */
	public function getOwningEntity(): string|null
	{
		return $this->owningEntity;
	}

	public function setIsMappedSuperclass(bool $isMappedSuperclass): void
	{
		$this->isMappedSuperclass = $isMappedSuperclass;
	}

	public function isMappedSuperclass(): bool
	{
		return $this->isMappedSuperclass;
	}

	public function setInheritanceType(int $type): void
	{
		$this->inheritanceType = $type;
	}

	public function getInheritanceType(): int
	{
		return $this->inheritanceType;
	}

	public function isInheritanceTypeNone(): bool
	{
		return $this->inheritanceType === self::INHERITANCE_TYPE_NONE;
	}

	/**
	 * Checks whether the mapped class uses the SINGLE_TABLE or JOINED_TABLE inheritance mapping strategy.
	 *
	 * @return bool TRUE if the class participates in a SINGLE_TABLE or JOINED_TABLE inheritance mapping, FALSE otherwise.
	 */
	public function isInheritanceTypeSingleTable(): bool
	{
		return $this->inheritanceType === self::INHERITANCE_TYPE_SINGLE_TABLE || $this->inheritanceType === self::INHERITANCE_TYPE_JOINED_TABLE;
	}

	/**
	 * @param array<string, string>|null $definition
	 */
	public function setDiscriminatorColumn(array|null $definition): void
	{
		$this->discriminatorColumn = $definition;
	}

	/**
	 * @return array<string, string>|null
	 */
	public function getDiscriminatorColumn(): array|null
	{
		return $this->discriminatorColumn === [] ? null : $this->discriminatorColumn;
	}

	/**
	 * @param array<int|string, class-string<T>> $map
	 *
	 * @throws Exceptions\Logic
	 */
	public function setDiscriminatorMap(array $map): void
	{
		foreach ($map as $value => $className) {
			$this->addDiscriminatorMapClass($value, $className);
		}
	}

	/**
	 * @return array<int|string, class-string<T>>
	 */
	public function getDiscriminatorMap(): array
	{
		return $this->discriminatorMap;
	}

	/**
	 * @param class-string<T> $className
	 *
	 * @throws Exceptions\Logic
	 */
	public function addDiscriminatorMapClass(int|string $name, string $className): void
	{
		$className = $this->fullyQualifiedClassName($className);

		$this->discriminatorMap[$name] = $className;

		if ($this->name === $className) {
			$this->discriminatorValue = $name;

			return;
		}

		if (!(class_exists($className) || interface_exists($className))) {
			throw new Exceptions\Logic(sprintf(
				'Document class "%s" used in the discriminator map of class "%s" does not exist.',
				$className,
				$this->name,
			));
		}

		$this->addSubClass($className);
	}

	public function getDiscriminatorValue(): int|string|null
	{
		return $this->discriminatorValue;
	}

	/**
	 * Sets the mapped subclasses of this class
	 *
	 * @param array<class-string<T>> $subclasses
	 */
	public function setSubclasses(array $subclasses): void
	{
		foreach ($subclasses as $subclass) {
			$this->subClasses[] = $this->fullyQualifiedClassName($subclass);
		}
	}

	/**
	 * @return array<class-string<T>>
	 */
	public function getSubClasses(): array
	{
		return $this->subClasses;
	}

	/**
	 * @param array<class-string<T>> $classes
	 */
	public function addSubClasses(array $classes): void
	{
		foreach ($classes as $className) {
			$this->addSubClass($className);
		}
	}

	/**
	 * @param class-string<T> $className
	 */
	public function addSubClass(string $className): void
	{
		// By ignoring classes that are not subclasses of the current class, we simplify inheriting
		// the subclass list from a parent class at the beginning of Documents\DocumentFactory::doLoadMetadata
		if (is_subclass_of($className, $this->name) && !in_array($className, $this->subClasses, true)) {
			$this->subClasses[] = $className;
		}
	}

	/**
	 * @param array<class-string<T>> $classNames
	 */
	public function setParentClasses(array $classNames): void
	{
		$this->parentClasses = $classNames;

		if ($classNames !== []) {
			$className = array_pop($classNames);
			assert(class_exists($className));

			$this->rootDocumentName = $className;
		}
	}

	/**
	 * @return array<class-string<T>>
	 */
	public function getParentClasses(): array
	{
		return $this->parentClasses;
	}

	/**
	 * @template CN of object
	 *
	 * @param class-string<CN> $className
	 *
	 * @return class-string<CN>
	 */
	public function fullyQualifiedClassName(string $className): string
	{
		if (!str_contains($className, '\\')) {
			// @phpstan-ignore-next-line
			return $this->namespace . '\\' . $className;
		}

		// @phpstan-ignore-next-line
		return ltrim($className, '\\');
	}

	public function __serialize(): array
	{
		// Get all properties except $reflectionClass
		$vars = get_object_vars($this);

		// Serialize properties, adding class name separately for reflection
		$vars['__reflection_class_name'] = $this->reflectionClass->getName();

		unset($vars['reflectionClass']);

		return $vars;
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __unserialize(array $data): void
	{
		// Restore properties
		foreach ($data as $key => $value) {
			if ($key === '__reflection_class_name') {
				if (!is_string($value) || !class_exists($value)) {
					throw new Exceptions\InvalidState('Failed to deserialize document ClassMetadata');
				}

				// @phpstan-ignore-next-line
				$this->reflectionClass = new ReflectionClass($value);
			} elseif (property_exists($this, $key)) {
				// @phpstan-ignore-next-line
				$this->{$key} = $value;
			}
		}
	}

}
