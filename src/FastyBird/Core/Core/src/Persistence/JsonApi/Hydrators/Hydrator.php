<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators;

use ArrayAccess;
use BackedEnum;
use DateTimeInterface;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Helpers\JsonApi as Helpers;
use FastyBird\Core\Persistence\JsonApi\Hydrators;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Nette\Utils;
use phpDocumentor;
use Ramsey\Uuid;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function assert;
use function call_user_func;
use function class_exists;
use function count;
use function explode;
use function gettype;
use function in_array;
use function interface_exists;
use function is_array;
use function is_callable;
use function is_numeric;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function strval;
use function trim;
use function ucfirst;
use function ucwords;

/**
 * Entity hydrator
 *
 * @template T of object
 */
abstract class Hydrator
{

	protected const string IDENTIFIER_KEY = 'id';

	/**
	 * Whether the resource has a client generated id
	 */
	protected string|null $entityIdentifier = null;

	/**
	 * The resource attribute keys to hydrate
	 *
	 * For example:
	 *
	 * ```
	 * $attributes = [
	 *  'foo',
	 *  'bar' => 'baz'
	 * ];
	 * ```
	 *
	 * Will transfer the `foo` resource attribute to the model `foo` attribute, and the
	 * resource `bar` attribute to the model `baz` attribute.
	 *
	 * @var array<int|string, string>
	 */
	protected array $attributes = [];

	/**
	 * The resource composited attribute keys to hydrate
	 *
	 * For example:
	 *
	 * ```
	 * $attributes = [
	 *  'params',
	 *  'bar' => 'baz'
	 * ];
	 * ```
	 *
	 * Will transfer the `foo` resource attribute to the model `foo` attribute, and the
	 * resource `bar` attribute to the model `baz` attribute.
	 *
	 * @var array<int|string, string>
	 */
	protected array $compositedAttributes = [];

	/**
	 * Resource relationship keys that should be automatically hydrated
	 *
	 * @var array<string>
	 */
	protected array $relationships = [];

	/** @var array<string, string>|null */
	private array|null $normalizedAttributes = null;

	/** @var array<string, string>|null */
	private array|null $normalizedCompositedAttributes = null;

	/** @var array<string, string>|null */
	private array|null $normalizedRelationships = null;

	private JsonApiExceptions\JsonApiMultipleError $errors;

	public function __construct(
		protected readonly Persistence\ManagerRegistry $managerRegistry,
		protected readonly Localization\Translator $translator,
		protected readonly Helpers\CrudReader|null $crudReader = null,
	)
	{
		$this->errors = new JsonApiExceptions\JsonApiMultipleError();
	}

	/**
	 * @param T|null $entity
	 *
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Throwable
	 */
	public function hydrate(
		JsonApi\IDocument $document,
		object|null $entity = null,
		bool $includeRelationShips = true,
	): Utils\ArrayHash
	{
		$entityMapping = $this->mapEntity($this->getEntityName());

		if (!$document->hasResource()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//jsonApi.hydrator.resourceInvalid.heading')),
				strval($this->translator->translate('//jsonApi.hydrator.resourceInvalid.message')),
				[
					'pointer' => '/data',
				],
			);
		}

		$resource = $document->getResource();

		$attributes = $this->hydrateAttributes(
			$this->getEntityName(),
			$resource->getAttributes(),
			$entityMapping,
			$entity,
			null,
		);

		$relationships = [];

		if ($includeRelationShips) {
			$relationships = $this->hydrateRelationships(
				$resource->getRelationships(),
				$entityMapping,
				$document->hasIncluded() ? $document->getIncluded() : null,
				$entity,
			);
		}

		if ($this->errors->hasErrors()) {
			throw $this->errors;
		}

		$result = Utils\ArrayHash::from(array_merge(
			[
				'entity' => $this->getEntityName(),
			],
			$attributes,
			$relationships,
		));

		if ($entity === null) {
			$identifierKey = $this->entityIdentifier ?? self::IDENTIFIER_KEY;

			try {
				$identifier = $resource->getId();

				if ($identifier === null || !Uuid\Uuid::isValid($identifier)) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//jsonApi.hydrator.identifierInvalid.heading')),
						strval($this->translator->translate('//jsonApi.hydrator.identifierInvalid.message')),
						[
							'pointer' => '/data/id',
						],
					);
				}

				$result[$identifierKey] = Uuid\Uuid::fromString($identifier);

			} catch (Exceptions\Runtime) {
				$result[$identifierKey] = Uuid\Uuid::uuid4();
			}
		}

		return $result;
	}

	/**
	 * @return class-string<T>
	 */
	abstract public function getEntityName(): string;

	/**
	 * @param class-string $entityClassName
	 *
	 * @return array<Hydrators\Fields\Field>
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function mapEntity(string $entityClassName): array
	{
		$entityManager = $this->managerRegistry->getManagerForClass($entityClassName);

		if ($entityManager === null) {
			return [];
		}

		$classMetadata = $entityManager->getClassMetadata($entityClassName);

		$reflectionProperties = [];

		try {
			if (class_exists($entityClassName)) {
				$rc = new ReflectionClass($entityClassName);

			} else {
				throw new Exceptions\InvalidState('Entity could not be parsed');
			}
		} catch (ReflectionException) {
			throw new Exceptions\InvalidState('Entity could not be parsed');
		}

		foreach ($rc->getProperties() as $rp) {
			$reflectionProperties[] = $rp->getName();
		}

		$constructorRequiredParameters = array_map(
			static fn (ReflectionParameter $parameter): string => $parameter->getName(),
			array_filter(
				$rc->getConstructor()?->getParameters() ?? [],
				static fn (ReflectionParameter $parameter): bool => !$parameter->isOptional(),
			),
		);

		$constructorOptionalParameters = array_map(
			static fn (ReflectionParameter $parameter): string => $parameter->getName(),
			array_filter(
				$rc->getConstructor()?->getParameters() ?? [],
				static fn (ReflectionParameter $parameter): bool => $parameter->isOptional(),
			),
		);

		$entityFields = array_unique(array_merge(
			$reflectionProperties,
			$classMetadata->getFieldNames(),
			$classMetadata->getAssociationNames(),
		));

		$fields = [];

		foreach ($entityFields as $fieldName) {
			try {
				// Check if property in entity class exists
				$rp = $rc->getProperty($fieldName);

			} catch (ReflectionException) {
				continue;
			}

			if (
				in_array($fieldName, $constructorRequiredParameters, true)
				|| in_array($fieldName, $constructorOptionalParameters, true)
			) {
				[$isRequired, $isWritable] = [
					in_array($fieldName, $constructorRequiredParameters, true),
					in_array($fieldName, $constructorOptionalParameters, true),
				];
			} else {
				if ($this->crudReader !== null) {
					[$isRequired, $isWritable] = $this->crudReader->read($rp) + [false, false];

				} else {
					$isRequired = false;
					$isWritable = true;
				}
			}

			// Check if field is updatable
			if (!$isRequired && !$isWritable) {
				continue;
			}

			$isRelationship = false;

			if ($this->getRelationshipKey($fieldName) !== null) {
				// Transform entity field name to schema relationship name
				$mappedKey = $this->getRelationshipKey($fieldName);

				$isRelationship = true;

			} elseif ($this->getAttributeKey($fieldName) !== null) {
				$mappedKey = $this->getAttributeKey($fieldName);

			} elseif ($this->getCompositedAttributeKey($fieldName) !== null) {
				$mappedKey = $this->getCompositedAttributeKey($fieldName);

			} else {
				continue;
			}

			// Extract all entity property annotations
			$propertyAttributes = array_map(
				(static fn ($attribute): string => $attribute->getName()),
				$rp->getAttributes(),
			);

			if (in_array(ORM\Mapping\OneToOne::class, $propertyAttributes, true)) {
				$propertyAttribute = array_reduce(
					$rp->getAttributes(),
					static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
						if ($carry === null && $attribute->getName() === ORM\Mapping\OneToOne::class) {
							return $attribute;
						}

						return $carry;
					},
				);
				assert($propertyAttribute instanceof ReflectionAttribute);

				$propertyAttribute = $propertyAttribute->newInstance();
				assert($propertyAttribute instanceof ORM\Mapping\OneToOne);

				$className = $propertyAttribute->targetEntity;

				// Check if class is callable
				if (is_string($className) && class_exists($className)) {
					$fields[] = new Hydrators\Fields\SingleEntityField(
						$className,
						false,
						$mappedKey,
						$isRelationship,
						$fieldName,
						$isRequired,
						$isWritable,
					);
				}
			} elseif (in_array(ORM\Mapping\OneToMany::class, $propertyAttributes, true)) {
				$propertyAttribute = array_reduce(
					$rp->getAttributes(),
					static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
						if ($carry === null && $attribute->getName() === ORM\Mapping\OneToMany::class) {
							return $attribute;
						}

						return $carry;
					},
				);
				assert($propertyAttribute instanceof ReflectionAttribute);

				$propertyAttribute = $propertyAttribute->newInstance();
				assert($propertyAttribute instanceof ORM\Mapping\OneToMany);

				$className = $propertyAttribute->targetEntity;

				// Check if class is callable
				if (is_string($className) && class_exists($className)) {
					$fields[] = new Hydrators\Fields\CollectionField(
						$className,
						true,
						$mappedKey,
						$isRelationship,
						$fieldName,
						$isRequired,
						$isWritable,
					);
				}
			} elseif (in_array(ORM\Mapping\ManyToMany::class, $propertyAttributes, true)) {
				$propertyAttribute = array_reduce(
					$rp->getAttributes(),
					static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
						if ($carry === null && $attribute->getName() === ORM\Mapping\ManyToMany::class) {
							return $attribute;
						}

						return $carry;
					},
				);
				assert($propertyAttribute instanceof ReflectionAttribute);

				$propertyAttribute = $propertyAttribute->newInstance();
				assert($propertyAttribute instanceof ORM\Mapping\ManyToMany);

				$className = $propertyAttribute->targetEntity;

				// Check if class is callable
				if ($className !== null && class_exists($className)) {
					$fields[] = new Hydrators\Fields\CollectionField(
						$className,
						true,
						$mappedKey,
						$isRelationship,
						$fieldName,
						$isRequired,
						$isWritable,
					);
				}
			} elseif (in_array(ORM\Mapping\ManyToOne::class, $propertyAttributes, true)) {
				$propertyAttribute = array_reduce(
					$rp->getAttributes(),
					static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
						if ($carry === null && $attribute->getName() === ORM\Mapping\ManyToOne::class) {
							return $attribute;
						}

						return $carry;
					},
				);
				assert($propertyAttribute instanceof ReflectionAttribute);

				$propertyAttribute = $propertyAttribute->newInstance();
				assert($propertyAttribute instanceof ORM\Mapping\ManyToOne);

				$className = $propertyAttribute->targetEntity;

				// Check if class is callable
				if (is_string($className) && class_exists($className)) {
					$fields[] = new Hydrators\Fields\SingleEntityField(
						$className,
						false,
						$mappedKey,
						$isRelationship,
						$fieldName,
						$isRequired,
						$isWritable,
					);
				}
			} else {
				$varAnnotation = $this->parseAnnotation($rp, 'var');

				try {
					$propertyType = $rp->getType();

					if ($propertyType instanceof ReflectionNamedType) {
						$varAnnotation = ($varAnnotation === null ? '' : $varAnnotation . '|')
							. $propertyType->getName() . ($propertyType->allowsNull() ? '|null' : '');
					}

					$rm = $rc->getMethod('get' . ucfirst($fieldName));

					$returnType = $rm->getReturnType();

					if ($returnType instanceof ReflectionNamedType) {
						$varAnnotation = ($varAnnotation === null ? '' : $varAnnotation . '|')
							. $returnType->getName() . ($returnType->allowsNull() ? '|null' : '');
					}
				} catch (ReflectionException) {
					// Nothing to do
				}

				if ($varAnnotation === null) {
					continue;
				}

				$className = null;

				$isString = false;
				$isNumber = false;
				$isDecimal = false;
				$isArray = false;
				$isBool = false;
				$isClass = false;
				$isMixed = false;

				$isNullable = false;

				$typesFound = 0;

				if (str_contains($varAnnotation, '|')) {
					$varDatatypes = explode('|', $varAnnotation);
					$varDatatypes = array_unique($varDatatypes);

				} else {
					$varDatatypes = [$varAnnotation];
				}

				foreach ($varDatatypes as $varDatatype) {
					if (class_exists($varDatatype) || interface_exists($varDatatype)) {
						$className = $varDatatype;
						$isClass = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'string') {
						$isString = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'int') {
						$isNumber = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'float') {
						$isDecimal = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'array' || strtolower($varDatatype) === 'mixed[]') {
						$isArray = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'bool') {
						$isBool = true;

						$typesFound++;

					} elseif (strtolower($varDatatype) === 'null') {
						$isNullable = true;

					} elseif (strtolower($varDatatype) === 'mixed') {
						$isMixed = true;

						$typesFound++;
					}
				}

				if ($typesFound > 0) {
					if ($typesFound > 1) {
						$fields[] = new Hydrators\Fields\MixedField(
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);

					} elseif ($isClass && $className !== null) {
						try {
							$typeRc = new ReflectionClass($className);

							if (
								$typeRc->implementsInterface(
									DateTimeInterface::class,
								)
								|| $className === DateTimeInterface::class
							) {
								$fields[] = new Hydrators\Fields\DateTimeField(
									$isNullable,
									$mappedKey,
									$fieldName,
									$isRequired,
									$isWritable,
								);

							} elseif ($typeRc->isSubclassOf(BackedEnum::class)) {
								$fields[] = new Hydrators\Fields\BackedEnumField(
									$this->translator,
									$className,
									$isNullable,
									$mappedKey,
									$fieldName,
									$isRequired,
									$isWritable,
								);

							} elseif ($typeRc->implementsInterface(ArrayAccess::class)) {
								$fields[] = new Hydrators\Fields\ArrayField(
									$this->translator,
									$isNullable,
									$mappedKey,
									$fieldName,
									$isRequired,
									$isWritable,
								);

							} else {
								$fields[] = new Hydrators\Fields\SingleEntityField(
									$className,
									$isNullable,
									$mappedKey,
									$isRelationship,
									$fieldName,
									$isRequired,
									$isWritable,
								);
							}
						} catch (ReflectionException) {
							$fields[] = new Hydrators\Fields\SingleEntityField(
								$className,
								$isNullable,
								$mappedKey,
								$isRelationship,
								$fieldName,
								$isRequired,
								$isWritable,
							);
						}
					} elseif ($isString) {
						$fields[] = new Hydrators\Fields\TextField(
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);

					} elseif ($isNumber || $isDecimal) {
						$fields[] = new Hydrators\Fields\NumberField(
							$this->translator,
							$isDecimal,
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);

					} elseif ($isArray) {
						$fields[] = new Hydrators\Fields\ArrayField(
							$this->translator,
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);

					} elseif ($isBool) {
						$fields[] = new Hydrators\Fields\BooleanField(
							$this->translator,
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);

					} elseif ($isMixed) {
						$fields[] = new Hydrators\Fields\MixedField(
							$isNullable,
							$mappedKey,
							$fieldName,
							$isRequired,
							$isWritable,
						);
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Get the model method name for a resource relationship key
	 */
	private function getRelationshipKey(string $entityKey): string|null
	{
		$this->normalizeRelationships();

		$key = $this->normalizedRelationships[$entityKey] ?? null;

		return is_string($key) ? $key : null;
	}

	private function normalizeRelationships(): void
	{
		if (is_array($this->normalizedRelationships)) {
			return;
		}

		$this->normalizedRelationships = [];

		if ($this->relationships !== []) {
			foreach ($this->relationships as $resourceKey => $entityKey) {
				if (is_numeric($resourceKey)) {
					$resourceKey = $entityKey;
				}

				$this->normalizedRelationships[$entityKey] = $resourceKey;
			}
		}
	}

	private function getAttributeKey(string $entityKey): string|null
	{
		$this->normalizeAttributes();

		$key = $this->normalizedAttributes[$entityKey] ?? null;

		return is_string($key) ? $key : null;
	}

	private function normalizeAttributes(): void
	{
		if (is_array($this->normalizedAttributes)) {
			return;
		}

		$this->normalizedAttributes = [];

		if ($this->attributes !== []) {
			foreach ($this->attributes as $resourceKey => $entityKey) {
				if (is_numeric($resourceKey)) {
					$resourceKey = $entityKey;
				}

				$this->normalizedAttributes[$entityKey] = $resourceKey;
			}
		}
	}

	private function getCompositedAttributeKey(string $entityKey): string|null
	{
		$this->normalizeCompositeAttributes();

		$key = $this->normalizedCompositedAttributes[$entityKey] ?? null;

		return is_string($key) ? $key : null;
	}

	private function normalizeCompositeAttributes(): void
	{
		if (is_array($this->normalizedCompositedAttributes)) {
			return;
		}

		$this->normalizedCompositedAttributes = [];

		if ($this->compositedAttributes !== []) {
			foreach ($this->compositedAttributes as $resourceKey => $entityKey) {
				if (is_numeric($resourceKey)) {
					$resourceKey = $entityKey;
				}

				$this->normalizedCompositedAttributes[$entityKey] = $resourceKey;
			}
		}
	}

	private function parseAnnotation(ReflectionProperty $rp, string $name): string|null
	{
		if ($rp->getDocComment() === false) {
			return null;
		}

		$factory = phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docblock = $factory->create($rp->getDocComment());

		foreach ($docblock->getTags() as $tag) {
			if ($tag->getName() === $name) {
				return trim((string) $tag);
			}
		}

		if ($name === 'var' && $rp->getType() instanceof ReflectionNamedType) {
			return $rp->getType()->getName();
		}

		return null;
	}

	/**
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 * @param array<Hydrators\Fields\Field> $entityMapping
	 * @param T|null $entity
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateAttributes(
		string $className,
		JsonApi\Objects\IStandardObject $attributes,
		array $entityMapping,
		object|null $entity,
		string|null $rootField,
	): array
	{
		$data = [];

		$isNew = $entity === null;

		foreach ($entityMapping as $field) {
			if ($field instanceof Hydrators\Fields\EntityField && $field->isRelationship()) {
				continue;
			}

			// Continue only if attribute is present
			if (
				!$attributes->has($field->getMappedName())
				&& !in_array($field->getFieldName(), $this->compositedAttributes, true)
			) {
				continue;
			}

			// If there is a specific method for this attribute, we'll hydrate that
			$value = $this->hasCustomHydrateAttribute($field->getFieldName(), $attributes)
				? $this->callHydrateAttribute($field->getFieldName(), $attributes, $entity)
				: $field->getValue($attributes);

			if ($value === null && $field->isRequired() && $isNew) {
				$this->errors->addError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//jsonApi.hydrator.missingRequiredAttribute.heading')),
					strval($this->translator->translate('//jsonApi.hydrator.missingRequiredAttribute.message')),
					[
						'pointer' => '/data/attributes/' . $field->getMappedName(),
					],
				);

			} elseif ($field->isWritable() || ($isNew && $field->isRequired())) {
				if ($field instanceof Hydrators\Fields\SingleEntityField) {
					// Get attribute entity class name
					$fieldClassName = $field->getClassName();

					/**
					 * @var string|int|float|bool|array<mixed>|JsonApi\Objects\IStandardObject|null $fieldAttributes
					 *
					 * @phpstan-var string|int|float|bool|array<mixed>|JsonApi\Objects\IStandardObject<string, string|int|float|bool|array<mixed>|null>|null $fieldAttributes
					 */
					$fieldAttributes = $attributes->get($field->getMappedName());

					if ($fieldAttributes instanceof JsonApi\Objects\IStandardObject) {
						$data[$field->getFieldName()] = $this->hydrateAttributes(
							$fieldClassName,
							$fieldAttributes,
							$this->mapEntity($fieldClassName),
							$entity,
							$field->getMappedName(),
						);

						if (
							isset($data[$field->getFieldName()])
							&& is_array($data[$field->getFieldName()])
							&& !isset($data[$field->getFieldName()]['entity'])
						) {
							$data[$field->getFieldName()]['entity'] = $fieldClassName;
						}
					} elseif ($value !== null || $field->isNullable()) {
						$data[$field->getFieldName()] = $value;
					}
				} else {
					$data[$field->getFieldName()] = $value;
				}
			}
		}

		try {
			if (class_exists($className)) {
				$rc = new ReflectionClass($className);

				if ($rc->getConstructor() !== null) {
					$constructor = $rc->getConstructor();

					foreach ($constructor->getParameters() as $num => $parameter) {
						if (
							!$parameter->isVariadic()
							&& $attributes->has($this->getAttributeKey($parameter->getName()) ?? $parameter->getName())
						) {
							if (array_key_exists($parameter->getName(), $data)) {
								continue;
							}

							// If there is a specific method for this attribute, we'll hydrate that
							$value = $this->hasCustomHydrateAttribute(
								$parameter->getName(),
								$attributes,
							) ? $this->callHydrateAttribute(
								$parameter->getName(),
								$attributes,
								$entity,
							) : $attributes->get(
								$this->getAttributeKey($parameter->getName()) ?? $parameter->getName(),
							);

							$data[$parameter->getName()] = $value;

						} elseif ($attributes->has($this->getAttributeKey((string) $num) ?? (string) $num)) {
							if (array_key_exists((string) $num, $data)) {
								continue;
							}

							// If there is a specific method for this attribute, we'll hydrate that
							$value = $this->hasCustomHydrateAttribute(
								(string) $num,
								$attributes,
							) ? $this->callHydrateAttribute(
								(string) $num,
								$attributes,
								$entity,
							) : $attributes->get(
								$this->getAttributeKey((string) $num) ?? (string) $num,
							);

							$data[(string) $num] = $value;
						}
					}
				}

				$data['entity'] = $className;
			}
		} catch (Throwable) {
			// Nothing to do here
		}

		if (!$isNew) {
			foreach ($data as $attribute => $value) {
				$isAllowed = false;

				foreach ($entityMapping as $field) {
					if ($field instanceof Hydrators\Fields\EntityField && $field->isRelationship()) {
						$isAllowed = true;

						continue;
					}

					if ($field->getFieldName() === $attribute) {
						$isAllowed = true;
					}
				}

				if (!$isAllowed) {
					unset($data[$attribute]);
				}
			}
		}

		return $data;
	}

	/**
	 * Check if hydrator has custom attribute hydration method
	 *
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 */
	private function hasCustomHydrateAttribute(
		string $attributeKey,
		JsonApi\Objects\IStandardObject $attributes,
	): bool
	{
		$method = $this->methodForAttribute($attributeKey);

		if ($method === '' || !method_exists($this, $method)) {
			return false;
		}

		$callable = [$this, $method];

		return is_callable($callable);
	}

	/**
	 * Return the method name to call for hydrating the specific attribute.
	 *
	 * If this method returns an empty value, or a value that is not callable, hydration
	 * of the relationship will be skipped
	 */
	private function methodForAttribute(string $key): string
	{
		return sprintf('hydrate%sAttribute', $this->classify($key));
	}

	/**
	 * Gets the upper camel case form of a string.
	 */
	private function classify(string $value): string
	{
		$converted = ucwords(str_replace(['-', '_'], ' ', $value));

		return str_replace(' ', '', $converted);
	}

	/**
	 * Hydrate a attribute by invoking a method on this hydrator.
	 *
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 * @param T|null $entity
	 */
	private function callHydrateAttribute(
		string $attributeKey,
		JsonApi\Objects\IStandardObject $attributes,
		object|null $entity = null,
	): mixed
	{
		$method = $this->methodForAttribute($attributeKey);

		if ($method === '' || !method_exists($this, $method)) {
			return null;
		}

		$callable = [$this, $method];

		if (is_callable($callable)) {
			return call_user_func($callable, $attributes, $entity);
		}

		return null;
	}

	/**
	 * @param array<Hydrators\Fields\Field> $entityMapping
	 * @param JsonApi\Objects\IResourceObjectCollection<JsonApi\Objects\IResourceObject>|null $included
	 * @param T|null $entity
	 *
	 * @return  array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function hydrateRelationships(
		JsonApi\Objects\IRelationshipObjectCollection $relationships,
		array $entityMapping,
		JsonApi\Objects\IResourceObjectCollection|null $included = null,
		object|null $entity = null,
	): array
	{
		$data = [];

		foreach ($entityMapping as $field) {
			if ($field instanceof Hydrators\Fields\EntityField && $field->isRelationship()) {
				if ($relationships->has($field->getMappedName())) {
					$relationship = $relationships->get($field->getMappedName());

					// If there is a specific method for this relationship, we'll hydrate that
					$result = $this->callHydrateRelationship(
						$field->getMappedName(),
						$relationship,
						$included,
						$entity,
					);

					if ($result !== null) {
						$data[$field->getFieldName()] = $result;

						continue;
					}

					// If this is a has-one, we'll hydrate it
					if ($relationship->isHasOne()) {
						$relationshipEntity = $this->hydrateHasOne(
							$field,
							$relationship,
							$entity,
							$entityMapping,
						);

						$data[$field->getFieldName()] = $relationshipEntity;

					} elseif ($relationship->isHasMany()) {
						$relationshipEntities = $this->hydrateHasMany(
							$field,
							$relationship,
							$entity,
							$entityMapping,
						);

						$data[$field->getFieldName()] = $relationshipEntities;
					}
				} elseif ($field->isRequired() && $entity === null) {
					$this->errors->addError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.heading')),
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.message')),
						[
							'pointer' => '/data/relationships/' . $field->getMappedName() . '/data/id',
						],
					);
				}
			}
		}

		return $data;
	}

	/**
	 * Hydrate a relationship by invoking a method on this hydrator.
	 *
	 * @param JsonApi\Objects\IResourceObjectCollection<JsonApi\Objects\IResourceObject>|null $included
	 * @param T|null $entity
	 *
	 * @return  array<mixed>|object|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function callHydrateRelationship(
		string $relationshipKey,
		JsonApi\Objects\IRelationshipObject $relationship,
		JsonApi\Objects\IResourceObjectCollection|null $included = null,
		object|null $entity = null,
	): array|object|null
	{
		$method = $this->methodForRelationship($relationshipKey);

		if ($method === '' || !method_exists($this, $method)) {
			return null;
		}

		$callable = [$this, $method];

		if (is_callable($callable)) {
			$result = call_user_func($callable, $relationship, $included, $entity);

			if ($result === null || is_array($result) || is_object($result)) {
				return $result;
			}

			throw new Exceptions\InvalidState(
				sprintf('Relationship have to be an array or entity instance, %s provided.', gettype($result)),
			);
		}

		return null;
	}

	/**
	 * Return the method name to call for hydrating the specific relationship.
	 *
	 * If this method returns an empty value, or a value that is not callable, hydration
	 * of the the relationship will be skipped
	 */
	private function methodForRelationship(string $key): string
	{
		return sprintf('hydrate%sRelationship', $this->classify($key));
	}

	/**
	 * Hydrate a resource has-one relationship
	 *
	 * @param T|null $entity
	 * @param array<Hydrators\Fields\Field> $entityMapping
	 */
	protected function hydrateHasOne(
		Hydrators\Fields\Field $field,
		JsonApi\Objects\IRelationshipObject $relationship,
		object|null $entity,
		array $entityMapping,
	): object|null
	{
		// Find relationship field
		if (
			$field instanceof Hydrators\Fields\EntityField
			&& $field->isRelationship()
		) {
			if ($field->isWritable() || ($entity === null && $field->isRequired())) {
				if ($relationship->hasIdentifier()) {
					$relationEntity = $this->findRelated($field->getClassName(), $relationship->getIdentifier());

					if ($relationEntity !== null) {
						return $relationEntity;
					} elseif ($entity === null && $field->isRequired()) {
						$this->errors->addError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.heading')),
							strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.message')),
							[
								'pointer' => '/data/relationships/' . $field->getMappedName() . '/data/id',
							],
						);
					}
				} elseif ($entity === null && $field->isRequired()) {
					$this->errors->addError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.heading')),
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.message')),
						[
							'pointer' => '/data/relationships/' . $field->getMappedName() . '/data/id',
						],
					);
				}
			}
		}

		return null;
	}

	/**
	 * @param class-string $entityClassName
	 */
	private function findRelated(
		string $entityClassName,
		JsonApi\Objects\IResourceIdentifierObject $identifier,
	): object|null
	{
		if ($identifier->getId() === null || !Uuid\Uuid::isValid($identifier->getId())) {
			return null;
		}

		if (!class_exists($entityClassName)) {
			return null;
		}

		$entityManager = $this->managerRegistry->getManagerForClass($entityClassName);

		if ($entityManager !== null) {
			return $entityManager
				->getRepository($entityClassName)
				->find($identifier->getId());
		}

		return null;
	}

	/**
	 * Hydrate a resource has-many relationship
	 *
	 * @param T|null $entity
	 * @param array<Hydrators\Fields\Field> $entityMapping
	 *
	 * @return array<int, object>
	 */
	protected function hydrateHasMany(
		Hydrators\Fields\Field $field,
		JsonApi\Objects\IRelationshipObject $relationship,
		object|null $entity,
		array $entityMapping,
	): array
	{
		$relations = [];

		// Find relationship field
		if (
			$field instanceof Hydrators\Fields\EntityField
			&& $field->isRelationship()
		) {
			if ($field->isWritable() || ($entity === null && $field->isRequired())) {
				if ($relationship->isHasMany()) {
					foreach ($relationship->getIdentifiers() as $identifier) {
						$relationEntity = $this->findRelated($field->getClassName(), $identifier);

						if ($relationEntity !== null) {
							$relations[] = $relationEntity;
						}
					}
				}

				if ($entity === null && $field->isRequired() && count($relations) === 0) {
					$this->errors->addError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.heading')),
						strval($this->translator->translate('//jsonApi.hydrator.missingRequiredRelation.message')),
						[
							'pointer' => '/data/relationships/' . $field->getMappedName() . '/data',
						],
					);
				}
			}

			return $relations;
		}

		return [];
	}

}
