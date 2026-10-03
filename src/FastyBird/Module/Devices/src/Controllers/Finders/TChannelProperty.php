<?php declare(strict_types = 1);

/**
 * TChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           09.02.22
 */

namespace FastyBird\Module\Devices\Controllers\Finders;

use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository
 */
trait TChannelProperty
{

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findProperty(
		string $id,
		Entities\Channels\Channel $channel,
	): Entities\Channels\Properties\Property
	{
		try {
			$findQuery = new Queries\Entities\FindChannelProperties();
			$findQuery->forChannel($channel);
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$property = $this->channelPropertiesRepository->findOneBy($findQuery);

			if ($property === null) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
			);
		}

		return $property;
	}

}
