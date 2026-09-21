<?php declare(strict_types = 1);

/**
 * EmailEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use Nette;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:UiModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DashboardEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Entities\Dashboards\Tabs\Repository $tabsRepository,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::prePersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function prePersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (!$entity instanceof Entities\Dashboards\Dashboard) {
			return;
		}

		$findTabQuery = new Queries\Entities\FindDashboardTabs();
		$findTabQuery->forDashboard($entity);

		$foundTab = $this->tabsRepository->findOneBy($findTabQuery);

		if ($foundTab === null) {
			$tab = new Entities\Dashboards\Tabs\Tab($entity, 'default');

			$entity->addTab($tab);
		}
	}

}
