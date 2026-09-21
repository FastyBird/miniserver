<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Connectors;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Types;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;
use Throwable;
use function array_map;
use function array_merge;
use function implode;
use function is_array;
use function md5;

/**
 * Connectors configuration repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		private readonly Models\Configuration\Builder $builder,
		private readonly Caching\Container $moduleCaching,
		private readonly ApplicationDocuments\Mapping\ClassMetadataFactory $classMetadataFactory,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
	)
	{
	}

	/**
	 * @template T of Documents\Connectors\Connector
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Documents\Connectors\Connector::class,
	): Documents\Connectors\Connector|null
	{
		$queryObject = new Queries\Configuration\FindConnectors();
		$queryObject->byId($id);

		$document = $this->findOneBy($queryObject, $type);

		if ($document !== null && !$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of Documents\Connectors\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = Documents\Connectors\Connector::class,
	): Documents\Connectors\Connector|null
	{
		try {
			/** @phpstan-var T|false $document */
			$document = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function (&$dependencies) use ($queryObject, $type): Documents\Connectors\Connector|false {
					$space = $this->builder
						->load(Types\ConfigurationType::CONNECTORS);

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						if ($metadata->getSubClasses() !== []) {
							$types = [
								$metadata->getDiscriminatorValue(),
							];

							foreach ($metadata->getSubClasses() as $subClass) {
								$subMetadata = $this->classMetadataFactory->getMetadataFor($subClass);

								if ($subMetadata->getDiscriminatorValue() !== null) {
									$types[] = $subMetadata->getDiscriminatorValue();
								}
							}

							$space = $space->find('.[?(@.type in [' . ('"' . implode('","', $types) . '"') . '])]');

							// Reset type to root class
							$type = Documents\Connectors\Connector::class;

						} else {
							$space = $space->find(
								'.[?(@.type =~ /(?i).*^' . $metadata->getDiscriminatorValue() . '*$/)]',
							);
						}
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					$document = $this->documentFactory->create($type, $result[0]);

					if (!$document instanceof $type && !$metadata->isAbstract()) {
						throw new Exceptions\InvalidState('Could not load document');
					}

					$dependencies = [
						NetteCaching\Cache::Tags => [
							Types\ConfigurationType::CONNECTORS->value,
							$document->getId()->toString(),
						],
					];

					return $document;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::CONNECTORS->value,
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		if ($document === false) {
			return null;
		}

		return $document;
	}

	/**
	 * @template T of Documents\Connectors\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = Documents\Connectors\Connector::class,
	): array
	{
		try {
			/** @phpstan-var array<T> $documents */
			$documents = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function (&$dependencies) use ($queryObject, $type): array {
					$children = [];

					$space = $this->builder
						->load(Types\ConfigurationType::CONNECTORS);

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						if ($metadata->getSubClasses() !== []) {
							foreach ($metadata->getSubClasses() as $subClass) {
								$children = array_merge($children, $this->findAllBy($queryObject, $subClass));
							}
						}

						$space = $space->find('.[?(@.type =~ /(?i).*^' . $metadata->getDiscriminatorValue() . '*$/)]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					$documents = array_merge(
						array_map(
							fn (array $item): Documents\Connectors\Connector => $this->documentFactory->create(
								$type,
								$item,
							),
							$result,
						),
						$children,
					);

					$dependencies = [
						NetteCaching\Cache::Tags => array_merge(
							[
								Types\ConfigurationType::CONNECTORS->value,
							],
							array_map(
								static fn (Documents\Connectors\Connector $document): string => $document->getId()->toString(),
								$documents,
							),
						),
					];

					return $documents;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::CONNECTORS->value,
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load documents', $ex->getCode(), $ex);
		}

		return $documents;
	}

}
