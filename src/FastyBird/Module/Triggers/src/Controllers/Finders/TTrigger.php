<?php declare(strict_types = 1);

/**
 * TTrigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Controllers\Finders;

use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\ITranslator $translator
 * @property-read Models\Entities\Triggers\TriggersRepository $triggersRepository
 */
trait TTrigger
{

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findTrigger(string $id): Entities\Triggers\Trigger
	{
		try {
			$findQuery = new Queries\Entities\FindTriggers();
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$trigger = $this->triggersRepository->findOneBy($findQuery);

			if ($trigger === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		return $trigger;
	}

}
