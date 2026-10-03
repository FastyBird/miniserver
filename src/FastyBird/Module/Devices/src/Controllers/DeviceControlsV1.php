<?php declare(strict_types = 1);

/**
 * DeviceControlsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Devices\Controllers;

use Exception;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Device controls API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class DeviceControlsV1 extends BaseV1
{

	use Controllers\Finders\TConnector;
	use Controllers\Finders\TDevice;

	public function __construct(
		protected readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Devices\Controls\ControlsRepository $deviceControlsRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load connector, when the device is addressed through one
		$connectorId = $request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID);
		$connector = is_string($connectorId) ? $this->findConnector($connectorId) : null;

		// & device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)), $connector);

		$findQuery = new Queries\Entities\FindDeviceControls();
		$findQuery->forDevice($device);

		$controls = $this->deviceControlsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $controls);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load connector, when the device is addressed through one
		$connectorId = $request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID);
		$connector = is_string($connectorId) ? $this->findConnector($connectorId) : null;

		// & device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)), $connector);

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$findQuery = new Queries\Entities\FindDeviceControls();
			$findQuery->forDevice($device);
			$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

			$control = $this->deviceControlsRepository->findOneBy($findQuery);

			if ($control !== null) {
				return $this->buildResponse($request, $response, $control);
			}
		}

		throw new Exceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
			strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
		);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load connector, when the device is addressed through one
		$connectorId = $request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID);
		$connector = is_string($connectorId) ? $this->findConnector($connectorId) : null;

		// & device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)), $connector);

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$findQuery = new Queries\Entities\FindDeviceControls();
			$findQuery->forDevice($device);
			$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

			$control = $this->deviceControlsRepository->findOneBy($findQuery);

			if ($control !== null) {
				if ($relationEntity === Schemas\Devices\Controls\Control::RELATIONSHIPS_DEVICE) {
					return $this->buildResponse($request, $response, $control->getDevice());
				}
			} else {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
				);
			}
		}

		return parent::readRelationship($request, $response);
	}

}
