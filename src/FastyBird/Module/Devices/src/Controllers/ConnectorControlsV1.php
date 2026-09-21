<?php declare(strict_types = 1);

/**
 * ConnectorControlsV1.php
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
 * Connector controls API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ConnectorControlsV1 extends BaseV1
{

	use Controllers\Finders\TConnector;

	public function __construct(
		protected readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $connectorControlsRepository,
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
		// At first, try to load connector
		$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

		$findQuery = new Queries\Entities\FindConnectorControls();
		$findQuery->forConnector($connector);

		$controls = $this->connectorControlsRepository->getResultSet($findQuery);

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
		// At first, try to load connector
		$this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$control = $this->connectorControlsRepository->find(
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
		// At first, try to load connector
		$this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$control = $this->connectorControlsRepository->find(
				Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))),
			);

			if ($control !== null) {
				if ($relationEntity === Schemas\Connectors\Controls\Control::RELATIONSHIPS_CONNECTOR) {
					return $this->buildResponse($request, $response, $control->getConnector());
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
