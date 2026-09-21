<?php declare(strict_types = 1);

/**
 * EntityMapper.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineCrud!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           29.01.14
 */

namespace FastyBird\Core\Mapping\DoctrineCrud;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Helpers\DoctrineCrud\Helpers;
use FastyBird\Core\Mapping\DoctrineCrud as Mapping;
use Nette;
use Nette\Utils;
use phpDocumentor;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function assert;
use function call_user_func;
use function call_user_func_array;
use function class_exists;
use function explode;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function method_exists;
use function sprintf;
use function strpos;
use function trim;
use function ucfirst;

/**
 * Doctrine CRUD entity mapper
 *
 * @package        iPublikuj:DoctrineCrud!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class EntityMapper implements IEntityMapper
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function fillEntity(Utils\ArrayHash $values, Entities\IEntity $entity, bool $isNew = false): Entities\IEntity
	{
		// This used to unwrap Doctrine\Common\Proxy\Proxy subclasses to reach the real entity class.
		// That interface left with doctrine/common, and under ORM 3 with native lazy objects there
		// is no proxy class at all: the entity IS its own class, merely uninitialised. Where a
		// proxy still exists, ClassMetadata::getName() below already resolves to the mapped class.
		$entityClass = $entity::class;

		$entityClassManager = $this->managerRegistry->getManagerForClass($entityClass);

		if ($entityClassManager === null) {
			throw new Exceptions\InvalidState(
				sprintf('Entity manager for entity %s could not be loaded', $entityClass),
			);
		}

		// ObjectManager::getClassMetadata() is typed to the Persistence interface, which has
		// none of the members used below -- getPropertyAccessor(), setFieldValue(), getFieldValue() are
		// all ORM specific -- so the narrowing is load bearing rather than cosmetic. It is
		// ORM\Mapping\ClassMetadata and not ClassMetadataInfo deliberately: ORM 3 removes the
		// latter, having merged it into the former, and in ORM 2 ClassMetadata already extends
		// it, so this reads the same under both majors.
		/** @var ORM\Mapping\ClassMetadata<object> $classMetadata */
		$classMetadata = $entityClassManager->getClassMetadata($entityClass);

		$reflectionProperties = [];

		try {
			$ref = new ReflectionClass($entityClass);

			foreach ($ref->getProperties() as $reflectionProperty) {
				$reflectionProperties[] = $reflectionProperty->getName();
			}
		} catch (ReflectionException) {
			// Nothing to do here
		}

		foreach (array_unique(
			array_merge($reflectionProperties, $classMetadata->getFieldNames(), $classMetadata->getAssociationNames()),
		) as $fieldName) {
			try {
				$propertyReflection = new ReflectionProperty($entityClass, $fieldName);

			} catch (ReflectionException) {
				// Entity property is readonly
				continue;
			}

			$crudAttribute = array_reduce(
				$propertyReflection->getAttributes(),
				static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
					if ($carry === null && $attribute->getName() === Mapping\Attribute\Crud::class) {
						return $attribute;
					}

					return $carry;
				},
			);

			if ($crudAttribute === null) {
				continue;
			}

			$crud = $crudAttribute->newInstance();
			assert($crud instanceof Mapping\Attribute\Crud);

			if ($isNew && $crud->isRequired() && !$values->offsetExists($fieldName)) {
				throw new DoctrineCrudExceptions\MissingRequiredField(
					$entity,
					$fieldName,
					sprintf('Missing required key "%s"', $fieldName),
				);
			}

			if (!array_key_exists($fieldName, (array) $values) || (!$isNew && !$crud->isWritable())) {
				continue;
			}

			$value = $values->offsetGet($fieldName);

			if (
				(
					$value instanceof Utils\ArrayHash
					|| is_array($value)
				)
				&& $classMetadata->getPropertyAccessor($fieldName) !== null
			) {
				if (!$classMetadata->getFieldValue($entity, $fieldName) instanceof Entities\IEntity) {
					$propertyAttributes = $propertyReflection->getAttributes();

					$attributes = array_map(
						(static fn (ReflectionAttribute $attribute): string => $attribute->getName()),
						$propertyAttributes,
					);

					if (isset($value['entity']) && class_exists($value['entity'])) {
						$className = $value['entity'];

					} elseif (in_array(ORM\Mapping\OneToOne::class, $attributes, true)) {
						$propertyAttribute = array_reduce(
							$propertyAttributes,
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

					} elseif (in_array(ORM\Mapping\OneToMany::class, $attributes, true)) {
						$propertyAttribute = array_reduce(
							$propertyAttributes,
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

						$items = [];

						if ($className !== null && class_exists($className)) {
							$rc = new ReflectionClass($className);

							$subClassIdProperty = null;

							foreach ($rc->getProperties() as $subClassProperty) {
								$subClassPropertyAttributes = $subClassProperty->getAttributes();

								$subClassAttributes = array_map(
									(static fn (ReflectionAttribute $attribute): string => $attribute->getName()),
									$subClassPropertyAttributes,
								);

								if (in_array(ORM\Mapping\Id::class, $subClassAttributes, true)) {
									$subClassIdProperty = $subClassProperty->getName();

									break;
								}
							}

							foreach ($value as $item) {
								assert($item instanceof Utils\ArrayHash);
								$subEntity = null;

								$subClassName = $className;

								$rc = new ReflectionClass($className);

								if (
									$rc->isAbstract()
									&& isset($item['entity'])
									&& is_string($item['entity'])
									&& class_exists($item['entity'])
								) {
									$subClassName = $item['entity'];

									$rc = new ReflectionClass($subClassName);
								}

								if ($subClassIdProperty !== null && $item->offsetExists($subClassIdProperty)) {
									$entityClassManager = $this->managerRegistry->getManagerForClass($subClassName);

									$subEntity = $entityClassManager
										?->getRepository($subClassName)
										->find($item->offsetGet($subClassIdProperty));

									if ($subEntity !== null && $subEntity instanceof Entities\IEntity) {
										$subEntity = $this->fillEntity($item, $subEntity);
									}
								}

								if ($subEntity === null) {
									$constructor = $rc->getConstructor();

									$subEntity = $constructor !== null ? $rc->newInstanceArgs(
										Helpers::autowireArguments(
											$constructor,
											array_merge((array) $item, ['parent_entity' => $entity]),
										),
									) : new $subClassName();

									if ($subEntity instanceof Entities\IEntity) {
										$subEntity = $this->fillEntity(
											Utils\ArrayHash::from(
												array_merge(
													(array) $item,
													[$this->findAttributeName($entity, $subClassName) => $entity],
												),
											),
											$subEntity,
											true,
										);
									}
								}

								$items[] = $subEntity;
							}

							$this->setFieldValue($classMetadata, $entity, $fieldName, $items);
						}

						continue;
					} elseif (in_array(ORM\Mapping\ManyToOne::class, $attributes, true)) {
						$propertyAttribute = array_reduce(
							$propertyAttributes,
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

					} else {
						$varAnnotation = $this->parseAnnotation($propertyReflection, 'var');

						$className = null;

						if ($varAnnotation !== null && strpos($varAnnotation, '|') !== false) {
							foreach (explode('|', $varAnnotation) as $varAnnotationItem) {
								if (class_exists($varAnnotationItem)) {
									$className = $varAnnotationItem;
								}
							}
						}
					}

					// Check if class is callable
					if (is_string($className) && class_exists($className)) {
						$rc = new ReflectionClass($className);

						if ($rc->isAbstract() && isset($value['entity']) && class_exists($value['entity'])) {
							$className = $value['entity'];
							$rc = new ReflectionClass($value['entity']);
						}

						$subEntityValue = $isNew ? null : $this->getFieldValue($classMetadata, $entity, $fieldName);

						if (
							$subEntityValue instanceof $className
							&& $subEntityValue instanceof Entities\IEntity
						) {
							$this->setFieldValue(
								$classMetadata,
								$entity,
								$fieldName,
								$this->fillEntity(
									$value instanceof Utils\ArrayHash ? $value : Utils\ArrayHash::from($value),
									$subEntityValue,
								),
							);

						} elseif ($subEntityValue === null) {
							$constructor = $rc->getConstructor();

							if ($constructor !== null) {
								$subEntity = $rc->newInstanceArgs(
									Helpers::autowireArguments(
										$constructor,
										array_merge((array) $value, ['parent_entity' => $entity]),
									),
								);

								$this->setFieldValue($classMetadata, $entity, $fieldName, $subEntity);

							} else {
								$this->setFieldValue($classMetadata, $entity, $fieldName, new $className());
							}
						}
					} else {
						$this->setFieldValue($classMetadata, $entity, $fieldName, $value);
					}
				}

				$fieldValue = $classMetadata->getFieldValue($entity, $fieldName);

				// Check again if entity was created
				if ($fieldValue !== null && $fieldValue instanceof Entities\IEntity) {
					$this->setFieldValue(
						$classMetadata,
						$entity,
						$fieldName,
						$this->fillEntity(Utils\ArrayHash::from((array) $value), $fieldValue, $isNew),
					);
				}
			} else {
				$varAnnotation = $this->parseAnnotation($propertyReflection, 'var');

				$className = $varAnnotation;

				if ($varAnnotation !== null && strpos($varAnnotation, '|') !== false) {
					foreach (explode('|', $varAnnotation) as $varAnnotationItem) {
						if (class_exists($varAnnotationItem)) {
							$className = $varAnnotationItem;
						}
					}
				}

				// Check if class is callable
				if (
					is_string($className)
					&& ($value instanceof Utils\ArrayHash || is_array($value))
				) {
					if (class_exists($className)) {
						$rc = new ReflectionClass($className);

						if ($rc->isAbstract() && isset($value['entity']) && class_exists($value['entity'])) {
							$className = $value['entity'];
							$rc = new ReflectionClass($value['entity']);
						}

						$constructor = $rc->getConstructor();

						if ($constructor !== null) {
							$subEntity = $rc->newInstanceArgs(
								Helpers::autowireArguments(
									$constructor,
									array_merge((array) $value, ['parent_entity' => $entity]),
								),
							);

							$this->setFieldValue($classMetadata, $entity, $fieldName, $subEntity);

						} else {
							$this->setFieldValue($classMetadata, $entity, $fieldName, new $className());
						}
					} elseif (isset($value['entity']) && class_exists($value['entity'])) {
						$className = $value['entity'];
						$rc = new ReflectionClass($value['entity']);

						$constructor = $rc->getConstructor();

						$subEntity = $constructor !== null ? $rc->newInstanceArgs(
							Helpers::autowireArguments(
								$constructor,
								array_merge((array) $value, ['parent_entity' => $entity]),
							),
						) : new $className();

						if ($subEntity instanceof Entities\IEntity) {
							$this->setFieldValue(
								$classMetadata,
								$entity,
								$fieldName,
								$this->fillEntity(
									Utils\ArrayHash::from(
										array_merge(
											(array) $value,
											[$this->findAttributeName($entity, $className) => $entity],
										),
									),
									$subEntity,
									true,
								),
							);
						}
					} else {
						$this->setFieldValue($classMetadata, $entity, $fieldName, $value);
					}
				} else {
					$this->setFieldValue($classMetadata, $entity, $fieldName, $value);
				}
			}
		}

		return $entity;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function findAttributeName(Entities\IEntity $entity, string $className): string
	{
		if (!class_exists($className)) {
			throw new Exceptions\InvalidState(
				sprintf('Provided class name %s is not valid class', $className),
			);
		}

		$rc = new ReflectionClass($className);

		foreach ($rc->getProperties() as $property) {
			$propertyAttributes = $property->getAttributes();

			$attributes = array_map(
				(static fn (ReflectionAttribute $attribute): string => $attribute->getName()),
				$propertyAttributes,
			);

			$propertyClassName = null;

			if (in_array(ORM\Mapping\OneToOne::class, $attributes, true)) {
				$propertyAttribute = array_reduce(
					$propertyAttributes,
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

				$propertyClassName = $propertyAttribute->targetEntity;

			} elseif (in_array(ORM\Mapping\OneToMany::class, $attributes, true)) {
				$propertyAttribute = array_reduce(
					$propertyAttributes,
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

				$propertyClassName = $propertyAttribute->targetEntity;

			} elseif (in_array(ORM\Mapping\ManyToOne::class, $attributes, true)) {
				$propertyAttribute = array_reduce(
					$propertyAttributes,
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

				$propertyClassName = $propertyAttribute->targetEntity;
			}

			if (is_string($propertyClassName) && $entity instanceof $propertyClassName) {
				return $property->getName();
			}
		}

		return 'parent_entity';
	}

	/**
	 * @param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function setFieldValue(
		ORM\Mapping\ClassMetadata $classMetadata,
		Entities\IEntity $entity,
		string $field,
		mixed $value,
	): void
	{
		$methodName = 'set' . ucfirst($field);

		if ($value instanceof Utils\ArrayHash) {
			$value = (array) $value;
		}

		try {
			$propertyReflection = new ReflectionMethod($entity::class, $methodName);

			if ($propertyReflection->isPublic()) {
				$callback = [$entity, $methodName];

				// Try to call state setter
				if (is_callable($callback)) {
					call_user_func_array($callback, [$value]);
				}
			} elseif ($classMetadata->getPropertyAccessor($field) !== null) {
				// Fallback for missing setter
				$classMetadata->setFieldValue($entity, $field, $value);
			}

			// Fallback for missing setter
		} catch (ReflectionException) {
			$classMetadata->setFieldValue($entity, $field, $value);
		}
	}

	/**
	 * @param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function getFieldValue(
		ORM\Mapping\ClassMetadata $classMetadata,
		Entities\IEntity $entity,
		string $field,
	): mixed
	{
		$methodName = 'get' . ucfirst($field);

		try {
			$propertyReflection = new ReflectionMethod($entity::class, $methodName);

			if ($propertyReflection->isPublic()) {
				$callback = [$entity, $methodName];

				// Try to call state setter
				if (is_callable($callback)) {
					return call_user_func($callback);
				}
			} elseif ($classMetadata->getPropertyAccessor($field) !== null) {
				// Fallback for missing setter
				return $classMetadata->getFieldValue($entity, $field);
			}

			// Fallback for missing setter
		} catch (ReflectionException) {
			return $classMetadata->getFieldValue($entity, $field);
		}

		return null;
	}

	private function parseAnnotation(Reflector $ref, string $name): string|null
	{
		$factory = phpDocumentor\Reflection\DocBlockFactory::createInstance();

		if (
			!method_exists($ref, 'getDocComment')
			|| !is_string($ref->getDocComment())
		) {
			return null;
		}

		$docBlock = $factory->create($ref->getDocComment());

		foreach ($docBlock->getTags() as $tag) {
			if ($tag->getName() === $name) {
				return trim((string) $tag);
			}
		}

		return null;
	}

}
