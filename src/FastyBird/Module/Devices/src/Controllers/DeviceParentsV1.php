<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Controllers;

use Exception;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use Psr\Http\Message;
use function strval;

/**
 * Device parents API controller
 *
 * @Secured\User(loggedIn)
 */
final class DeviceParentsV1 extends BaseV1
{

	use Controllers\Finders\TDevice;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
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
		// At first, try to load device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->forChild($device);

		$parents = $this->devicesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $parents);
	}

}
