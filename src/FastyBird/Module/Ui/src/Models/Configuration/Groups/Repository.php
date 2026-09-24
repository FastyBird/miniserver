<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Models\Configuration\Groups;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Caching;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Exceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Types;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;
use Throwable;
use function array_map;
use function array_merge;
use function is_array;

/**
 * Groups configuration repository
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		private readonly Caching\Container $moduleCaching,
		private readonly Models\Configuration\Builder $builder,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function find(Uuid\UuidInterface $id): UiDocuments\Groups\Group|null
	{
		$queryObject = new Queries\Configuration\FindGroups();
		$queryObject->byId($id);

		return $this->findOneBy($queryObject);
	}

	/**
	 * @param Queries\Configuration\FindGroups<UiDocuments\Groups\Group> $queryObject
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindGroups $queryObject,
	): UiDocuments\Groups\Group|null
	{
		try {
			/** @phpstan-var UiDocuments\Groups\Group|false $document */
			$document = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyOne($queryObject),
				function (&$dependencies) use ($queryObject): UiDocuments\Groups\Group|false {
					$space = $this->builder
						->load(Types\ConfigurationType::GROUPS);

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					$document = $this->documentFactory->create(
						UiDocuments\Groups\Group::class,
						$result[0],
					);

					$dependencies = [
						NetteCaching\Cache::Tags => [
							Types\ConfigurationType::GROUPS->value,
							$document->getId()->toString(),
						],
					];

					return $document;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::GROUPS->value,
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
	 * @param Queries\Configuration\FindGroups<UiDocuments\Groups\Group> $queryObject
	 *
	 * @return array<UiDocuments\Groups\Group>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindGroups $queryObject,
	): array
	{
		try {
			/** @phpstan-var array<UiDocuments\Groups\Group> $documents */
			$documents = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyAll($queryObject),
				function (&$dependencies) use ($queryObject): array {
					$space = $this->builder
						->load(Types\ConfigurationType::GROUPS);

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					$documents = array_map(
						fn (array $item): UiDocuments\Groups\Group => $this->documentFactory->create(
							UiDocuments\Groups\Group::class,
							$item,
						),
						$result,
					);

					$dependencies = [
						NetteCaching\Cache::Tags => array_merge(
							[
								Types\ConfigurationType::GROUPS->value,
							],
							array_map(
								static fn (UiDocuments\Groups\Group $document): string => $document->getId()->toString(),
								$documents,
							),
						),
					];

					return $documents;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::GROUPS->value,
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load documents', $ex->getCode(), $ex);
		}

		return $documents;
	}

}
