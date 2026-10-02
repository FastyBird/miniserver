<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use FilesystemIterator;
use Nette\Neon;
use PhpToken;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use UnexpectedValueException;
use function array_diff;
use function array_keys;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function glob;
use function implode;
use function is_array;
use function is_string;
use function realpath;
use function sort;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use const GLOB_ONLYDIR;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_ENCAPSED_AND_WHITESPACE;
use const T_NULLSAFE_OBJECT_OPERATOR;
use const T_OBJECT_OPERATOR;
use const T_STRING;

/**
 * Asserts that every translation key the backend asks for is defined (#603).
 *
 * A missing key does not fail. The translator returns the key itself, so the user reads
 * "//modbus-connector.cmd.install.messages..." in a console prompt, or an API client gets it
 * as the title of a JSON:API error. Nothing else notices: PHPStan sees a string, and no test
 * renders every console prompt or error.
 *
 * This test needs no container. It tokenizes every PHP file under src/FastyBird/<Type>/<Name>/src,
 * collects the first argument of every ->translate() call, and resolves it against the
 * Translations/*.neon files of the same tree. The translator is global: the domain is the part
 * of the key before its first dot, and it selects the file named <domain>.<locale>.neon in
 * whichever package ships it. So the DateTime automator's //triggers-module.* keys resolve
 * against the Triggers module's file.
 *
 * Console commands, API hydrators and controllers all use the same
 * $this->translator->translate('//<domain>.<key>') form, so one pattern covers all three.
 */
final class TranslationKeyGuardTest extends TestCase
{

	/**
	 * The locale every key has to resolve in: config/common.neon's default and first fallback
	 * locale. File names spell it both en_US and en_us, and both load at runtime.
	 */
	private const string LOCALE = 'en_us';

	/**
	 * Keys built at runtime: a literal prefix concatenated with a value, usually an enum's
	 * ->value. The scan can only check that the prefix names an existing subtree; which leaves
	 * the code can reach depends on the value.
	 *
	 * A prefix the sources use but this list does not name fails the test, so a new runtime
	 * key has to be added here deliberately. An entry the sources no longer use fails too.
	 */
	private const array RUNTIME_PREFIXES = [
		// Addon/VirtualThermostat Commands\Install
		'//virtual-thermostat-addon.cmd.install.answers.mode.' => 'Types\HvacMode->value',
		'//virtual-thermostat-addon.cmd.install.answers.preset.' => 'Types\Preset->value',
		'//virtual-thermostat-addon.cmd.install.data.' => 'Types\ChannelPropertyIdentifier->value',
		'//virtual-thermostat-addon.cmd.install.messages.preset.' => 'Types\Preset->value',
		'//virtual-thermostat-addon.cmd.install.questions.provide.targetTemperature.' => 'Types\Preset->value',
		// Bridge/*ConnectorHomeKitConnector Builders\Builder, lower-cased
		'//shelly-connector-homekit-connector-bridge.base.misc.services.' => 'HomeKit Types\ServiceType->value',
		'//viera-connector-homekit-connector-bridge.base.misc.services.' => 'HomeKit Types\ServiceType->value',
		// Connector/HomeKit Commands\Install and Bridge/ShellyConnectorHomeKitConnector Commands\Build
		'//homekit-connector.cmd.base.category.' => 'HomeKit Types\AccessoryCategory->value',
		// Connector Commands\Install: the connector's client mode
		'//modbus-connector.cmd.base.mode.' => 'Modbus Types\ClientMode->value',
		'//ns-panel-connector.cmd.base.mode.' => 'NsPanel Types\ClientMode->value',
		'//shelly-connector.cmd.base.mode.' => 'Shelly Types\ClientMode->value',
		'//sonoff-connector.cmd.base.mode.' => 'Sonoff Types\ClientMode->value',
		'//tuya-connector.cmd.base.mode.' => 'Tuya Types\ClientMode->value',
		// Connector Commands\Install: other device and channel enums
		'//fb-mqtt-connector.cmd.base.protocol.' => 'FbMqtt Types\ProtocolVersion->value',
		'//modbus-connector.cmd.base.registerType.' => 'Modbus Types\ChannelType->value',
		'//ns-panel-connector.cmd.base.attribute.' => 'NsPanel Types\Attribute->value',
		'//ns-panel-connector.cmd.base.capability.' => 'NsPanel Types\Capability->value',
		'//ns-panel-connector.cmd.base.deviceType.' => 'NsPanel Types\Category->value',
		'//shelly-connector.cmd.install.answers.generation.' => 'Shelly Types\DeviceGeneration->value',
	];

	/**
	 * Keys the scan must find, one per kind of caller: a Core API hydrator, a module API
	 * hydrator, a hydrator that uses another package's domain, and a console command. Their
	 * presence keeps the "every key resolves" assertion from passing over a scan that came
	 * back short.
	 */
	private const array REPRESENTATIVE_KEYS = [
		'//api.hydrator.invalidAttribute.heading',
		'//devices-module.base.messages.notFound.heading',
		'//triggers-module.conditions.messages.invalidTime.heading',
		'//modbus-connector.cmd.install.title',
	];

	/** @var array{literals: list<array{location: string, key: string}>, prefixes: list<array{location: string, key: string}>, dynamic: list<string>}|null */
	private static array|null $calls = null;

	/** @var array<string, array<string, array<string, true>>>|null */
	private static array|null $catalogues = null;

	/**
	 * @throws Neon\Exception
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	public function testEveryLiteralKeyIsDefined(): void
	{
		$calls = self::calls();

		$found = [];
		$undefined = [];

		foreach ($calls['literals'] as $call) {
			$found[$call['key']] = true;

			if (!$this->isDefined($call['key'])) {
				$undefined[] = sprintf('%s %s', $call['location'], $call['key']);
			}
		}

		foreach (self::REPRESENTATIVE_KEYS as $key) {
			self::assertArrayHasKey($key, $found, sprintf('Key "%s" was not found in the sources', $key));
		}

		self::assertSame(
			[],
			$undefined,
			sprintf(
				"These translation keys are not defined in any %s translation file:\n%s",
				self::LOCALE,
				implode("\n", $undefined),
			),
		);
	}

	/**
	 * @throws Neon\Exception
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	public function testEveryRuntimeCompletedPrefixIsAllowlisted(): void
	{
		$calls = self::calls();

		$used = [];

		foreach ($calls['prefixes'] as $call) {
			$used[] = $call['key'];
		}

		$used = array_values(array_unique($used));
		sort($used);

		$allowed = array_keys(self::RUNTIME_PREFIXES);
		sort($allowed);

		self::assertSame(
			[],
			array_values(array_diff($used, $allowed)),
			'These runtime-completed key prefixes are not in RUNTIME_PREFIXES',
		);

		self::assertSame(
			[],
			array_values(array_diff($allowed, $used)),
			'These RUNTIME_PREFIXES entries are no longer used by any ->translate() call',
		);

		$empty = [];

		foreach ($allowed as $prefix) {
			if (!$this->isDefinedPrefix($prefix)) {
				$empty[] = $prefix;
			}
		}

		self::assertSame([], $empty, 'These runtime-completed key prefixes name no defined key');
	}

	/**
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	public function testEveryKeyIsAStringLiteral(): void
	{
		self::assertSame(
			[],
			self::calls()['dynamic'],
			'These ->translate() calls build their key from something other than a string literal,'
				. ' so this test cannot check them',
		);
	}

	/**
	 * @throws Neon\Exception
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	private function isDefined(string $key): bool
	{
		[$domain, $id] = $this->split($key);

		return $domain !== null && isset(self::catalogues()[$domain][self::LOCALE][$id]);
	}

	/**
	 * @throws Neon\Exception
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	private function isDefinedPrefix(string $prefix): bool
	{
		[$domain, $id] = $this->split($prefix);

		if ($domain === null) {
			return false;
		}

		foreach (array_keys(self::catalogues()[$domain][self::LOCALE] ?? []) as $defined) {
			if (str_starts_with($defined, $id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{0: string|null, 1: string}
	 */
	private function split(string $key): array
	{
		if (!str_starts_with($key, '//')) {
			return [null, $key];
		}

		$dot = strpos($key, '.');

		if ($dot === false) {
			return [null, $key];
		}

		return [substr($key, 2, $dot - 2), substr($key, $dot + 1)];
	}

	/**
	 * @return array{literals: list<array{location: string, key: string}>, prefixes: list<array{location: string, key: string}>, dynamic: list<string>}
	 *
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	private static function calls(): array
	{
		if (self::$calls !== null) {
			return self::$calls;
		}

		$literals = [];
		$prefixes = [];
		$dynamic = [];

		foreach (self::sourceFiles('.php') as $location => $path) {
			$contents = file_get_contents($path);

			if (!is_string($contents)) {
				throw new RuntimeException(sprintf('Could not read %s', $path));
			}

			$tokens = [];

			foreach (PhpToken::tokenize($contents) as $token) {
				if (!$token->isIgnorable()) {
					$tokens[] = $token;
				}
			}

			foreach ($tokens as $i => $token) {
				if (
					!$token->is(T_STRING)
					|| $token->text !== 'translate'
					|| !isset($tokens[$i - 1], $tokens[$i + 1], $tokens[$i + 2], $tokens[$i + 3])
					|| !$tokens[$i - 1]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
					|| $tokens[$i + 1]->text !== '('
				) {
					continue;
				}

				$argument = $tokens[$i + 2];
				$next = $tokens[$i + 3];
				$where = sprintf('%s:%d', $location, $argument->line);

				if ($argument->is(T_CONSTANT_ENCAPSED_STRING)) {
					// The scan finds no escape sequence in any key, so stripping the quotes is enough
					$key = substr($argument->text, 1, -1);

					if ($next->text === '.') {
						// '//domain.some.prefix.' . $value->value
						$prefixes[] = ['location' => $where, 'key' => $key];
					} else {
						$literals[] = ['location' => $where, 'key' => $key];
					}
				} elseif ($argument->text === '"' && $next->is(T_ENCAPSED_AND_WHITESPACE)) {
					// "//domain.some.prefix.{$value}"
					$prefixes[] = ['location' => $where, 'key' => $next->text];
				} else {
					$dynamic[] = sprintf('%s %s', $where, $argument->text);
				}
			}
		}

		self::$calls = ['literals' => $literals, 'prefixes' => $prefixes, 'dynamic' => $dynamic];

		return self::$calls;
	}

	/**
	 * Flattened translation keys, by domain and lower-cased locale.
	 *
	 * @return array<string, array<string, array<string, true>>>
	 *
	 * @throws Neon\Exception
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	private static function catalogues(): array
	{
		if (self::$catalogues !== null) {
			return self::$catalogues;
		}

		$catalogues = [];

		foreach (self::sourceFiles('.neon') as $location => $path) {
			if (basename(dirname($path)) !== 'Translations') {
				continue;
			}

			// <domain>.<locale>.neon
			$parts = explode('.', basename($path));

			if (count($parts) !== 3) {
				throw new RuntimeException(sprintf('Translation file %s is not named <domain>.<locale>.neon', $location));
			}

			$data = Neon\Neon::decodeFile($path);

			if (!is_array($data)) {
				throw new RuntimeException(sprintf('Translation file %s does not decode to a map', $location));
			}

			$catalogues[$parts[0]][strtolower($parts[1])] = self::flatten($data);
		}

		self::$catalogues = $catalogues;

		return self::$catalogues;
	}

	/**
	 * The same flattening the translator's array loader applies: nested maps join with dots.
	 *
	 * @param array<mixed> $data
	 *
	 * @return array<string, true>
	 */
	private static function flatten(array $data, string $prefix = ''): array
	{
		$keys = [];

		foreach ($data as $name => $value) {
			$key = $prefix . $name;

			if (is_array($value)) {
				$keys += self::flatten($value, $key . '.');
			} else {
				$keys[$key] = true;
			}
		}

		return $keys;
	}

	/**
	 * Every file with the given suffix under src/FastyBird/<Type>/<Name>/src, keyed by its path
	 * relative to the repository root.
	 *
	 * @return array<string, string>
	 *
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	private static function sourceFiles(string $suffix): array
	{
		$root = realpath(__DIR__ . '/../../..');
		$directories = glob(__DIR__ . '/../../../src/FastyBird/*/*/src', GLOB_ONLYDIR);

		if ($root === false || $directories === false || $directories === []) {
			throw new RuntimeException('Could not find the package source directories');
		}

		$files = [];

		foreach ($directories as $directory) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			);

			foreach ($iterator as $file) {
				if (!$file instanceof SplFileInfo || !str_ends_with($file->getFilename(), $suffix)) {
					continue;
				}

				$path = $file->getRealPath();

				if ($path === false || !str_starts_with($path, $root . '/')) {
					continue;
				}

				$files[substr($path, strlen($root) + 1)] = $path;
			}
		}

		return $files;
	}

}
