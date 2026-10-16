<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use Psr\Http\Message;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_merge;
use function intval;
use function is_array;
use function is_bool;
use function preg_match;
use function sprintf;
use function uniqid;

/**
 * Generation 2 device http API interface
 */
final class Gen2HttpApi extends HttpApi
{

	private const string GET_DEVICE_INFORMATION_ENDPOINT = 'http://%s/rpc/Shelly.GetDeviceInfo';

	private const string GET_DEVICE_CONFIGURATION_ENDPOINT = 'http://%s/rpc/Shelly.GetConfig';

	private const string GET_DEVICE_STATE_ENDPOINT = 'http://%s/rpc/Shelly.GetStatus';

	private const string SET_DEVICE_STATE_ENDPOINT = 'http://%s/rpc';

	private const string GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen2_http_shelly.json';

	private const string GET_DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME = 'gen2_http_config.json';

	private const string GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'gen2_http_state.json';

	private const string PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	private const string COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	private const string SWITCH_SET_METHOD = 'Switch.Set';

	private const string COVER_GO_TO_POSITION_METHOD = 'Cover.GoToPosition';

	private const string LIGHT_SET_METHOD = 'Light.Set';

	private const string SCRIPT_SET_ENABLED_METHOD = 'Script.Start';

	private const string SCRIPT_SET_DISABLED_METHOD = 'Script.Stop';

	private const string SMOKE_SET_METHOD = 'Smoke.Mute';

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen2\GetDeviceInformation> : Messages\Response\Gen2\GetDeviceInformation)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Gen2\GetDeviceInformation
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_INFORMATION_ENDPOINT, $address),
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
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen2\GetDeviceConfiguration> : Messages\Response\Gen2\GetDeviceConfiguration)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceConfiguration(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Gen2\GetDeviceConfiguration
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_CONFIGURATION_ENDPOINT, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceConfiguration($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceConfiguration($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Gen2\GetDeviceState> : Messages\Response\Gen2\GetDeviceState)
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
	): Promise\PromiseInterface|Messages\Response\Gen2\GetDeviceState
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_STATE_ENDPOINT, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceState($request, $result);
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
		string $component,
		int|float|string|bool $value,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
			}

			throw new Exceptions\HttpApiCall('Property identifier is not in expected format');
		}

		try {
			$componentMethod = $this->buildComponentMethod($component, $value);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Component method could not be created'));
			}

			throw new Exceptions\HttpApiCall('Component method could not be created');
		}

		try {
			$componentAttribute = $this->buildComponentAttribute($component);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Component attribute could not be created'));
			}

			throw new Exceptions\HttpApiCall('Component attribute could not be created');
		}

		try {
			$body = $componentAttribute !== null ? Utils\Json::encode([
				'id' => uniqid(),
				'method' => $componentMethod,
				'params' => [
					'id' => intval($propertyMatches['identifier']),
					$componentAttribute->value => $value,
				],
			]) : Utils\Json::encode([
				'id' => uniqid(),
				'method' => $componentMethod,
				'params' => [
					'id' => intval($propertyMatches['identifier']),
				],
			]);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState(
					'Message body could not be encoded',
					$ex->getCode(),
					$ex,
				));
			}

			throw new Exceptions\InvalidState(
				'Message body could not be encoded',
				$ex->getCode(),
				$ex,
			);
		}

		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf(self::SET_DEVICE_STATE_ENDPOINT, $address),
			[],
			[],
			$body,
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

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
	): Messages\Response\Gen2\GetDeviceInformation
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createMessage(Messages\Response\Gen2\GetDeviceInformation::class, $body);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceConfiguration(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Gen2\GetDeviceConfiguration
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = $devicePower = $scripts = $smoke = $voltmeters = [];

		foreach ($body as $key => $configuration) {
			if (
				is_array($configuration)
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& Types\ComponentType::tryFrom($componentMatches['component']) !== null
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH->value) {
					$switches[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER->value) {
					$covers[] = array_merge(
						$configuration,
						[
							'motor' => (array) $configuration['motor'],
							'obstruction_detection' => (array) $configuration['obstruction_detection'],
							'safety_switch' => (array) $configuration['safety_switch'],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT->value) {
					$lights[] = array_merge(
						$configuration,
						[
							'default' => (array) $configuration['default'],
							'night_mode' => (array) $configuration['night_mode'],
							'safety_switch' => (array) $configuration['safety_switch'],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT->value) {
					$inputs[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE->value) {
					$temperature[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY->value) {
					$humidity[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::DEVICE_POWER->value) {
					$devicePower[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::SCRIPT->value) {
					$scripts[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::SMOKE->value) {
					$smoke[] = $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::VOLTMETER->value) {
					$voltmeters[] = $configuration;
					$voltmeters[] = array_merge(
						$configuration,
						[
							'xvoltage' => (array) $configuration['xvoltage'],
						],
					);
				}
			}
		}

		return $this->createMessage(Messages\Response\Gen2\GetDeviceConfiguration::class, [
			Types\ComponentType::SWITCH->value => $switches,
			Types\ComponentType::COVER->value => $covers,
			Types\ComponentType::INPUT->value => $inputs,
			Types\ComponentType::LIGHT->value => $lights,
			Types\ComponentType::TEMPERATURE->value => $temperature,
			Types\ComponentType::HUMIDITY->value => $humidity,
			Types\ComponentType::DEVICE_POWER->value => $devicePower,
			Types\ComponentType::SCRIPT->value => $scripts,
			Types\ComponentType::SMOKE->value => $smoke,
			Types\ComponentType::VOLTMETER->value => $voltmeters,
		]);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\Gen2\GetDeviceState
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = $devicePower = $scripts = $smoke = $voltmeters = [];
		$ethernet = $wifi = null;

		foreach ($body as $key => $state) {
			if (
				is_array($state)
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& Types\ComponentType::tryFrom($componentMatches['component']) !== null
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH->value) {
					$switches[] = array_merge(
						$state,
						[
							'aenergy' => $state['aenergy'],
							'temperature' => $state['temperature'],
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER->value) {
					$covers[] = array_merge(
						$state,
						[
							'aenergy' => $state['aenergy'],
							'temperature' => $state['temperature'],
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT->value) {
					$lights[] = $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT->value) {
					$inputs[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE->value) {
					$temperature[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY->value) {
					$humidity[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::DEVICE_POWER->value) {
					$devicePower[] = array_merge(
						$state,
						[
							'battery' => $state['battery'],
							'external' => $state['external'],
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::SCRIPT->value) {
					$scripts[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::SMOKE->value) {
					$smoke[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::VOLTMETER->value) {
					$voltmeters[] = array_merge(
						$state,
						[
							'errors' => array_key_exists('errors', $state)
								? (array) $state['errors']
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::ETHERNET->value) {
					$ethernet = $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::WIFI->value) {
					$wifi = $state;
				}
			}
		}

		return $this->createMessage(Messages\Response\Gen2\GetDeviceState::class, [
			Types\ComponentType::SWITCH->value => $switches,
			Types\ComponentType::COVER->value => $covers,
			Types\ComponentType::INPUT->value => $inputs,
			Types\ComponentType::LIGHT->value => $lights,
			Types\ComponentType::TEMPERATURE->value => $temperature,
			Types\ComponentType::HUMIDITY->value => $humidity,
			Types\ComponentType::DEVICE_POWER->value => $devicePower,
			Types\ComponentType::SCRIPT->value => $scripts,
			Types\ComponentType::SMOKE->value => $smoke,
			Types\ComponentType::VOLTMETER->value => $voltmeters,
			Types\ComponentType::ETHERNET->value => $ethernet,
			Types\ComponentType::WIFI->value => $wifi,
		]);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentMethod(string $component, int|float|string|bool $value): string
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return self::SWITCH_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::TARGET_POSITION->value
		) {
			return self::COVER_GO_TO_POSITION_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& (
				$componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
				|| $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS->value
			)
		) {
			return self::LIGHT_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SCRIPT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::RUNNING->value
			&& is_bool($value)
		) {
			return $value ? self::SCRIPT_SET_ENABLED_METHOD : self::SCRIPT_SET_DISABLED_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SMOKE->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::MUTE->value
		) {
			return self::SMOKE_SET_METHOD;
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentAttribute(string $component): Types\ComponentActionAttribute|null
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return Types\ComponentActionAttribute::ON;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::TARGET_POSITION->value
		) {
			return Types\ComponentActionAttribute::POSITION;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return Types\ComponentActionAttribute::ON;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS->value
		) {
			return Types\ComponentActionAttribute::BRIGHTNESS;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SCRIPT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::RUNNING->value
		) {
			return null;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SMOKE->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::MUTE->value
		) {
			return null;
		}

		throw new Exceptions\InvalidState('Property attribute could not be build');
	}

}
