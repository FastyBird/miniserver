<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types as ShellyTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
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
 */
final class Gen1HttpApi extends HttpApi
{

	private const string GET_DEVICE_INFORMATION = 'http://%s/shelly';

	private const string GET_DEVICE_DESCRIPTION = 'http://%s/cit/d';

	private const string GET_DEVICE_STATE = 'http://%s/status';

	private const string SET_DEVICE_STATE = 'http://%s/%s/%s?%s=%s';

	private const string GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_shelly.json';

	private const string GET_DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_description.json';

	private const string GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'gen1_http_state.json';

	private const string CHANNEL_BLOCK = '/^(?P<identifier>[0-9]+)_(?P<description>[a-zA-Z]+)(_(?P<channel>[0-9]+))?$/';

	private const string PROPERTY_SENSOR = '/^(?P<identifier>[0-9]+)_(?P<type>[a-zA-Z]{1,3})_(?P<description>[a-zA-Z0-9]+)$/';

	private const array SENSORS_UNIT = [
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

	private const array WRITABLE_SENSORS = [
		ShellyTypes\SensorDescription::MODE->value,
		ShellyTypes\SensorDescription::OUTPUT->value,
		ShellyTypes\SensorDescription::ROLLER->value,
		ShellyTypes\SensorDescription::RED->value,
		ShellyTypes\SensorDescription::GREEN->value,
		ShellyTypes\SensorDescription::BLUE->value,
		ShellyTypes\SensorDescription::WHITE->value,
		ShellyTypes\SensorDescription::WHITE_LEVEL->value,
		ShellyTypes\SensorDescription::GAIN->value,
		ShellyTypes\SensorDescription::BRIGHTNESS->value,
		ShellyTypes\SensorDescription::COLOR_TEMP->value,
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

		if (!array_key_exists('blk', $body) || !array_key_exists('sen', $body)) {
			throw new Exceptions\HttpApiCall(
				'Received response is not valid',
				$request,
				$response,
			);
		}

		$blocks = $body['blk'];
		$sensors = $body['sen'];

		if (is_array($blocks) && is_array($sensors)) {
			foreach ($blocks as $block) {
				if (
					!is_array($block)
					|| !array_key_exists('I', $block)
					|| !array_key_exists('D', $block)
				) {
					$this->logger->debug(
						'Received device block description is not in valid format',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'gen1-http-api',
							'description' => (array) $block,
						],
					);

					continue;
				}

				$blockDescription = [
					'identifier' => intval($block['I']),
					'description' => strval($block['D']),
					'sensors' => [],
				];

				foreach ($sensors as $sensor) {
					if (
						!is_array($sensor)
						|| !array_key_exists('I', $sensor)
						|| !array_key_exists('T', $sensor)
						|| !array_key_exists('D', $sensor)
						|| !array_key_exists('L', $sensor)
					) {
						$this->logger->debug(
							'Received block sensor description is not in valid format',
							[
								'source' => Sources\Connector::SHELLY->value,
								'type' => 'gen1-http-api',
								'description' => (array) $sensor,
							],
						);

						continue;
					}

					if (
						(
							is_array($sensor['L'])
							&& in_array(
								$block['I'],
								array_map(
									static fn ($item): int => intval($item),
									$sensor['L'],
								),
								true,
							)
						)
						|| intval($block['I']) === intval($sensor['L'])
					) {
						try {
							$sensorRange = $this->parseSensorRange(
								strval($block['D']),
								strval($sensor['D']),
								array_key_exists('R', $sensor)
									? (
										is_array($sensor['R'])
											? $sensor['R']
											: strval($sensor['R'])
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
							'identifier' => intval($sensor['I']),
							'type' => $sensor['T'],
							'description' => strval($sensor['D']),
							'data_type' => $sensorRange->getDataType()->value,
							'unit' => array_key_exists(strval(array_key_exists('U', $sensor)), self::SENSORS_UNIT)
								? self::SENSORS_UNIT[strval(array_key_exists('U', $sensor))]
								: null,
							'format' => $sensorRange->getFormat(),
							'invalid' => $sensorRange->getInvalid(),
							'queryable' => true,
							'settable' => in_array($sensor['D'], self::WRITABLE_SENSORS, true),
						];
					}
				}

				$data['blocks'][] = $blockDescription;
			}
		}

		return $this->createMessage(Messages\Response\Gen1\GetDeviceDescription::class, $data);
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

		if (array_key_exists('relays', $body) && is_array($body['relays'])) {
			foreach ($body['relays'] as $relayStatus) {
				assert(is_array($relayStatus));

				$relays[] = $relayStatus;
			}
		}

		$rollers = [];

		if (array_key_exists('rollers', $body) && is_array($body['rollers'])) {
			foreach ($body['rollers'] as $rollerStatus) {
				assert(is_array($rollerStatus));

				$rollers[] = $rollerStatus;
			}
		}

		$inputs = [];

		if (array_key_exists('inputs', $body) && is_array($body['inputs'])) {
			foreach ($body['inputs'] as $inputStatus) {
				assert(is_array($inputStatus));

				$inputs[] = $inputStatus;
			}
		}

		$lights = [];

		if (array_key_exists('lights', $body) && is_array($body['lights'])) {
			foreach ($body['lights'] as $lightStatus) {
				assert(is_array($lightStatus));

				$lights[] = $lightStatus;
			}
		}

		$meters = [];

		if (array_key_exists('meters', $body) && is_array($body['meters'])) {
			foreach ($body['meters'] as $meterStatus) {
				assert(is_array($meterStatus));

				$meters[] = $meterStatus;
			}
		}

		$emeters = [];

		if (array_key_exists('emeters', $body) && is_array($body['emeters'])) {
			foreach ($body['emeters'] as $emeterStatus) {
				assert(is_array($emeterStatus));

				$emeters[] = $emeterStatus;
			}
		}

		$wifi = null;

		if (
			array_key_exists('wifi_sta', $body)
			&& is_array($body['wifi_sta'])
		) {
			$wifi = $body['wifi_sta'];
		}

		return $this->createMessage(Messages\Response\Gen1\GetDeviceState::class, [
			'relays' => $relays,
			'rollers' => $rollers,
			'inputs' => $inputs,
			'lights' => $lights,
			'meters' => $meters,
			'emeters' => $emeters,
			'wifi' => $wifi,
		]);
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
						ValuesTypes\DataType::UNKNOWN,
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
						ValuesTypes\DataType::BOOLEAN,
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
						ValuesTypes\DataType::UCHAR,
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
						ValuesTypes\DataType::USHORT,
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
						ValuesTypes\DataType::UINT,
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
						ValuesTypes\DataType::CHAR,
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
						ValuesTypes\DataType::SHORT,
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
						ValuesTypes\DataType::INT,
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
							ValuesTypes\DataType::INT,
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
							ValuesTypes\DataType::FLOAT,
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
						ValuesTypes\DataType::ENUM,
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
					ValuesTypes\DataType::UNKNOWN,
				)->value,
				'format' => $this->adjustSensorFormat($block, $description, null),
				'invalid' => null,
			],
		);
	}

	private function adjustSensorDataType(
		string $block,
		string $description,
		ValuesTypes\DataType $dataType,
	): ValuesTypes\DataType
	{
		if (
			str_starts_with($block, ShellyTypes\BlockDescription::RELAY->value)
			&& Utils\Strings::lower($description) === ShellyTypes\SensorDescription::OUTPUT->value
		) {
			return ValuesTypes\DataType::SWITCH;
		}

		if (
			str_starts_with($block, ShellyTypes\BlockDescription::LIGHT->value)
			&& Utils\Strings::lower($description) === ShellyTypes\SensorDescription::OUTPUT->value
		) {
			return ValuesTypes\DataType::SWITCH;
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
			str_starts_with($block, ShellyTypes\BlockDescription::RELAY->value)
			&& Utils\Strings::lower($description) === ShellyTypes\SensorDescription::OUTPUT->value
		) {
			return [
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::ON->value],
					[ValuesTypes\DataTypeShort::BOOLEAN->value, true],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RelayPayload::ON->value],
				],
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::OFF->value],
					[ValuesTypes\DataTypeShort::BOOLEAN->value, false],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RelayPayload::OFF->value],
				],
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::TOGGLE->value],
					null,
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RelayPayload::TOGGLE->value],
				],
			];
		}

		if (
			str_starts_with($block, ShellyTypes\BlockDescription::ROLLER->value)
			&& Utils\Strings::lower($description) === ShellyTypes\SensorDescription::ROLLER->value
		) {
			return [
				[
					[ValuesTypes\DataTypeShort::COVER->value, Payloads\Cover::OPEN->value],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RollerPayload::OPEN->value],
					null,
				],
				[
					[ValuesTypes\DataTypeShort::COVER->value, Payloads\Cover::OPENED->value],
					null,
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RollerPayload::OPEN->value],
				],
				[
					[ValuesTypes\DataTypeShort::COVER->value, Payloads\Cover::CLOSE->value],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RollerPayload::CLOSE->value],
					null,
				],
				[
					[ValuesTypes\DataTypeShort::COVER->value, Payloads\Cover::CLOSED->value],
					null,
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RollerPayload::CLOSE->value],
				],
				[
					[ValuesTypes\DataTypeShort::COVER->value, Payloads\Cover::STOP->value],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\RollerPayload::STOP->value],
					null,
				],
			];
		}

		if (
			str_starts_with($block, ShellyTypes\BlockDescription::LIGHT->value)
			&& Utils\Strings::lower($description) === ShellyTypes\SensorDescription::OUTPUT->value
		) {
			return [
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::ON->value],
					[ValuesTypes\DataTypeShort::BOOLEAN->value, true],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\LightSwitchPayload::ON->value],
				],
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::OFF->value],
					[ValuesTypes\DataTypeShort::BOOLEAN->value, false],
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\LightSwitchPayload::OFF->value],
				],
				[
					[ValuesTypes\DataTypeShort::SWITCH->value, Payloads\Switcher::TOGGLE->value],
					null,
					[ValuesTypes\DataTypeShort::STRING->value, ShellyTypes\Payloads\LightSwitchPayload::TOGGLE->value],
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

		if ($propertyMatches['description'] === ShellyTypes\SensorDescription::OUTPUT->value) {
			return 'turn';
		}

		if ($propertyMatches['description'] === ShellyTypes\SensorDescription::ROLLER->value) {
			return 'go';
		}

		if ($propertyMatches['description'] === ShellyTypes\SensorDescription::COLOR_TEMP->value) {
			return 'temp';
		}

		if ($propertyMatches['description'] === ShellyTypes\SensorDescription::WHITE_LEVEL->value) {
			return 'white';
		}

		return $propertyMatches['description'];
	}

}
