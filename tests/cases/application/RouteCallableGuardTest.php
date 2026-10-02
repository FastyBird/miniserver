<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_key_exists;
use function constant;
use function defined;
use function escapeshellarg;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function shell_exec;
use function sprintf;
use function strval;
use function sys_get_temp_dir;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Asserts, at production scope, that every registered API route can reach the controller code
 * it names (#596).
 *
 * A route whose target method does not exist does not fail loudly. The authorization
 * middleware reflects on the target method before dispatch, and
 * Security\Access\AnnotationChecker::checkAccess() turns the ReflectionException into "access
 * denied", so the route answers 403 to every caller. That is how the Devices property state
 * routes (#586) and the Triggers control create/update/delete routes (#596) went unnoticed.
 *
 * The controller finder traits (Controllers\Finders\T*) read repositories from the controller
 * that composes them. Their @property-read docblocks satisfy PHPStan whether or not the
 * controller declares the property, so a controller that calls a finder without injecting the
 * repository it reads fails only at runtime, with a 500. That is how both channel property
 * /children routes broke.
 */
final class RouteCallableGuardTest extends TestCase
{

	/**
	 * One route per router file registered by default, plus the routes that used to be broken.
	 * Their presence keeps the "every route" assertion from passing vacuously over an
	 * enumeration that came back short.
	 */
	private const array REPRESENTATIVE_ROUTES = [
		'GET /api/accounts-module/v1/accounts' => 'FastyBird\Module\Accounts\Controllers\AccountsV1::index',
		'GET /api/devices-module/v1/devices' => 'FastyBird\Module\Devices\Controllers\DevicesV1::index',
		'GET /api/devices-module/v1/channels/{channel}/properties/{property}/children'
			=> 'FastyBird\Module\Devices\Controllers\ChannelPropertyChildrenV1::index',
		'GET /api/devices-module/v1/channels/{channel}/properties/{property}/state'
			=> 'FastyBird\Module\Devices\Controllers\ChannelPropertyStateV1::read',
		'GET /api/triggers-module/v1/triggers' => 'FastyBird\Module\Triggers\Controllers\TriggersV1::index',
		'GET /api/triggers-module/v1/triggers/{trigger}/controls'
			=> 'FastyBird\Module\Triggers\Controllers\TriggerControlsV1::index',
		'GET /api/ui-module/v1/dashboards' => 'FastyBird\Module\Ui\Controllers\DashboardsV1::index',
		'GET /api/shelly-connector-homekit-connector-bridge/v1/bridges'
			=> 'FastyBird\Bridge\ShellyConnectorHomeKitConnector\Controllers\BridgesV1::index',
		'GET /api/viera-connector-homekit-connector-bridge/v1/bridges'
			=> 'FastyBird\Bridge\VieraConnectorHomeKitConnector\Controllers\BridgesV1::index',
		'GET /api/virtual-thermostat-addon-homekit-connector-bridge/v1/bridges'
			=> 'FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers\BridgesV1::index',
	];

	/**
	 * One called finder per package that ships finders, and the two controllers that used to
	 * call a finder without its repository. Same purpose as REPRESENTATIVE_ROUTES.
	 */
	private const array REPRESENTATIVE_FINDERS = [
		'FastyBird\Module\Accounts\Controllers\EmailsV1 uses FastyBird\Module\Accounts\Controllers\Finders\TAccount',
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		'FastyBird\Module\Devices\Controllers\ChannelPropertyChildrenV1 uses FastyBird\Module\Devices\Controllers\Finders\TChannel',
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		'FastyBird\Module\Devices\Controllers\ChannelPropertyStateV1 uses FastyBird\Module\Devices\Controllers\Finders\TDevice',
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		'FastyBird\Module\Triggers\Controllers\TriggerControlsV1 uses FastyBird\Module\Triggers\Controllers\Finders\TTrigger',
		'FastyBird\Module\Ui\Controllers\TabsV1 uses FastyBird\Module\Ui\Controllers\Finders\TTab',
	];

	/**
	 * @var array{
	 *     error: string|null,
	 *     routes: list<array{route: string, controller: string|null, method: string|null, public: bool}>,
	 *     finders: list<array{controller: string, trait: string, called: bool, property: string, declared: bool}>,
	 * }|null
	 */
	private static array|null $result = null;

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryRouteTargetsAPublicControllerMethod(): void
	{
		$result = $this->bootProductionScope();

		self::assertNull($result['error']);

		$targets = [];
		$unreachable = [];

		foreach ($result['routes'] as $route) {
			$target = sprintf('%s::%s', $route['controller'] ?? '?', $route['method'] ?? '?');

			$targets[$route['route']] = $target;

			if (!$route['public']) {
				$unreachable[] = sprintf('%s -> %s', $route['route'], $target);
			}
		}

		foreach (self::REPRESENTATIVE_ROUTES as $route => $target) {
			self::assertArrayHasKey($route, $targets, sprintf('Route "%s" is not registered', $route));
			self::assertSame($target, $targets[$route], $route);
		}

		self::assertSame(
			[],
			$unreachable,
			sprintf(
				"These routes name a controller method that does not exist or is not public:\n%s",
				implode("\n", $unreachable),
			),
		);
	}

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryCalledFinderHasTheDependenciesItReads(): void
	{
		$result = $this->bootProductionScope();

		self::assertNull($result['error']);

		$called = [];
		$undeclared = [];

		foreach ($result['finders'] as $finder) {
			// A finder the controller composes but never calls reads nothing at runtime
			if (!$finder['called']) {
				continue;
			}

			$called[sprintf('%s uses %s', $finder['controller'], $finder['trait'])] = true;

			if (!$finder['declared']) {
				$undeclared[] = sprintf(
					'%s uses %s, which reads $%s',
					$finder['controller'],
					$finder['trait'],
					$finder['property'],
				);
			}
		}

		foreach (self::REPRESENTATIVE_FINDERS as $pair) {
			self::assertArrayHasKey($pair, $called, sprintf('Finder "%s" was not found', $pair));
		}

		self::assertSame(
			[],
			$undeclared,
			sprintf(
				"These controllers call a finder without declaring the property it reads:\n%s",
				implode("\n", $undeclared),
			),
		);
	}

	/**
	 * @return array{
	 *     error: string|null,
	 *     routes: list<array{route: string, controller: string|null, method: string|null, public: bool}>,
	 *     finders: list<array{controller: string, trait: string, called: bool, property: string, declared: bool}>,
	 * }
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function bootProductionScope(): array
	{
		// Both tests read the same report, and a production-scope boot takes several seconds
		self::$result ??= $this->runProductionScope();

		return self::$result;
	}

	/**
	 * @return array{
	 *     error: string|null,
	 *     routes: list<array{route: string, controller: string|null, method: string|null, public: bool}>,
	 *     finders: list<array{controller: string, trait: string, called: bool, property: string, declared: bool}>,
	 * }
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function runProductionScope(): array
	{
		// The production container is compiled once and then loaded from the temp directory with
		// no freshness check (debugMode is off), so the default var/temp would hand this test
		// whatever container an earlier boot left there. The suite run's own temp directory is
		// new for every run.
		$tempDir = (defined('FB_TEMP_DIR') ? strval(constant('FB_TEMP_DIR')) : sys_get_temp_dir())
			. '/route-callable-guard';

		$command = sprintf(
			'FB_APP_DIR=%s FB_TEMP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s 2>&1',
			escapeshellarg(__DIR__ . '/../../..'),
			escapeshellarg($tempDir),
			escapeshellarg('route-guard-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-routes.php'),
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		$decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		if (
			!is_array($decoded)
			|| !array_key_exists('error', $decoded)
			|| !(is_string($decoded['error']) || $decoded['error'] === null)
			|| !is_array($decoded['routes'] ?? null)
			|| !is_array($decoded['finders'] ?? null)
		) {
			throw new RuntimeException(sprintf('Production-scope boot did not report a result: %s', $output));
		}

		$routes = [];

		foreach ($decoded['routes'] as $route) {
			if (!is_array($route)) {
				throw new RuntimeException('Production-scope boot reported a malformed route list');
			}

			$controller = $route['controller'] ?? null;
			$method = $route['method'] ?? null;

			if (
				!is_string($route['route'] ?? null)
				|| !(is_string($controller) || $controller === null)
				|| !(is_string($method) || $method === null)
				|| !is_bool($route['public'] ?? null)
			) {
				throw new RuntimeException('Production-scope boot reported a malformed route list');
			}

			$routes[] = [
				'route' => $route['route'],
				'controller' => $controller,
				'method' => $method,
				'public' => $route['public'],
			];
		}

		$finders = [];

		foreach ($decoded['finders'] as $finder) {
			if (
				!is_array($finder)
				|| !is_string($finder['controller'] ?? null)
				|| !is_string($finder['trait'] ?? null)
				|| !is_bool($finder['called'] ?? null)
				|| !is_string($finder['property'] ?? null)
				|| !is_bool($finder['declared'] ?? null)
			) {
				throw new RuntimeException('Production-scope boot reported a malformed finder list');
			}

			$finders[] = [
				'controller' => $finder['controller'],
				'trait' => $finder['trait'],
				'called' => $finder['called'],
				'property' => $finder['property'],
				'declared' => $finder['declared'],
			];
		}

		return [
			'error' => $decoded['error'],
			'routes' => $routes,
			'finders' => $finders,
		];
	}

}
