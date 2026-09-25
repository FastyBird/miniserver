<?php declare(strict_types = 1);

/**
 * DevicePropertyStateV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           24.01.24
 */

namespace FastyBird\Module\Devices\Controllers;

use Exception;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function strval;

/**
 * Device property state API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class DevicePropertyStateV1 extends BaseV1
{

	use Controllers\Finders\TDevice;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApiExceptions\JsonApi
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
		// & property
		$findPropertyQuery = new Queries\Configuration\FindDeviceProperties();
		$findPropertyQuery->byDeviceId($device->getId());
		$findPropertyQuery->byId(
			Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_PROPERTY_ID))),
		);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

		if (
			!$property instanceof Documents\Devices\Properties\Dynamic
			&& !$property instanceof Documents\Devices\Properties\Mapped
		) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
			);
		}

		$state = $this->devicePropertiesStatesManager->readState($property);

		if ($state === null) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
			);
		}

		return $this->buildResponse($request, $response, $state);
	}

}
