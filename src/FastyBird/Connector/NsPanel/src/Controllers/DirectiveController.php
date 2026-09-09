<?php declare(strict_types = 1);

/**
 * DirectiveController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\Controllers;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Schemas as ToolsSchemas;
use FastyBird\Core\Tools\Utilities as ToolsUtilities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use RuntimeException;
use function array_key_exists;
use function is_scalar;
use function is_string;
use function preg_match;
use function strval;

/**
 * Gateway directive controller
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DirectiveController extends BaseController
{

	private const SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'set_device_state.json';

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly Protocol\Driver $devicesDriver,
		private readonly NsPanel\Helpers\MessageBuilder $messageBuilder,
		private readonly ToolsSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ServerRequestError
	 * @throws ExchangeExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function process(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->logger->debug(
			'Requested updating of characteristics of selected accessories',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'directive-controller',
				'request' => [
					'method' => $request->getMethod(),
					'address' => $request->getServerParams()['REMOTE_ADDR'],
					'path' => $request->getUri()->getPath(),
					'query' => $request->getQueryParams(),
					'body' => $request->getBody()->getContents(),
				],
			],
		);

		$connectorId = $request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR);
		$connectorId = is_scalar($connectorId) ? strval($connectorId) : null;

		if ($connectorId === null || !Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Connector id could not be determined',
			);
		}

		$connectorId = Uuid\Uuid::fromString($connectorId);

		$request->getBody()->rewind();

		$body = $request->getBody()->getContents();

		$gatewayId = $request->getAttribute(Router\Router::URL_GATEWAY_ID);
		$gatewayId = is_scalar($gatewayId) ? strval($gatewayId) : null;

		if ($gatewayId === null || !Uuid\Uuid::isValid($gatewayId)) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Gateway id could not be determined',
			);
		}

		$gatewayId = Uuid\Uuid::fromString($gatewayId);

		// At first, try to load device
		try {
			$deviceId = $request->getAttribute(Router\Router::URL_DEVICE_ID);
			$deviceId = is_scalar($deviceId) ? strval($deviceId) : null;

			if ($deviceId === null || !Uuid\Uuid::isValid($deviceId)) {
				throw new Exceptions\ServerRequestError(
					$request,
					Types\ServerStatus::ENDPOINT_UNREACHABLE,
					'Device could could not be found',
				);
			}

			$protocolDevice = $this->devicesDriver->findDevice(Uuid\Uuid::fromString($deviceId));

		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::ENDPOINT_UNREACHABLE,
				'Device could could not be found',
			);
		}

		if (
			$protocolDevice === null
			|| !$protocolDevice->getConnector()->equals($connectorId)
			|| !$protocolDevice->getParent()->equals($gatewayId)
		) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::ENDPOINT_UNREACHABLE,
				'Device could could not be found',
			);
		}

		try {
			$body = $this->schemaValidator->validate(
				$body,
				$this->getSchema(self::SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (ToolsExceptions\Logic | ToolsExceptions\MalformedInput | ToolsExceptions\InvalidData $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INVALID_DIRECTIVE,
				'Could not validate received response payload',
				$ex->getCode(),
				$ex,
			);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Could not validate received response payload',
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$requestData = $this->messageBuilder->create(
				NsPanel\API\Messages\Request\SetDeviceState::class,
				(array) Utils\Json::decode(Utils\Json::encode($body), forceArrays: true),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INVALID_DIRECTIVE,
				'Could not map data to request message',
				$ex->getCode(),
				$ex,
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INVALID_DIRECTIVE,
				'Request data are not valid JSON data',
				$ex->getCode(),
				$ex,
			);
		}

		$state = [];

		foreach ($requestData->getDirective()->getPayload()->getState() as $key => $item) {
			$identifier = null;

			if (
				is_string($key)
				&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
				&& array_key_exists('name', $matches) === true
			) {
				$identifier = $matches['name'];
			}

			foreach ($item->getState() as $attribute => $value) {
				$state[] = [
					'capability' => $item->getType()->value,
					'attribute' => $attribute,
					'value' => ToolsUtilities\Value::flattenValue($value),
					'identifier' => $identifier,
				];
			}
		}

		$this->queue->append(
			$this->messageBuilder->create(
				Queue\Messages\StoreDeviceState::class,
				[
					'connector' => $protocolDevice->getConnector(),
					'gateway' => $protocolDevice->getParent(),
					'device' => $protocolDevice->getId(),
					'state' => $state,
				],
			),
		);

		try {
			$responseData = $this->messageBuilder->create(
				NsPanel\API\Messages\Response\SetDeviceState::class,
				[
					'event' => [
						'header' => [
							'name' => Types\Header::UPDATE_DEVICE_STATES_RESPONSE->value,
							'message_id' => $requestData->getDirective()->getHeader()->getMessageId(),
							'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
						],
					],
				],
			);

			$response->getBody()->write(Utils\Json::encode($responseData->toJson()));
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Could not map data to response message',
				$ex->getCode(),
				$ex,
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Response data are not valid JSON data',
				$ex->getCode(),
				$ex,
			);
		} catch (RuntimeException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::INTERNAL_ERROR,
				'Could not write data to response',
				$ex->getCode(),
				$ex,
			);
		}

		return $response;
	}

}
