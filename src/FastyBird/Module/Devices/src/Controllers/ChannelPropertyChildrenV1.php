<?php declare(strict_types = 1);

/**
 * ChannelPropertyChildrenV1.php
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

namespace FastyBird\Module\Devices\Controllers;

use Exception;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use Psr\Http\Message;
use function strval;

/**
 * Device property children API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ChannelPropertyChildrenV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TChannel;
	use Controllers\Finders\TChannelProperty;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);
		// & property
		$property = $this->findProperty(strval($request->getAttribute(Router\ApiRoutes::URL_PROPERTY_ID)), $channel);

		$findQuery = new Queries\Entities\FindChannelProperties();
		$findQuery->forParent($property);

		$children = $this->channelPropertiesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $children);
	}

}
