<?php declare(strict_types = 1);

/**
 * ConnectorPropertyStateV1.php
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
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
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
 * Connector property state API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ConnectorPropertyStateV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TConnector;

	public function __construct(
		protected readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load connector
		$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));
		// & property
		$findPropertyQuery = new Queries\Configuration\FindConnectorProperties();
		$findPropertyQuery->byConnectorId($connector->getId());
		$findPropertyQuery->byId(
			Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_PROPERTY_ID))),
		);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

		if (!$property instanceof Documents\Connectors\Properties\Dynamic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//connectors-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//connectors-module.base.messages.notFound.message')),
			);
		}

		$state = $this->connectorPropertiesStatesManager->readState($property);

		if ($state === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//connectors-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//connectors-module.base.messages.notFound.message')),
			);
		}

		return $this->buildResponse($request, $response, $state);
	}

}
