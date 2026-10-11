<?php declare(strict_types = 1);

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
 * @property-read Models\Entities\Devices\DevicesRepository $devicesRepository
 */
trait FindsDevice
{

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findDevice(
		string $id,
		Entities\Connectors\Connector|null $connector = null,
	): Entities\Devices\Device
	{
		try {
			if ($connector !== null) {
				$findQuery = new Queries\Entities\FindDevices();
				$findQuery->forConnector($connector);
				$findQuery->byId(Uuid\Uuid::fromString($id));

				$device = $this->devicesRepository->findOneBy($findQuery);
			} else {
				$device = $this->devicesRepository->find(Uuid\Uuid::fromString($id));
			}

			if ($device === null) {
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

		return $device;
	}

}
