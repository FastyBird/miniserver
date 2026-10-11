<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Controllers\Finders;

use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Entities\Groups\Repository $groupsRepository
 */
trait FindsGroup
{

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findGroup(string $id): Entities\Groups\Group
	{
		try {
			$group = $this->groupsRepository->find(Uuid\Uuid::fromString($id));

			if ($group === null) {
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

		return $group;
	}

}
