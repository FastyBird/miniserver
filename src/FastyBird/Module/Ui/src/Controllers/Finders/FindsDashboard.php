<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Controllers\Finders;

use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Entities\Dashboards\Repository $dashboardsRepository
 */
trait FindsDashboard
{

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findDashboard(string $id): Entities\Dashboards\Dashboard
	{
		try {
			$findQuery = new Queries\Entities\FindDashboards();
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$dashboard = $this->dashboardsRepository->findOneBy($findQuery);

			if ($dashboard === null) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//ui-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//ui-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//ui-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//ui-module.base.messages.notFound.message')),
			);
		}

		return $dashboard;
	}

}
