<?php declare(strict_types = 1);

/**
 * Gen1HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function explode;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strval;

/**
 * Generation 1 device http API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1HttpApi extends HttpApi
{

	use Nette\SmartObject;

	private const GET_DEVICE_INFORMATION = 'http://%s/shelly';

	private const GET_DEVICE_DESCRIPTION = 'http://%s/cit/d';

	private const GET_DEVICE_STATE = 'http://%s/status';

	private const SET_DEVICE_STATE = 'http://%s/%s/%s?%s=%s';

	private const GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_shelly.json';

	private const GET_DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_description.json';

	private const GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'gen1_http_state.json';

	private const CHANNEL_BLOCK = '/^(?P<identifier>[0-9]+)_(?P<description>[a-zA-Z]+)(_(?P<channel>[0-9]+))?$/';

	private const PROPERTY_SENSOR = '/^(?P<identifier>[0-9]+)_(?P<type>[a-zA-Z]{1,3})_(?P<description>[a-zA-Z0-9]+)$/';

	private const SENSORS_UNIT = [
		'W' => 'W',
		'Wmin' => 'Wmin',
		'Wh' => 'Wh',
		'V' => 'V',
		'A' => 'A',
		'C' => '°C',
		'F' => '°F',
		'K' => 'K',
		'deg' => 'deg',
		'lux' => 'lx',
		'ppm' => 'ppm',
		's' => 's',
		'pct' => '%',
	];

	private const WRITABLE_SENSORS = [
		Types\SensorDescription::MODE->value,
		Types\SensorDescription::OUTPUT->value,
		Types\SensorDescription::ROLLER->value,
		Types\SensorDescription::RED->value,
		Types\SensorDescription::GREEN->value,
		Types\SensorDescription::BLUE->value,
		Types\SensorDescription::WHITE->value,
		Types\SensorDescription::WHITE_LEVEL->value,
		Types\SensorDescription::GAIN->value,
		Types\SensorDescription::BRIGHTNESS->value,
		Types\SensorDescription::COLOR_TEMP->value,
	];

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen1\GetDeviceInformation> : Messages\Response\Gen1\GetDeviceInformation)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Gen1\GetDeviceInformation
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_INFORMATION, $address),
		);

		$result = $this->callRequest($request, null, null, null, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceInformation($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceInformation($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen1\GetDeviceDescription> : Messages\Response\Gen1\GetDeviceDescription)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceDescription(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Gen1\GetDeviceDescription
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_DESCRIPTION, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceDescription($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceDescription($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen1\GetDeviceState> : Messages\Response\Gen1\GetDeviceState)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceState(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Gen1\GetDeviceState
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_STATE, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceStatus($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceStatus($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	public function setDeviceState(
		string $address,
		string|null $username,
		string|null $password,
		string $blockIdentifier,
		string $sensorIdentifier,
		int|float|string|bool $value,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		if (
			preg_match(self::CHANNEL_BLOCK, $blockIdentifier, $channelMatches) !== 1
			|| !array_key_exists('channel', $channelMatches)
		) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Channel identifier is not in expected format'));
			}

			throw new Exceptions\InvalidState('Channel identifier is not in expected format');
		}

		try {
			$sensorAction = $this->buildSensorAction($sensorIdentifier);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
			}

			throw new Exceptions\InvalidState('Sensor action could not be created');
		}

		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(
				self::SET_DEVICE_STATE,
				$address,
				$channelMatches['description'],
				intval($channelMatches['channel']),
				$sensorAction,
				$value,
			),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					$deferred->resolve($response->getStatusCode() === StatusCodeInterface::STATUS_OK);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $result->getStatusCode() === StatusCodeInterface::STATUS_OK;
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceInformation(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Gen1\GetDeviceInformation
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createMessage(Messages\Response\Gen1\GetDeviceInformation::class, $body);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceDescription(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Gen1\GetDeviceDescription
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME,
		);

		$data = [
			'blocks' => [],
		];

		if (!$body->offsetExists('blk') || !$body->offsetExists('sen')) {
			throw new Exceptions\HttpApiCall(
				'Received response is not valid',
				$request,
				$response,
			);
		}

		$blocks = $body->offsetGet('blk');
		$sensors = $body->offsetGet('sen');

		if ($blocks instanceof Utils\ArrayHash && $sensors instanceof Utils\ArrayHash) {
			foreach ($blocks as $block) {
				if (
					!$block instanceof Utils\ArrayHash
					|| !$block->offsetExists('I')
					|| !$block->offsetExists('D')
				) {
					$this->logger->debug(
						'Received device block description is not in valid format',
						[
							'source' => MetadataTypes\Sources\Connector::SHELLY->value,
							'type' => 'gen1-http-api',
							'description' => (array) $block,
						],
					);

					continue;
				}

				$blockDescription = [
					'identifier' => intval($block->offsetGet('I')),
					'description' => strval($block->offsetGet('D')),
					'sensors' => [],
				];

				foreach ($sensors as $sensor) {
					if (
						!$sensor instanceof Utils\ArrayHash
						|| !$sensor->offsetExists('I')
						|| !$sensor->offsetExists('T')
						|| !$sensor->offsetExists('D')
						|| !$sensor->offsetExists('L')
					) {
						$this->logger->debug(
							'Received block sensor description is not in valid format',
							[
								'source' => MetadataTypes\Sources\Connector::SHELLY->value,
								'type' => 'gen1-http-api',
								'description' => (array) $sensor,
							],
						);

						continue;
					}

					if (
						(
							$sensor->offsetGet('L') instanceof Utils\ArrayHash
							&& in_array(
								$block->offsetGet('I'),
								array_map(
									static fn ($item): int => intval($item),
									(array) $sensor->offsetGet('L'),
								),
								true,
							)
						)
						|| intval($block->offsetGet('I')) === intval($sensor->offsetGet('L'))
					) {
						try {
							$sensorRange = $this->parseSensorRange(
								strval($block->offsetGet('D')),
								strval($sensor->offsetGet('D')),
								$sensor->offsetExists('R')
									? (
										is_array($sensor->offsetGet('R'))
										|| $sensor->offsetGet('R') instanceof Utils\ArrayHash
											? (array) $sensor->offsetGet('R')
											: strval($sensor->offsetGet('R'))
									)
									: null,
							);
						} catch (Exceptions\Runtime $ex) {
							throw new Exceptions\HttpApiCall(
								'Sensor range could not be decoded from response',
								$request,
								$response,
								$ex->getCode(),
								$ex,
							);
						}

						$blockDescription['sensors'][] = [
							'identifier' => intval($sensor->offsetGet('I')),
							'type' => $sensor->offsetGet('T'),
							'description' => strval($sensor->offsetGet('D')),
							'data_type' => $sensorRange->getDataType()->value,
							'unit' => array_key_exists(strval($sensor->offsetExists('U')), self::SENSORS_UNIT)
								? self::SENSORS_UNIT[strval($sensor->offsetExists('U'))]
								: null,
							'format' => $sensorRange->getFormat(),
							'invalid' => $sensorRange->getInvalid(),
							'queryable' => true,
							'settable' => in_array($sensor->offsetGet('D'), self::WRITABLE_SENSORS, true),
						];
					}
				}

				$data['blocks'][] = $blockDescription;
			}
		}

		return $this->createMessage(Messages\Response\Gen1\GetDeviceDescription::class, Utils\ArrayHash::from($data));
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceStatus(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Gen1\GetDeviceState
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
		);

		$relays = [];

		if (
			$body->offsetExists('relays')
			&& (
				is_array($body->offsetGet('relays'))
				|| $body->offsetGet('relays') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('relays') as $relayStatus) {
				assert($relayStatus instanceof Utils\ArrayHash);

				$relays[] = (array) $relayStatus;
			}
		}

		$rollers = [];

		if (
			$body->offsetExists('rollers')
			&& (
				is_array($body->offsetGet('rollers'))
				|| $body->offsetGet('rollers') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('rollers') as $rollerStatus) {
				assert($rollerStatus instanceof Utils\ArrayHash);

				$rollers[] = (array) $rollerStatus;
			}
		}

		$inputs = [];

		if (
			$body->offsetExists('inputs')
			&& (
				is_array($body->offsetGet('inputs'))
				|| $body->offsetGet('inputs') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('inputs') as $inputStatus) {
				assert($inputStatus instanceof Utils\ArrayHash);

				$inputs[] = (array) $inputStatus;
			}
		}

		$lights = [];

		if (
			$body->offsetExists('lights')
			&& (
				is_array($body->offsetGet('lights'))
				|| $body->offsetGet('lights') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('lights') as $lightStatus) {
				assert($lightStatus instanceof Utils\ArrayHash);

				$lights[] = (array) $lightStatus;
			}
		}

		$meters = [];

		if (
			$body->offsetExists('meters')
			&& (
				is_array($body->offsetGet('meters'))
				|| $body->offsetGet('meters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('meters') as $meterStatus) {
				assert($meterStatus instanceof Utils\ArrayHash);

				$meters[] = (array) $meterStatus;
			}
		}

		$emeters = [];

		if (
			$body->offsetExists('emeters')
			&& (
				is_array($body->offsetGet('emeters'))
				|| $body->offsetGet('emeters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('emeters') as $emeterStatus) {
				assert($emeterStatus instanceof Utils\ArrayHash);

				$emeters[] = (array) $emeterStatus;
			}
		}

		$wifi = null;

		if (
			$body->offsetExists('wifi_sta')
			&& $body->offsetGet('wifi_sta') instanceof Utils\ArrayHash
		) {
			$wifi = (array) $body->offsetGet('wifi_sta');
		}

		return $this->createMessage(Messages\Response\Gen1\GetDeviceState::class, Utils\ArrayHash::from([
			'relays' => $relays,
			'rollers' => $rollers,
			'inputs' => $inputs,
			'lights' => $lights,
			'meters' => $meters,
			'emeters' => $emeters,
			'wifi' => $wifi,
		]));
	}

	/**
	 * @param string|array<string>|null $rawRange
	 *
	 * @throws Exceptions\Runtime
	 */
	private function parseSensorRange(
		string $block,
		string $description,
		string|array|null $rawRange,
	): Messages\Response\Gen1\SensorRange
	{
		$invalidValue = null;

		if (is_array($rawRange) && count($rawRange) === 2) {
			$normalValue = $rawRange[0];
			$invalidValue = $rawRange[1] === (string) (int) $rawRange[1]
				? intval($rawRange[1])
				: ($rawRange[1] === (string) (float) $rawRange[1] ? floatval(
					$rawRange[1],
				) : $rawRange[1]);

		} elseif (is_string($rawRange)) {
			$normalValue = $rawRange;

		} else {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::UNKNOWN,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => null,
				],
			);
		}

		if ($normalValue === '0/1' || $normalValue === '1/0') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::BOOLEAN,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U8') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::UCHAR,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U16') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::USHORT,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U32') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::UINT,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I8') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::CHAR,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I16') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::SHORT,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I32') {
			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::INT,
					)->value,
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if (str_contains($normalValue, '/')) {
			$normalValueParts = explode('/', $normalValue);

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (int) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (int) $normalValueParts[1]
			) {
				return $this->messageBuilder->create(
					Messages\Response\Gen1\SensorRange::class,
					[
						'data_type' => $this->adjustSensorDataType(
							$block,
							$description,
							MetadataTypes\DataType::INT,
						)->value,
						'format' => $this->adjustSensorFormat(
							$block,
							$description,
							[intval($normalValueParts[0]), intval($normalValueParts[1])],
						),
						'invalid' => $invalidValue,
					],
				);
			}

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (float) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (float) $normalValueParts[1]
			) {
				return $this->messageBuilder->create(
					Messages\Response\Gen1\SensorRange::class,
					[
						'data_type' => $this->adjustSensorDataType(
							$block,
							$description,
							MetadataTypes\DataType::FLOAT,
						)->value,
						'format' => $this->adjustSensorFormat(
							$block,
							$description,
							[floatval($normalValueParts[0]), floatval($normalValueParts[1])],
						),
						'invalid' => $invalidValue,
					],
				);
			}

			return $this->messageBuilder->create(
				Messages\Response\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::ENUM,
					)->value,
					'format' => $this->adjustSensorFormat(
						$block,
						$description,
						array_map(static fn (string $item): string => Utils\Strings::trim($item), $normalValueParts),
					),
					'invalid' => $invalidValue,
				],
			);
		}

		return $this->messageBuilder->create(
			Messages\Response\Gen1\SensorRange::class,
			[
				'data_type' => $this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::UNKNOWN,
				)->value,
				'format' => $this->adjustSensorFormat($block, $description, null),
				'invalid' => null,
			],
		);
	}

	private function adjustSensorDataType(
		string $block,
		string $description,
		MetadataTypes\DataType $dataType,
	): MetadataTypes\DataType
	{
		if (
			str_starts_with($block, Types\BlockDescription::RELAY->value)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT->value
		) {
			return MetadataTypes\DataType::SWITCH;
		}

		if (
			str_starts_with($block, Types\BlockDescription::LIGHT->value)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT->value
		) {
			return MetadataTypes\DataType::SWITCH;
		}

		return $dataType;
	}

	/**
	 * @param array<string>|array<int>|array<float>|null $format
	 *
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
	 */
	private function adjustSensorFormat(
		string $block,
		string $description,
		array|null $format,
	): array|null
	{
		if (
			str_starts_with($block, Types\BlockDescription::RELAY->value)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT->value
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::ON->value],
					[MetadataTypes\DataTypeShort::BOOLEAN->value, true],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RelayPayload::ON->value],
				],
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::OFF->value],
					[MetadataTypes\DataTypeShort::BOOLEAN->value, false],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RelayPayload::OFF->value],
				],
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::TOGGLE->value],
					null,
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RelayPayload::TOGGLE->value],
				],
			];
		}

		if (
			str_starts_with($block, Types\BlockDescription::ROLLER->value)
			&& Utils\Strings::lower($description) === Types\SensorDescription::ROLLER->value
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::COVER->value, MetadataTypes\Payloads\Cover::OPEN->value],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RollerPayload::OPEN->value],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::COVER->value, MetadataTypes\Payloads\Cover::OPENED->value],
					null,
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RollerPayload::OPEN->value],
				],
				[
					[MetadataTypes\DataTypeShort::COVER->value, MetadataTypes\Payloads\Cover::CLOSE->value],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RollerPayload::CLOSE->value],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::COVER->value, MetadataTypes\Payloads\Cover::CLOSED->value],
					null,
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RollerPayload::CLOSE->value],
				],
				[
					[MetadataTypes\DataTypeShort::COVER->value, MetadataTypes\Payloads\Cover::STOP->value],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\RollerPayload::STOP->value],
					null,
				],
			];
		}

		if (
			str_starts_with($block, Types\BlockDescription::LIGHT->value)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT->value
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::ON->value],
					[MetadataTypes\DataTypeShort::BOOLEAN->value, true],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\LightSwitchPayload::ON->value],
				],
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::OFF->value],
					[MetadataTypes\DataTypeShort::BOOLEAN->value, false],
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\LightSwitchPayload::OFF->value],
				],
				[
					[MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\Payloads\Switcher::TOGGLE->value],
					null,
					[MetadataTypes\DataTypeShort::STRING->value, Types\Payloads\LightSwitchPayload::TOGGLE->value],
				],
			];
		}

		return $format;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildSensorAction(string $property): string
	{
		if (preg_match(self::PROPERTY_SENSOR, $property, $propertyMatches) !== 1) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if ($propertyMatches['description'] === Types\SensorDescription::OUTPUT->value) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::ROLLER->value) {
			return 'go';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::COLOR_TEMP->value) {
			return 'temp';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::WHITE_LEVEL->value) {
			return 'white';
		}

		return $propertyMatches['description'];
	}

}
