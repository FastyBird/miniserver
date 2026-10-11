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
 * @property-read Localization\ITranslator $translator
 * @property-read Models\Entities\Widgets\Repository $widgetsRepository
 */
trait FindsWidget
{

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findWidget(string $id): Entities\Widgets\Widget
	{
		try {
			$findQuery = new Queries\Entities\FindWidgets();
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$widget = $this->widgetsRepository->findOneBy($findQuery);

			if ($widget === null) {
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

		return $widget;
	}

}
