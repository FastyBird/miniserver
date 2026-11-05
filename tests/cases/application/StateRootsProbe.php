<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use ArrayObject;
use Composer\Autoload\ClassLoader;
use DateTime;
use DateTimeImmutable;
use FastyBird\Bridge\RedisDbPluginDevicesModule\States as DevicesBridgeStates;
use FastyBird\Bridge\RedisDbPluginTriggersModule\States as TriggersBridgeStates;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\CouchDb\States as CouchDbStates;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use FilesystemIterator;
use Generator;
use JsonException;
use Nette\Utils;
use Orisai\ObjectMapper\MappedObject;
use Ramsey\Uuid\Uuid;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use stdClass;
use Throwable;
use TypeError;
use UnexpectedValueException;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function getopt;
use function interface_exists;
use function is_array;
use function is_dir;
use function is_subclass_of;
use function json_encode;
use function ksort;
use function reset;
use function rtrim;
use function serialize;
use function sort;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtok;
use function substr;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * What bootstrap-state-roots.php does in the child process: the inputs, the states, the report. The states are the
 * classes that extend the RedisDb plugin's State, the ones the Devices module's property states are implemented by and
 * the CouchDb plugin's State, in every package (#724 PR4d)
 */
final class StateRootsProbe
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string DATE = '2020-04-01T12:00:00+00:00';

	private const string OTHER_DATE = '2021-11-30T23:59:59+02:00';

	/**
	 * @throws JsonException
	 * @throws UnexpectedValueException
	 */
	public static function run(): int
	{
		$options = getopt('', ['messages', 'census', 'objects']);
		$withMessages = is_array($options) && array_key_exists('messages', $options);
		$loader = ClassLoader::getRegisteredLoaders();
		$loader = reset($loader);

		if ($loader === false) {
			throw new UnexpectedValueException('No class loader is registered');
		}

		// With --census: no container, only which side of Mapper::map()'s dispatch every state is on
		if (is_array($options) && array_key_exists('census', $options)) {
			$census = [];

			foreach (self::findStates($loader) as $class) {
				foreach (
					[
						'redisdb' => RedisDbStates\State::class,
						'devices' => DevicesStates\Property::class,
						'couchdb' => CouchDbStates\State::class,
					] as $family => $root
				) {
					if ($class === $root || is_subclass_of($class, $root)) {
						$census[$family][$class] = is_subclass_of($class, MappedObject::class);
					}
				}
			}

			echo json_encode($census, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

			return 0;
		}

		try {
			$configurator = Boot\Bootstrap::boot();

			// See bootstrap-production-scope.php: the Vite manifest is a frontend artefact a checkout does not have
			$configurator->addConfig([
				'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
			]);

			$container = $configurator->createContainer();
			$mapper = $container->getByType(CoreDocuments\Mapping\Mapper::class);
			$factory = new RedisDbStates\StateFactory($mapper);

			if (is_array($options) && array_key_exists('objects', $options)) {
				echo json_encode(
					self::objects($mapper),
					JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
				), PHP_EOL;

				return 0;
			}

			$report = [];

			foreach (self::findStates($loader) as $class) {
				if (
					!is_subclass_of($class, RedisDbStates\State::class)
					|| (new ReflectionClass($class))->isAbstract()
				) {
					continue;
				}

				foreach (self::cases($class) as $name => $input) {
					$report[$class][$name] = self::map($factory, $mapper, $class, $input, $withMessages);
				}
			}

			ksort($report);

			echo json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), PHP_EOL;

			return 0;
		} catch (Throwable $ex) {
			echo json_encode(['error' => $ex::class . ': ' . $ex->getMessage()]), PHP_EOL;

			return 1;
		}
	}

	/**
	 * What an input makes of a state: what Redis gives the factory is JSON text, what the property managers hand
	 * the mapper is an array that can hold objects
	 *
	 * @param class-string<RedisDbStates\State> $class
	 * @param array{string, array<string, mixed>} $input
	 *
	 * @return array<string, mixed>
	 *
	 * @throws JsonException
	 */
	private static function map(
		RedisDbStates\StateFactory $factory,
		CoreDocuments\Mapping\Mapper $mapper,
		string $class,
		array $input,
		bool $withMessages,
	): array
	{
		[$kind, $data] = $input;

		try {
			$state = $kind === 'json'
				? $factory->create($class, Utils\Json::encode($data))
				: $mapper->map($data, $class, new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: true));

			return ['json' => Utils\Json::encode($state->toArray()), 'serialized' => serialize($state)];
		} catch (CoreDocuments\Exceptions\MappingFailed | RedisDbExceptions\InvalidArgument $ex) {
			// What a refused input throws; any other exception is a defect and ends the run
			return $withMessages
				? ['refused' => true, 'message' => (string) strtok($ex->getMessage(), "\n")]
				: ['refused' => true];
		}
	}

	/**
	 * What a few objects make where orisai took any object (ObjectValue) and Valinor takes the objects of the types
	 * the fields declare: the dates and the payload cases are taken by both, an object of another class is not
	 *
	 * @return array<string, array<string, array<string, string>>>
	 *
	 * @throws JsonException
	 */
	private static function objects(CoreDocuments\Mapping\Mapper $mapper): array
	{
		$report = [];
		$options = new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: true);
		$stored = ['id' => self::ID];

		foreach (
			[
				DevicesBridgeStates\Property::class => ['actual_value', 'expected_value', 'pending', 'created_at', 'updated_at'],
				TriggersBridgeStates\Action::class => ['created_at', 'updated_at'],
				TriggersBridgeStates\Condition::class => ['created_at', 'updated_at'],
			] as $class => $fields
		) {
			foreach ($fields as $field) {
				foreach (
					[
						'a DateTimeImmutable' => new DateTimeImmutable(self::DATE),
						'a DateTime' => new DateTime(self::OTHER_DATE),
						'a stdClass' => new stdClass(),
						'an ArrayObject' => new ArrayObject([1]),
						'a Generator' => (static function (): Generator {
							yield 1;
						})(),
					] as $name => $object
				) {
					try {
						$state = $mapper->map([...$stored, $field => $object], $class, $options);

						$report[$class][$field . ' as ' . $name] = ['json' => Utils\Json::encode($state->toArray())];
					} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
						$report[$class][$field . ' as ' . $name] = ['refused' => (string) strtok(
							$ex->getMessage(),
							"\n",
						)];
					} catch (TypeError) {
						// orisai let any object through and the state's property then refused it, with an error
						$report[$class][$field . ' as ' . $name] = ['error' => 'TypeError'];
					}
				}
			}
		}

		foreach (['actual_value', 'expected_value'] as $field) {
			foreach (
				[
					'a Button' => Payloads\Button::PRESSED,
					'a Switcher' => Payloads\Switcher::ON,
					'a Cover' => Payloads\Cover::STOP,
				] as $name => $payload
			) {
				$state = $mapper->map([...$stored, $field => $payload], DevicesBridgeStates\Property::class, $options);

				$report[DevicesBridgeStates\Property::class][$field . ' as ' . $name] = [
					'json' => Utils\Json::encode($state->toArray()),
				];
			}
		}

		return $report;
	}

	/**
	 * @param class-string<RedisDbStates\State> $class
	 *
	 * @return array<string, array{string, array<string, mixed>}>
	 */
	private static function cases(string $class): array
	{
		if ($class === DevicesBridgeStates\Property::class) {
			return self::propertyCases();
		}

		if ($class === TriggersBridgeStates\Action::class || $class === TriggersBridgeStates\Condition::class) {
			return self::triggerCases();
		}

		// The three states that only extend the generic one, and the dummies of the packages' tests when they are found
		$all = self::propertyCases();

		return [
			'the fields of a stored one' => $all['the fields of a stored one'],
			'every field' => $all['every field'],
			'blank and null fields' => $all['blank and null fields'],
			'the valid flag as 1' => $all['valid as 1'],
			'the valid flag as a text' => $all['valid as "TRUE"'],
			'objects' => $all['the dates as objects, the payloads as cases'],
			'no id' => $all['no id'],
		];
	}

	/**
	 * @return array<string, array{string, array<string, mixed>}>
	 */
	private static function propertyCases(): array
	{
		$stored = [
			'id' => self::ID,
			'actual_value' => null,
			'expected_value' => null,
			'pending' => false,
			'valid' => false,
			'created_at' => null,
			'updated_at' => null,
		];

		$cases = [
			'the fields of a stored one' => ['json', $stored],
			'the required field' => ['json', ['id' => self::ID]],
			'every field' => [
				'json',
				array_merge($stored, [
					'actual_value' => 'on',
					'expected_value' => 5.5,
					'pending' => self::DATE,
					'valid' => true,
					'created_at' => self::DATE,
					'updated_at' => self::OTHER_DATE,
				]),
			],
			'blank and null fields' => [
				'json',
				array_merge(
					$stored,
					['actual_value' => '', 'expected_value' => " \t", 'created_at' => '', 'updated_at' => ' '],
				),
			],
			'unknown fields' => ['json', array_merge($stored, ['source' => 'x', 'x' => [1], 'actual_value' => 3])],
			'a no-break space as a value' => ['json', array_merge($stored, ['actual_value' => "\u{00A0}"])],
			'the dates as objects, the payloads as cases' => [
				'array',
				array_merge($stored, [
					'actual_value' => Payloads\Switcher::ON,
					'expected_value' => new DateTimeImmutable(self::DATE),
					'pending' => new DateTime(self::OTHER_DATE),
					'created_at' => new DateTime(self::DATE),
					'updated_at' => new DateTimeImmutable(self::OTHER_DATE),
				]),
			],
			'the values as objects of the other types' => [
				'array',
				array_merge($stored, [
					'actual_value' => Payloads\Button::PRESSED,
					'expected_value' => Payloads\Cover::OPEN,
					'pending' => new DateTimeImmutable(self::DATE),
				]),
			],
			'a UUID object' => ['array', array_merge($stored, ['id' => Uuid::fromString(self::ID)])],
			'no id' => ['json', ['actual_value' => 1]],
			'an id which is not a UUID' => ['json', array_merge($stored, ['id' => 'x'])],
			'an id of a UUID without dashes' => ['json', array_merge(
				$stored,
				['id' => '176984ad7cf7465d9e5371668a74a688'],
			)],
			'an id which is a number' => ['json', array_merge($stored, ['id' => 5])],
		];

		$values = [
			'true' => true,
			'false' => false,
			'0' => 0,
			'5' => 5,
			'-50' => -50,
			'5.0' => 5.0,
			'5.5' => 5.5,
			'"text"' => 'text',
			'"5"' => '5',
			'"true"' => 'true',
			'" padded "' => ' padded ',
			'"switch_on"' => 'switch_on',
			'"btn_pressed"' => 'btn_pressed',
			'"cover_open"' => 'cover_open',
			'a date text' => self::DATE,
			'a date text with an offset' => self::OTHER_DATE,
			'a date text in another format' => '2020-04-01 12:00:00',
			'"N/A"' => 'N/A',
			'null' => null,
			'""' => '',
			'" "' => '   ',
			'"\f"' => "\f",
			'a list' => [1],
			'an empty list' => [],
			'a map' => ['a' => 1],
		];

		foreach (['actual_value', 'expected_value'] as $field) {
			foreach ($values as $name => $value) {
				$cases[$field . ' is ' . $name] = ['json', array_merge($stored, [$field => $value])];
			}
		}

		foreach (
			[
				'false' => false,
				'true' => true,
				'a date text' => self::DATE,
				'a date text with an offset' => self::OTHER_DATE,
				'a date text in another format' => '2020-04-01 12:00:00',
				'an empty text' => '',
				'a text' => 'true',
				'1' => 1,
				'0' => 0,
				'null' => null,
				'a list' => [1],
				'a timestamp' => 1_585_742_400,
			] as $name => $value
		) {
			$cases['pending is ' . $name] = ['json', array_merge($stored, ['pending' => $value])];
		}

		// castBoolLike: what BoolValue(castBoolLike: true) took for a bool, and what it did not
		foreach (
			[
				'true' => true,
				'false' => false,
				'1' => 1,
				'0' => 0,
				'"1"' => '1',
				'"0"' => '0',
				'"true"' => 'true',
				'"false"' => 'false',
				'"TRUE"' => 'TRUE',
				'"False"' => 'False',
				'"yes"' => 'yes',
				'"no"' => 'no',
				'"on"' => 'on',
				'"off"' => 'off',
				'"01"' => '01',
				'" 1"' => ' 1',
				'"1 "' => '1 ',
				'"-1"' => '-1',
				'"2"' => '2',
				'2' => 2,
				'-1' => -1,
				'1.0' => 1.0,
				'0.0' => 0.0,
				'""' => '',
				'null' => null,
				'a list' => [1],
			] as $name => $value
		) {
			$cases['valid as ' . $name] = ['json', array_merge($stored, ['valid' => $value])];
		}

		foreach (
			[
				'null' => null,
				'an empty text' => '',
				'a blank text' => " \t",
				'a date' => self::DATE,
				'a date with an offset' => self::OTHER_DATE,
				'a day which the month has not' => '2020-02-31T12:00:00+00:00',
				'Zulu' => '2020-04-01T12:00:00Z',
				'fractions of a second' => '2020-04-01T12:00:00.123+00:00',
				'another format' => '2020-04-01 12:00:00',
				'a date without a time' => '2020-04-01',
				'a timestamp' => 1_585_742_400,
				'false' => false,
				'a list' => [1],
			] as $name => $value
		) {
			$cases['created_at is ' . $name] = ['json', array_merge($stored, ['created_at' => $value])];
			$cases['updated_at is ' . $name] = ['json', array_merge($stored, ['updated_at' => $value])];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{string, array<string, mixed>}>
	 */
	private static function triggerCases(): array
	{
		$stored = ['id' => self::ID, 'validation_result' => false, 'created_at' => null, 'updated_at' => null];

		$cases = [
			'the fields of a stored one' => ['json', $stored],
			'the required field' => ['json', ['id' => self::ID]],
			'every field' => [
				'json',
				['id' => self::ID, 'validation_result' => true, 'created_at' => self::DATE, 'updated_at' => self::OTHER_DATE],
			],
			'blank dates' => ['json', array_merge($stored, ['created_at' => '', 'updated_at' => ' '])],
			'unknown fields' => ['json', array_merge($stored, ['source' => 'x', 'validationResult' => true])],
			'no id' => ['json', ['validation_result' => true]],
			'an id which is not a UUID' => ['json', array_merge($stored, ['id' => 'x'])],
		];

		foreach (
			[
				'true' => true,
				'false' => false,
				'1' => 1,
				'0' => 0,
				'"1"' => '1',
				'"0"' => '0',
				'"true"' => 'true',
				'"false"' => 'false',
				'"TRUE"' => 'TRUE',
				'"False"' => 'False',
				'"yes"' => 'yes',
				'"01"' => '01',
				'"2"' => '2',
				'2' => 2,
				'1.0' => 1.0,
				'""' => '',
				'null' => null,
				'a list' => [1],
			] as $name => $value
		) {
			$cases['validation_result as ' . $name] = ['json', array_merge($stored, ['validation_result' => $value])];
		}

		foreach (['created_at', 'updated_at'] as $field) {
			foreach (
				[
					'an empty text' => '',
					'a date' => self::DATE,
					'a day which the month has not' => '2020-02-31T12:00:00+00:00',
					'another format' => '2020-04-01 12:00:00',
					'a timestamp' => 1_585_742_400,
					'a list' => [1],
				] as $name => $value
			) {
				$cases[$field . ' is ' . $name] = ['json', array_merge($stored, [$field => $value])];
			}
		}

		return $cases;
	}

	/**
	 * Every class of the packages that is a state of one of the three trees: it is found where the namespaces of the
	 * packages are, in a directory called States
	 *
	 * @return list<class-string>
	 *
	 * @throws UnexpectedValueException
	 */
	private static function findStates(ClassLoader $loader): array
	{
		$found = [];

		foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
			if (!str_starts_with($prefix, 'FastyBird\\') || str_contains($prefix, '\\Tests\\')) {
				continue;
			}

			foreach ($directories as $directory) {
				if (!is_dir($directory)) {
					continue;
				}

				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
				);

				foreach ($files as $file) {
					if (!$file instanceof SplFileInfo) {
						continue;
					}

					$path = $file->getPathname();

					if (!str_ends_with($path, '.php') || !str_contains($path, '/States/')) {
						continue;
					}

					$class = $prefix . str_replace('/', '\\', substr($path, strlen(rtrim($directory, '/')) + 1, -4));

					if (class_exists($class) || interface_exists($class)) {
						$found[] = $class;
					}
				}
			}
		}

		sort($found);

		return array_values(array_unique($found));
	}

}
