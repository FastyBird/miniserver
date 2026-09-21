<?php declare(strict_types = 1);

/**
 * ChannelControlsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.06.19
 */

namespace FastyBird\Module\Devices\Controllers;

use Exception;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use function strval;

/**
 * Device channel controls API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ChannelControlsV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TChannel;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly Models\Entities\Channels\Controls\ControlsRepository $channelControlsRepository,
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
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		$findQuery = new Queries\Entities\FindChannelControls();
		$findQuery->forChannel($channel);

		$controls = $this->channelControlsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $controls);
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$control = $this->channelControlsRepository->find(
				Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))),
			);

			if ($control !== null) {
				return $this->buildResponse($request, $response, $control);
			}
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
			strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
		);
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$control = $this->channelControlsRepository->find(
				Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))),
			);

			if ($control !== null) {
				if ($relationEntity === Schemas\Channels\Controls\Control::RELATIONSHIPS_CHANNEL) {
					return $this->buildResponse($request, $response, $control->getChannel());
				}
			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
				);
			}
		}

		return parent::readRelationship($request, $response);
	}

}
