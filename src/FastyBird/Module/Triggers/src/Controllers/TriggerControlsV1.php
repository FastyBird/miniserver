<?php declare(strict_types = 1);

/**
 * TriggerControlsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Controllers;

use Exception;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message;
use Ramsey\Uuid;
use function strtolower;
use function strval;

/**
 * Trigger controls API controller
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class TriggerControlsV1 extends BaseV1
{

	use Controllers\Finders\TTrigger;

	public function __construct(
		protected readonly Models\Entities\Triggers\TriggersRepository $triggersRepository,
		private readonly Models\Entities\Triggers\Controls\ControlsRepository $controlRepository,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Manual) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		$findQuery = new Queries\Entities\FindTriggerControls();
		$findQuery->forTrigger($trigger);

		$controls = $this->controlRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $controls);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Manual) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$findQuery = new Queries\Entities\FindTriggerControls();
			$findQuery->forTrigger($trigger);
			$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

			// & control
			$control = $this->controlRepository->findOneBy($findQuery);

			if ($control !== null) {
				return $this->buildResponse($request, $response, $control);
			}
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
			strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
		);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Manual) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			$findQuery = new Queries\Entities\FindTriggerControls();
			$findQuery->forTrigger($trigger);
			$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

			// & control
			$control = $this->controlRepository->findOneBy($findQuery);

			if ($control !== null) {
				if ($relationEntity === Schemas\Triggers\Controls\Control::RELATIONSHIPS_TRIGGER) {
					return $this->buildResponse($request, $response, $control->getTrigger());
				}
			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
				);
			}
		}

		return parent::readRelationship($request, $response);
	}

}
