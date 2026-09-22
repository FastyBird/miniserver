<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\DoctrineTimestampable\Driver;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Configuration;
use FastyBird\Core\Exceptions;
use Nette;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use function array_reverse;
use function assert;
use function class_parents;
use function hash;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Reads #[Timestampable] attributes off entity properties and builds their timestamping metadata
 */
final class Timestampable
{

	use Nette\SmartObject;

	// Annotation field is timestampable
	private const string EXTENSION_ANNOTATION = 'FastyBird\Core\Mapping\DoctrineTimestampable\Annotation\Timestampable';

	/**
	 * List of cached object configurations
	 *
	 * @var array<mixed>
	 */
	private static array $objectConfigurations = [];

	/**
	 * List of types which are valid for blame
	 *
	 * @var array<string>
	 */
	private array $validTypes = [
		'date',
		'time',
		'datetime',
		'datetimetz',
		'timestamp',
		// DBAL 4 refuses a DateTimeImmutable on the mutable 'datetime' family and routes it to
		// the *_immutable types; the app's clock hands out immutables, so those must validate.
		'datetime_immutable',
		'datetimetz_immutable',
		'date_immutable',
		'time_immutable',
		'vardatetime',
		'integer',
	];

	public function __construct(
		private readonly Configuration\Configuration $configuration,
		private readonly CacheItemPoolInterface|null $cache = null,
	)
	{
	}

	/**
	 * Get the configuration for specific object class
	 * if cache driver is present it scans it also
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws InvalidArgumentException
	 *
	 * @phpstan-param class-string $class
	 */
	public function getObjectConfigurations(Persistence\ObjectManager $objectManager, string $class): array
	{
		$config = [];

		if (isset(self::$objectConfigurations[$class])) {
			$config = self::$objectConfigurations[$class];

		} else {
			$metadataFactory = $objectManager->getMetadataFactory();

			$cacheId = self::getCacheId($class);

			if ($this->cache !== null && ($cached = $this->cache->fetch($cacheId)) !== false) {
				self::$objectConfigurations[$class] = $cached;

				$config = $cached;

			} else {
				/**
				 *
				 *
				 * @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata
				 */
				$classMetadata = $metadataFactory->getMetadataFor($class);
				assert($classMetadata instanceof ORM\Mapping\ClassMetadata);

				// Re-generate metadata on cache miss
				$this->loadMetadataForObjectClass($objectManager, $classMetadata);

				if (isset(self::$objectConfigurations[$class])) {
					$config = self::$objectConfigurations[$class];
				}
			}

			$objectClass = $config['useObjectClass'] ?? $class;

			if ($objectClass !== $class) {
				$this->getObjectConfigurations($objectManager, $objectClass);
			}
		}

		return $config;
	}

	/**
	 * Get the cache id
	 */
	private static function getCacheId(string $className): string
	{
		// PSR-6 reserves {}()/\@: in keys, and a class name plus the old Doctrine\Common\Cache
		// suffix carries both a backslash and a dollar sign. Hash it rather than strip it, so two
		// classes cannot collide once their separators are gone.
		return 'timestampable_' . hash('xxh128', $className);
	}

	/**
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws InvalidArgumentException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	public function loadMetadataForObjectClass(
		Persistence\ObjectManager $objectManager,
		ORM\Mapping\ClassMetadata $classMetadata,
	): void
	{
		if ($classMetadata->isMappedSuperclass) {
			return; // Ignore mappedSuperclasses for now
		}

		// The annotation reader accepts a ReflectionClass, which can be
		// obtained from the $classMetadata
		$reflectionClass = $classMetadata->getReflectionClass();

		$config = [];

		$useObjectName = $classMetadata->getName();

		// Collect metadata from inherited classes
		if (class_parents($classMetadata->getName()) !== false) {
			foreach (array_reverse(class_parents($classMetadata->getName())) as $parentClass) {
				// Read only inherited mapped classes
				if ($objectManager->getMetadataFactory()
					->hasMetadataFor($parentClass)) {
					/**
					 *
					 *
					 * @phpstan-var ORM\Mapping\ClassMetadata<object> $parentClassMetadata
					 */
					$parentClassMetadata = $objectManager->getClassMetadata($parentClass);
					assert($parentClassMetadata instanceof ORM\Mapping\ClassMetadata);

					$config = $this->readExtendedMetadata($parentClassMetadata, $config);

					$isBaseInheritanceLevel = !$parentClassMetadata->isInheritanceTypeNone()
						&& $parentClassMetadata->parentClasses !== []
						&& $config !== [];

					if ($isBaseInheritanceLevel === true) {
						$useObjectName = $reflectionClass->getName();
					}
				}
			}
		}

		$config = $this->readExtendedMetadata($classMetadata, $config);

		if ($config !== []) {
			$config['useObjectClass'] = $useObjectName;
		}

		// Cache the metadata (even if it's empty)
		// Caching empty metadata will prevent re-parsing non-existent annotations
		$cacheId = self::getCacheId($classMetadata->getName());

		if ($this->cache !== null) {
			$this->cache?->save($this->cache->getItem($cacheId)->set($config));
		}

		self::$objectConfigurations[$classMetadata->getName()] = $config;
	}

	/**
	 * @param array<mixed> $config
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function readExtendedMetadata(ORM\Mapping\ClassMetadata $classMetadata, array $config): array
	{
		$class = $classMetadata->getReflectionClass();

		// Property annotations
		foreach ($class->getProperties() as $property) {
			if ($classMetadata->isMappedSuperclass && $property->isPrivate() === false ||
				$classMetadata->isInheritedField($property->getName()) ||
				isset($classMetadata->associationMappings[$property->getName()]['inherited'])
			) {
				continue;
			}

			$timestampable = ($property->getAttributes(self::EXTENSION_ANNOTATION)[0] ?? null)?->newInstance();

			if ($timestampable !== null) {
				$field = $property->getName();

				// No map field nor association
				if (
					$classMetadata->hasField($field) === false
					&& $classMetadata->hasAssociation($field) === false
					&& $this->configuration->useLazyAssociation() === false
				) {
					if ($this->configuration->autoMapField()) {
						$classMetadata->mapField([
							'fieldName' => $field,
							'type' => $this->configuration->dbFieldType,
							'nullable' => true,
						]);

					} else {
						throw new Exceptions\InvalidMapping(
							sprintf(
								'Unable to find timestampable [%s] as mapped property in entity - %s',
								$field,
								$classMetadata->getName(),
							),
						);
					}
				}

				if ($classMetadata->hasField($field)) {
					if (
						!$this->isValidField($classMetadata, $field)
						&& $this->configuration->useLazyAssociation() === false
					) {
						throw new Exceptions\InvalidMapping(
							sprintf(
								'Field - [%s] type is not valid and must be \'string\' or a one-to-many relation in class - %s',
								$field,
								$classMetadata->getName(),
							),
						);
					}
				}

				// Check for valid events
				if (!in_array($timestampable->on, ['update', 'create', 'change', 'delete'], true)) {
					throw new Exceptions\InvalidMapping(
						sprintf(
							'Field - [%s] trigger \'on\' is not one of [update, create, change] in class - %s',
							$field,
							$classMetadata->getName(),
						),
					);
				}

				if ($timestampable->on === 'change') {
					if (!isset($timestampable->field)) {
						throw new Exceptions\InvalidMapping(
							sprintf(
								'Missing parameters on property - %s, field must be set on [change] trigger in class - %s',
								$field,
								$classMetadata->getName(),
							),
						);
					}

					if (is_array($timestampable->field) && isset($timestampable->value)) {
						throw new Exceptions\InvalidMapping(
							'Timestampable extension does not support multiple value changeset detection yet.',
						);
					}

					$field = [
						'field' => $field,
						'trackedField' => $timestampable->field,
						'value' => is_array(
							$timestampable->value,
						) ? $timestampable->value : [$timestampable->value],
					];
				}

				// properties are unique and mapper checks that, no risk here
				$config[$timestampable->on][] = $field;
			}
		}

		return $config;
	}

	/**
	 * Checks if $field type is valid
	 *
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function isValidField(ORM\Mapping\ClassMetadata $classMetadata, string $field): bool
	{
		$mapping = $classMetadata->getFieldMapping($field);

		return in_array($mapping['type'], $this->validTypes, true);
	}

}
