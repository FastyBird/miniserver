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
 * @date           14.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Channels\Properties;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Types;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;
use Throwable;
use function array_filter;
use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function md5;

/**
 * Channels properties configuration repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		private readonly Models\Configuration\Builder $builder,
		private readonly Caching\Container $moduleCaching,
		private readonly CoreDocuments\Mapping\ClassMetadataFactory $classMetadataFactory,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
	)
	{
	}

	/**
	 * @template T of DevicesDocuments\Channels\Properties\Property
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = DevicesDocuments\Channels\Properties\Property::class,
	): DevicesDocuments\Channels\Properties\Property|null
	{
		$queryObject = new Queries\Configuration\FindChannelProperties();
		$queryObject->byId($id);

		$document = $this->findOneBy($queryObject, $type);

		if ($document !== null && !$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of DevicesDocuments\Channels\Properties\Property
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string $type = DevicesDocuments\Channels\Properties\Property::class,
	): DevicesDocuments\Channels\Properties\Property|null
	{
		try {
			/** @phpstan-var T|false $document */
			$document = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function (&$dependencies) use ($queryObject, $type): DevicesDocuments\Channels\Properties\Property|false {
					$space = $this->builder
						->load(Types\ConfigurationType::CHANNELS_PROPERTIES);

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						$space = $space->find('.[?(@.type == "' . $metadata->getDiscriminatorValue() . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					foreach (
						[
							DevicesDocuments\Channels\Properties\Dynamic::class,
							DevicesDocuments\Channels\Properties\Variable::class,
							DevicesDocuments\Channels\Properties\Mapped::class,
						] as $class
					) {
						try {
							$document = $this->documentFactory->create($class, $result[0]);
							assert($document instanceof $type);

							$dependencies = [
								NetteCaching\Cache::Tags => [
									Types\ConfigurationType::CHANNELS_PROPERTIES->value,
									$document->getId()->toString(),
								],
							];

							return $document;
						} catch (Throwable) {
							// Just ignore it
						}
					}

					return false;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::CHANNELS_PROPERTIES->value,
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
	 * @template T of DevicesDocuments\Channels\Properties\Property
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string $type = DevicesDocuments\Channels\Properties\Property::class,
	): array
	{
		try {
			/** @phpstan-var array<T> $documents */
			$documents = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function (&$dependencies) use ($queryObject, $type): array {
					$space = $this->builder
						->load(Types\ConfigurationType::CHANNELS_PROPERTIES);

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						$space = $space->find('.[?(@.type == "' . $metadata->getDiscriminatorValue() . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					$documents = array_filter(
						array_map(
							function (array $item): DevicesDocuments\Channels\Properties\Property|null {
								foreach (
									[
										DevicesDocuments\Channels\Properties\Dynamic::class,
										DevicesDocuments\Channels\Properties\Variable::class,
										DevicesDocuments\Channels\Properties\Mapped::class,
									] as $class
								) {
									try {
										return $this->documentFactory->create($class, $item);
									} catch (Throwable) {
										// Just ignore it
									}
								}

								return null;
							},
							$result,
						),
						static fn ($item): bool => $item instanceof $type,
					);

					$dependencies = [
						NetteCaching\Cache::Tags => array_merge(
							[
								Types\ConfigurationType::CHANNELS_PROPERTIES->value,
							],
							array_map(
								static fn (DevicesDocuments\Channels\Properties\Property $document): string => $document->getId()->toString(),
								$documents,
							),
						),
					];

					return $documents;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::CHANNELS_PROPERTIES->value,
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load documents', $ex->getCode(), $ex);
		}

		return $documents;
	}

}
