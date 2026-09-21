<?php declare(strict_types = 1);

/**
 * AttributeDriver.php
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

namespace FastyBird\Core\Documents\Mapping\Driver;

use Error;
use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RegexIterator;
use UnexpectedValueException;
use function array_merge;
use function array_unique;
use function assert;
use function constant;
use function get_declared_classes;
use function in_array;
use function is_array;
use function is_dir;
use function is_int;
use function is_string;
use function preg_match;
use function preg_quote;
use function realpath;
use function sprintf;
use function str_contains;
use function str_replace;

/**
 * Document mapping attribute driver
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AttributeDriver implements MappingDriver
{

	private const DOCUMENT_ATTRIBUTE_CLASSES = [
		Documents\Mapping\Document::class => 1,
		Documents\Mapping\MappedSuperclass::class => 2,
	];

	/** @var array<int, class-string> */
	private array|null $classNames = null;

	/** @var array<int, string> */
	private array $paths = [];

	/** @var array<int, string> */
	private array $excludePaths = [];

	private string $fileExtension = '.php';

	/** @var AttributeReader<Documents\Mapping\MappingAttribute> */
	private AttributeReader $reader;

	/**
	 * @param array<string> $paths
	 */
	public function __construct(array $paths = [])
	{
		$this->reader = new AttributeReader();

		$this->addPaths($paths);
	}

	/**
	 * @param array<string> $paths
	 */
	public function addPaths(array $paths): void
	{
		$this->paths = array_unique(array_merge($this->paths, $paths));
	}

	/**
	 * Retrieves the defined metadata lookup paths.
	 *
	 * @return array<int, string>
	 */
	public function getPaths(): array
	{
		return $this->paths;
	}

	/**
	 * Append exclude lookup paths to metadata driver.
	 *
	 * @param array<string> $paths
	 */
	public function addExcludePaths(array $paths): void
	{
		$this->excludePaths = array_unique(array_merge($this->excludePaths, $paths));
	}

	/**
	 * Retrieve the defined metadata lookup exclude paths.
	 *
	 * @return array<int, string>
	 */
	public function getExcludePaths(): array
	{
		return $this->excludePaths;
	}

	/**
	 * Gets the file extension used to look for mapping files under.
	 */
	public function getFileExtension(): string
	{
		return $this->fileExtension;
	}

	/**
	 * Sets the file extension used to look for mapping files under.
	 */
	public function setFileExtension(string $fileExtension): void
	{
		$this->fileExtension = $fileExtension;
	}

	/**
	 * Gets the names of all mapped classes known to this driver.
	 *
	 * @return array<int, class-string<object>>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getAllClassNames(): array
	{
		if ($this->classNames !== null) {
			return $this->classNames;
		}

		if ($this->paths === []) {
			throw new Exceptions\InvalidState(
				'Documents attribute driver have to have configured paths defining documents location',
			);
		}

		$classes = [];
		$includedFiles = [];

		foreach ($this->paths as $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Provided path: "%s" is not valid directory', $path));
			}

			try {
				$iterator = new RegexIterator(
					new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
						RecursiveIteratorIterator::LEAVES_ONLY,
					),
					'/^.+' . preg_quote($this->fileExtension) . '$/i',
					RegexIterator::GET_MATCH,
				);
			} catch (UnexpectedValueException $ex) {
				throw new Exceptions\InvalidState('An unexpected error occurred', $ex->getCode(), $ex);
			}

			foreach ($iterator as $file) {
				assert(is_array($file) && $file !== []);

				$sourceFile = $file[0];

				assert(is_string($sourceFile));

				if (preg_match('(^phar:)i', $sourceFile) === 0) {
					$sourceFile = realpath($sourceFile);

					if ($sourceFile === false) {
						continue;
					}
				}

				foreach ($this->excludePaths as $excludePath) {
					$realExcludePath = realpath($excludePath);

					assert($realExcludePath !== false);

					$exclude = str_replace('\\', '/', $realExcludePath);
					$current = str_replace('\\', '/', $sourceFile);

					if (str_contains($current, $exclude)) {
						continue 2;
					}
				}

				require_once $sourceFile;

				$includedFiles[] = $sourceFile;
			}
		}

		$declared = get_declared_classes();

		foreach ($declared as $className) {
			$rc = new ReflectionClass($className);

			$sourceFile = $rc->getFileName();

			if (
				!in_array($sourceFile, $includedFiles, true)
				|| $this->isTransient($className)
			) {
				continue;
			}

			$classes[] = $className;
		}

		$this->classNames = $classes;

		return $classes;
	}

	/**
	 * Returns whether the class with the specified name should have its metadata loaded.
	 * This is only the case if it is either mapped as a Document or a MappedSuperclass.
	 *
	 * @param class-string<object> $className
	 */
	public function isTransient(string $className): bool
	{
		$classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

		foreach ($classAttributes as $attr) {
			if (isset(self::DOCUMENT_ATTRIBUTE_CLASSES[$attr::class])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Documents\Mapping\ClassMetadata<T> $metadata
	 *
	 * @throws Exceptions\Logic
	 */
	public function loadMetadataForClass(Documents\Mapping\ClassMetadata $metadata): void
	{
		$reflectionClass = $metadata->getReflectionClass();

		$classAttributes = $this->reader->getClassAttributes($reflectionClass);

		// Evaluate Document attribute
		if (isset($classAttributes[Documents\Mapping\Document::class])) {
			$documentAttribute = $classAttributes[Documents\Mapping\Document::class];
			assert($documentAttribute instanceof Documents\Mapping\Document);

			if ($documentAttribute->entity !== null) {
				$metadata->setOwningEntity($documentAttribute->entity);
			}
		} elseif (isset($classAttributes[Documents\Mapping\MappedSuperclass::class])) {
			$metadata->setIsMappedSuperclass(true);

		} else {
			throw new Exceptions\Logic(
				sprintf('Provided document class: "%s" has not specified required attributes', $metadata->getName()),
			);
		}

		// Evaluate InheritanceType attribute
		if (isset($classAttributes[Documents\Mapping\InheritanceType::class])) {
			$inheritanceTypeAttribute = $classAttributes[Documents\Mapping\InheritanceType::class];
			assert($inheritanceTypeAttribute instanceof Documents\Mapping\InheritanceType);

			try {
				$inheritanceType = constant(
					'FastyBird\Core\Documents\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAttribute->type,
				);
				assert(is_int($inheritanceType));
			} catch (Error $ex) {
				throw new Exceptions\Logic(
					sprintf(
						'Document inheritance type: "%s" for class: "%s" is not valid',
						$inheritanceTypeAttribute->type,
						$metadata->getName(),
					),
					$ex->getCode(),
					$ex,
				);
			}

			$metadata->setInheritanceType($inheritanceType);

			if ($metadata->getInheritanceType() !== Documents\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
				// Evaluate DiscriminatorColumn attribute
				if (isset($classAttributes[Documents\Mapping\DiscriminatorColumn::class])) {
					$discriminatorColumnAttribute = $classAttributes[Documents\Mapping\DiscriminatorColumn::class];
					assert($discriminatorColumnAttribute instanceof Documents\Mapping\DiscriminatorColumn);

					$columnDef = [
						'name' => $discriminatorColumnAttribute->name,
						'type' => $discriminatorColumnAttribute->type ?? 'string',
					];

					$metadata->setDiscriminatorColumn($columnDef);
				} else {
					$metadata->setDiscriminatorColumn(['name' => 'dtype', 'type' => 'string']);
				}

				// Evaluate DiscriminatorMap attribute
				if (isset($classAttributes[Documents\Mapping\DiscriminatorMap::class])) {
					/** @var Documents\Mapping\DiscriminatorMap<T> $discriminatorMapAttribute */
					$discriminatorMapAttribute = $classAttributes[Documents\Mapping\DiscriminatorMap::class];

					$metadata->setDiscriminatorMap($discriminatorMapAttribute->value);
				}
			}
		}

		if (isset($classAttributes[Documents\Mapping\DiscriminatorEntry::class])) {
			$discriminatorEntryAttribute = $classAttributes[Documents\Mapping\DiscriminatorEntry::class];
			assert($discriminatorEntryAttribute instanceof Documents\Mapping\DiscriminatorEntry);

			$metadata->addDiscriminatorMapClass($discriminatorEntryAttribute->name, $metadata->getName());
		}
	}

}
