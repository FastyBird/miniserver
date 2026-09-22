<?php declare(strict_types = 1);

/**
 * EntityDiscriminator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           06.02.24
 */

namespace FastyBird\Core\Subscribers\Application;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Core\Entities\Application as Entities;
use FastyBird\Core\Exceptions;
use ReflectionClass;
use function array_keys;
use function end;
use function explode;
use function in_array;
use function sprintf;
use function str_contains;
use function strtolower;

/**
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EntityDiscriminator implements Common\EventSubscriber
{

	private const array INHERITANCE_TYPE = ['SINGLE_TABLE', 'JOINED'];

	/** @var array<string, string> */
	private static array $discriminators = [];

	/**
	 * @return array<string>
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::loadClassMetadata,
		];
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ORM\Mapping\MappingException
	 */
	public function loadClassMetadata(ORM\Event\LoadClassMetadataEventArgs $event): void
	{
		$metadata = $event->getClassMetadata();
		$classReflection = $metadata->getReflectionClass();

		$inheritanceTypeAttribute = $classReflection->getAttributes(ORM\Mapping\InheritanceType::class);

		$inheritanceType = $inheritanceTypeAttribute !== [] ? $inheritanceTypeAttribute[0]->newInstance() : null;

		$discriminatorAttribute = $classReflection->getAttributes(ORM\Mapping\DiscriminatorMap::class);

		$discriminatorMap = $discriminatorAttribute !== [] ? $discriminatorAttribute[0]->newInstance() : null;

		$em = $event->getEntityManager();

		$discriminatorMapExtension = $this->detectFromChildren($em, $classReflection);

		if (
			$inheritanceType instanceof ORM\Mapping\InheritanceType
			&& in_array($inheritanceType->value, self::INHERITANCE_TYPE, true)
			&& $discriminatorMapExtension !== []
		) {
			$extendedDiscriminatorMap = $discriminatorMap?->value ?? [];

			foreach ($discriminatorMapExtension as $name => $className) {
				if (!in_array($className, $extendedDiscriminatorMap, true)) {
					$extendedDiscriminatorMap[$name] = $className;
				}
			}

			if (!in_array($classReflection->name, $extendedDiscriminatorMap, true)) {
				$extendedDiscriminatorMap[$this->getShortName($classReflection->name)] = $classReflection->name;
			}

			foreach ($extendedDiscriminatorMap as $name => $classString) {
				$metadata->addDiscriminatorMapClass($name, $classString);
			}
		}
	}

	/**
	 * @param ReflectionClass<object> $parentRc
	 *
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function detectFromChildren(ORM\EntityManagerInterface $em, ReflectionClass $parentRc): array
	{
		self::$discriminators = [];

		$mappingDriver = $em->getConfiguration()->getMetadataDriverImpl();

		if ($mappingDriver === null) {
			throw new Exceptions\InvalidState('Entity manager mapping driver could not be loaded');
		}

		foreach ($mappingDriver->getAllClassNames() as $class) {
			$childrenRc = new ReflectionClass($class);

			if ($childrenRc->getParentClass() === false) {
				continue;
			}

			if (!$childrenRc->isSubclassOf($parentRc->getName())) {
				continue;
			}

			$discriminator = $this->getDiscriminatorForClass($childrenRc);

			if ($discriminator !== null) {
				self::$discriminators[$discriminator] = $class;
			}
		}

		return self::$discriminators;
	}

	/**
	 * @param ReflectionClass<object> $rc
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function getDiscriminatorForClass(ReflectionClass $rc): string|null
	{
		if ($rc->isAbstract()) {
			return null;
		}

		$attributes = $rc->getAttributes(Entities\Mapping\DiscriminatorEntry::class);

		if ($attributes !== []) {
			$discriminatorEntry = $attributes[0]->newInstance();

			$this->ensureDiscriminatorIsUnique($discriminatorEntry->name, $rc);

			return $discriminatorEntry->name;
		}

		return null;
	}

	/**
	 * @param ReflectionClass<object> $rc
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function ensureDiscriminatorIsUnique(string $discriminator, ReflectionClass $rc): void
	{
		if (in_array($discriminator, array_keys(self::$discriminators), true)) {
			throw new Exceptions\InvalidState(sprintf(
				'Found duplicate discriminator map entry "%s" in "%s".',
				$discriminator,
				$rc->getName(),
			));
		}
	}

	private function getShortName(string $className): string
	{
		if (!str_contains($className, '\\')) {
			return strtolower($className);
		}

		$parts = explode('\\', $className);

		return strtolower(end($parts));
	}

}
