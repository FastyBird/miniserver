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

namespace FastyBird\Module\Ui\Models\Configuration\Dashboards\Tabs;

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
 * Dashboards tabs configuration repository
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
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
	public function find(
		Uuid\UuidInterface $id,
	): UiDocuments\Dashboards\Tabs\Tab|null
	{
		$queryObject = new Queries\Configuration\FindDashboardTabs();
		$queryObject->byId($id);

		return $this->findOneBy($queryObject);
	}

	/**
	 * @param Queries\Configuration\FindDashboardTabs<UiDocuments\Dashboards\Tabs\Tab> $queryObject
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindDashboardTabs $queryObject,
	): UiDocuments\Dashboards\Tabs\Tab|null
	{
		try {
			/** @phpstan-var UiDocuments\Dashboards\Tabs\Tab|false $document */
			$document = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyOne($queryObject),
				function (&$dependencies) use ($queryObject): UiDocuments\Dashboards\Tabs\Tab|false {
					$space = $this->builder
						->load(Types\ConfigurationType::DASHBOARDS_TABS);

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					$document = $this->documentFactory->create(
						UiDocuments\Dashboards\Tabs\Tab::class,
						$result[0],
					);

					$dependencies = [
						NetteCaching\Cache::Tags => [
							Types\ConfigurationType::DASHBOARDS_TABS->value,
							$document->getId()->toString(),
						],
					];

					return $document;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::DASHBOARDS_TABS->value,
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
	 * @param Queries\Configuration\FindDashboardTabs<UiDocuments\Dashboards\Tabs\Tab> $queryObject
	 *
	 * @return array<UiDocuments\Dashboards\Tabs\Tab>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDashboardTabs $queryObject,
	): array
	{
		try {
			/** @phpstan-var array<UiDocuments\Dashboards\Tabs\Tab> $documents */
			$documents = $this->moduleCaching->getConfigurationRepositoryCache()->load(
				$this->createKeyAll($queryObject),
				function (&$dependencies) use ($queryObject): array {
					$space = $this->builder
						->load(Types\ConfigurationType::DASHBOARDS_TABS);

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					$documents = array_map(
						fn (array $item): UiDocuments\Dashboards\Tabs\Tab => $this->documentFactory->create(
							UiDocuments\Dashboards\Tabs\Tab::class,
							$item,
						),
						$result,
					);

					$dependencies = [
						NetteCaching\Cache::Tags => array_merge(
							[
								Types\ConfigurationType::DASHBOARDS_TABS->value,
							],
							array_map(
								static fn (UiDocuments\Dashboards\Tabs\Tab $document): string => $document->getId()->toString(),
								$documents,
							),
						),
					];

					return $documents;
				},
				[
					NetteCaching\Cache::Tags => [
						Types\ConfigurationType::DASHBOARDS_TABS->value,
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load documents', $ex->getCode(), $ex);
		}

		return $documents;
	}

}
