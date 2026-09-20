<?php declare(strict_types = 1);

/**
 * Builder.php
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

namespace FastyBird\Module\Ui\Models\Configuration;

use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Ui\Caching;
use FastyBird\Module\Ui\Exceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Types;
use Flow\JSONPath;
use Nette\Caching as NetteCaching;
use Throwable;
use function assert;

/**
 * Configuration builder
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Builder
{

	public function __construct(
		private Caching\Container $moduleCaching,
		private Models\Entities\Dashboards\Repository $dashboardsRepository,
		private Models\Entities\Dashboards\Tabs\Repository $dashboardsTabsRepository,
		private Models\Entities\Groups\Repository $groupsRepository,
		private Models\Entities\Widgets\Repository $widgetsRepository,
		private Models\Entities\Widgets\DataSources\Repository $widgetsDataSourcesRepository,
		private Models\Entities\Widgets\Displays\Repository $widgetsDisplayRepository,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function load(Types\ConfigurationType $type, bool $force = false): JSONPath\JSONPath
	{
		try {
			if ($force) {
				$this->moduleCaching->getConfigurationBuilderCache()->remove($type->value);
			}

			$data = $this->moduleCaching->getConfigurationBuilderCache()->load(
				$type->value,
				fn (): JSONPath\JSONPath => new JSONPath\JSONPath($this->build($type)),
				[
					NetteCaching\Cache::Tags => [$type->value],
				],
			);
			assert($data instanceof JSONPath\JSONPath);

			return $data;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	private function build(Types\ConfigurationType $type): array
	{
		$data = [];

		if ($type === Types\ConfigurationType::DASHBOARDS) {
			foreach ($this->dashboardsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DASHBOARDS_TABS) {
			foreach ($this->dashboardsTabsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::GROUPS) {
			foreach ($this->groupsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::WIDGETS) {
			foreach ($this->widgetsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::WIDGETS_DATA_SOURCES) {
			foreach ($this->widgetsDataSourcesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::WIDGETS_DISPLAY) {
			foreach ($this->widgetsDisplayRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		}

		return $data;
	}

}
