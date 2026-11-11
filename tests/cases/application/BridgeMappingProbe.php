<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Composer\Autoload\ClassLoader;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping as ShellyMapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping as VieraMapping;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents as CoreDocuments;
use FilesystemIterator;
use JsonException;
use Orisai\ObjectMapper\MappedObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;
use UnexpectedValueException;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function dirname;
use function file_get_contents;
use function getopt;
use function interface_exists;
use function is_array;
use function is_dir;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function json_encode;
use function ksort;
use function reset;
use function serialize;
use function sort;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtok;
use function strtolower;
use function strtoupper;
use function substr;
use const ARRAY_FILTER_USE_KEY;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * What bootstrap-bridge-mapping.php does in the child process: the inputs, the mapping classes, the report. The classes
 * are the ones of the two HomeKit bridges that read a mapping file of the connector's resources (Shelly, generations 1
 * and 2, and Viera): the mapping of a generation or of the services, the accessories and the services in it and the
 * characteristics of a service. Each is given the whole mapping file, and then every class a sample taken from the file
 * with its fields one at a time in every wrong form there is: missing, null, blank, a number, a list, a text, and the
 * forms of its own kind (a type of another service, an enum that does not exist, a list with keys, a format of the
 * shapes a property has)
 */
final class BridgeMappingProbe
{

	private const array ROOTS = [
		'shelly' => ShellyMapping\Mapping::class,
		'viera' => VieraMapping\Mapping::class,
	];

	/**
	 * @throws JsonException
	 * @throws UnexpectedValueException
	 */
	public static function run(): int
	{
		$options = getopt('', ['messages', 'census']);
		$withMessages = is_array($options) && array_key_exists('messages', $options);
		$loader = ClassLoader::getRegisteredLoaders();
		$loader = reset($loader);

		if ($loader === false) {
			throw new UnexpectedValueException('No class loader is registered');
		}

		// With --census: no container, only which side of Mapper::map()'s dispatch every class of the two trees is on
		if (is_array($options) && array_key_exists('census', $options)) {
			$census = [];

			foreach (self::find($loader) as $class) {
				foreach (self::ROOTS as $family => $root) {
					if (is_subclass_of($class, $root)) {
						$census[$family][$class] = is_subclass_of($class, MappedObject::class);
					}
				}
			}

			ksort($census);

			echo json_encode($census, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

			return 0;
		}

		try {
			$configurator = Boot\Bootstrap::boot();

			// See bootstrap-production-scope.php: the Vite manifest is a frontend artefact a checkout does not have
			$configurator->addConfig([
				'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
			]);

			$mapper = $configurator->createContainer()->getByType(CoreDocuments\Mapping\Mapper::class);
			$report = [];

			foreach (self::specs() as $class => $spec) {
				foreach (self::cases($spec) as $name => $data) {
					$report[$class][$name] = self::map($mapper, $class, $data, $withMessages);
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
	 * @param class-string $class
	 * @param array<mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private static function map(
		CoreDocuments\Mapping\Mapper $mapper,
		string $class,
		array $data,
		bool $withMessages,
	): array
	{
		try {
			/** @phpstan-ignore argument.type */
			$mapping = $mapper->map($data, $class, new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: true));

			return ['serialized' => serialize($mapping)];
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			return $withMessages
				? ['refused' => true, 'message' => (string) strtok($ex->getMessage(), "\n")]
				: ['refused' => true];
		} catch (Throwable $ex) {
			// A defect, not a refusal: an assertion of a constructor, a type error of a property
			return ['error' => $ex::class];
		}
	}

	/**
	 * @return list<class-string>
	 *
	 * @throws UnexpectedValueException
	 */
	private static function find(ClassLoader $loader): array
	{
		$found = [];

		foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
			if (!str_starts_with($prefix, 'FastyBird\\Bridge\\') || str_contains($prefix, '\\Tests\\')) {
				continue;
			}

			foreach ($directories as $directory) {
				if (!is_dir($directory . '/Mapping')) {
					continue;
				}

				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($directory . '/Mapping', FilesystemIterator::SKIP_DOTS),
				);

				foreach ($files as $file) {
					if (!$file instanceof SplFileInfo || !str_ends_with($file->getPathname(), '.php')) {
						continue;
					}

					$class = $prefix . 'Mapping\\' . str_replace(
						'/',
						'\\',
						substr($file->getPathname(), strlen($directory . '/Mapping/'), -4),
					);

					if (class_exists($class) || interface_exists($class)) {
						$found[] = $class;
					}
				}
			}
		}

		sort($found);

		return array_values(array_unique($found));
	}

	/**
	 * The mapping files of the connectors' resources
	 *
	 * @return array<string, mixed>
	 *
	 * @throws JsonException
	 */
	private static function resources(): array
	{
		$bridges = dirname(__DIR__, 3) . '/src/FastyBird/Bridge';
		$read = static function (string $path): mixed {
			$content = file_get_contents($path);

			return $content === false ? null : json_decode($content, true, 512, JSON_THROW_ON_ERROR);
		};

		return [
			'gen1' => $read($bridges . '/ShellyConnectorHomeKitConnector/resources/gen1.json'),
			'gen2' => $read($bridges . '/ShellyConnectorHomeKitConnector/resources/gen2.json'),
			'viera' => $read($bridges . '/VieraConnectorHomeKitConnector/resources/mapping.json'),
		];
	}

	/**
	 * Every class that is given inputs: a sample, taken from the mapping files, and the kind of each of its fields, which
	 * says which forms of a wrong value are worth giving it. 'all' is the whole of a mapping file, given as it is
	 *
	 * @return array<class-string, array{sample: array<string, mixed>, kinds: array<string, string>, others?: array<string, mixed>, light?: bool}>
	 *
	 * @throws JsonException
	 */
	private static function specs(): array
	{
		$files = self::resources();
		/** @var list<array<string, mixed>> $gen1 */
		$gen1 = $files['gen1'];
		/** @var list<array<string, mixed>> $gen2 */
		$gen2 = $files['gen2'];
		/** @var array{services: list<array<string, mixed>>} $viera */
		$viera = $files['viera'];

		$accessory = static function (array $list, string $type): array {
			foreach ($list as $item) {
				if ($item['type'] === $type) {
					return $item;
				}
			}

			throw new UnexpectedValueException('No accessory of the type ' . $type);
		};
		$service = static function (array $list, string $type): array {
			foreach ($list as $accessoryItem) {
				foreach ($accessoryItem['services'] as $serviceItem) {
					if ($serviceItem['type'] === $type) {
						return $serviceItem;
					}
				}
			}

			throw new UnexpectedValueException('No service of the type ' . $type);
		};
		$characteristic = static function (array $services, callable $match): array {
			foreach ($services as $serviceItem) {
				foreach ($serviceItem['characteristics'] as $item) {
					if ($match($item)) {
						return $item;
					}
				}
			}

			throw new UnexpectedValueException('No characteristic');
		};

		$shellyServices = [];

		foreach ($gen2 as $item) {
			foreach ($item['services'] as $serviceItem) {
				$shellyServices[] = $serviceItem;
			}
		}

		$accessoryKinds = [
			'type' => 'const',
			'models' => 'text list',
			'categories' => 'enum list',
			'services' => 'objects list',
		];
		$serviceKinds = [
			'type' => 'const',
			'class' => 'text',
			'category' => 'enum',
			'channel' => 'nullable text',
			'index_start' => 'index',
			'multiple' => 'bool like',
			'characteristics' => 'objects list',
		];
		$vieraServiceKinds = [
			'type' => 'const',
			'class' => 'text',
			'channel' => 'nullable text',
			'multiple' => 'bool like',
			'characteristics' => 'objects list',
		];
		$characteristicKinds = [
			'type' => 'enum',
			'channel' => 'nullable text',
			'property' => 'property',
			'nullable' => 'bool like',
			'require' => 'enum list',
			'format' => 'format',
			'value' => 'scalar',
		];

		$shellyCharacteristic = $characteristic(
			$shellyServices,
			static fn (array $item): bool => array_key_exists('format', $item) && array_key_exists('value', $item),
		);
		$vieraCharacteristic = $characteristic(
			$viera['services'],
			static fn (array $item): bool => ($item['property'] ?? null) !== null,
		);

		$specs = [
			ShellyMapping\Gen1::class => ['sample' => ['accessories' => $gen1], 'kinds' => ['accessories' => 'objects list'], 'light' => true],
			ShellyMapping\Gen2::class => ['sample' => ['accessories' => $gen2], 'kinds' => ['accessories' => 'objects list'], 'light' => true],
			ShellyMapping\Accessories\LightAccessory::class => [
				'sample' => $accessory($gen2, 'light'),
				'kinds' => $accessoryKinds,
				'others' => ['services' => $service($gen2, 'Switch')],
			],
			ShellyMapping\Accessories\RollerAccessory::class => [
				'sample' => $accessory($gen2, 'roller'),
				'kinds' => $accessoryKinds,
			],
			ShellyMapping\Accessories\SwitchAccessory::class => [
				'sample' => $accessory($gen2, 'switch'),
				'kinds' => $accessoryKinds,
			],
			ShellyMapping\Accessories\InputAccessory::class => [
				'sample' => $accessory($gen2, 'input'),
				'kinds' => $accessoryKinds,
			],
			ShellyMapping\Services\LightbulbService::class => ['sample' => $service($gen2, 'Lightbulb'), 'kinds' => $serviceKinds],
			ShellyMapping\Services\OutletService::class => ['sample' => $service($gen2, 'Outlet'), 'kinds' => $serviceKinds, 'light' => true],
			ShellyMapping\Services\StatelessProgrammableSwitch::class => [
				'sample' => $service($gen2, 'StatelessProgrammableSwitch'),
				'kinds' => $serviceKinds,
				'light' => true,
			],
			ShellyMapping\Services\SwitchService::class => ['sample' => $service($gen2, 'Switch'), 'kinds' => $serviceKinds, 'light' => true],
			ShellyMapping\Services\ValveService::class => ['sample' => $service($gen2, 'Valve'), 'kinds' => $serviceKinds, 'light' => true],
			ShellyMapping\Services\WindowCoveringService::class => [
				'sample' => $service($gen2, 'WindowCovering'),
				'kinds' => $serviceKinds,
				'light' => true,
			],
			ShellyMapping\Characteristics\BasicCharacteristic::class => [
				'sample' => $shellyCharacteristic,
				'kinds' => $characteristicKinds,
			],
			VieraMapping\Services::class => ['sample' => $viera, 'kinds' => ['services' => 'objects list'], 'light' => true],
		];

		foreach (
			[
				VieraMapping\Services\Television::class => 'Television',
				VieraMapping\Services\TelevisionSpeaker::class => 'TelevisionSpeaker',
				VieraMapping\Services\InputSource::class => 'InputSource',
			] as $class => $type
		) {
			$specs[$class] = [
				'sample' => $service([['services' => $viera['services']]], $type),
				'kinds' => $vieraServiceKinds,
			];
		}

		$specs[VieraMapping\Characteristics\BasicCharacteristic::class] = [
			'sample' => $vieraCharacteristic,
			'kinds' => [
				'type' => 'enum',
				'channel' => 'nullable text',
				'property' => 'nullable text',
				'nullable' => 'bool like',
				'require' => 'enum list',
				'format' => 'format',
				'value' => 'scalar',
			],
		];

		return $specs;
	}

	/**
	 * @param array{sample: array<string, mixed>, kinds: array<string, string>, others?: array<string, mixed>, light?: bool} $spec
	 *
	 * @return array<string, array<mixed>>
	 */
	private static function cases(array $spec): array
	{
		$sample = $spec['sample'];
		$cases = [
			'the sample' => $sample,
			'unknown fields' => array_merge($sample, ['source' => 'x', 'x' => [1]]),
			'nothing' => [],
		];

		foreach ($spec['kinds'] as $field => $kind) {
			$variants = array_merge(
				[
					'missing' => null,
					'null' => null,
					'an empty string' => '',
					'spaces' => " \t",
					'a no-break space' => "\u{00A0}",
					'zero' => 0,
					'one' => 1,
					'a float' => 1.5,
					'a numeric text' => '5',
					'true' => true,
					'false' => false,
					'a text' => 'x',
					'a text with spaces around' => ' x ',
					'a list' => ['x'],
					'an empty list' => [],
					'a list of a number' => [1],
				],
				self::variantsOf($kind, $sample[$field] ?? null, $spec['others'][$field] ?? null),
			);

			if (($spec['light'] ?? false) === true) {
				$variants = array_intersect_key(
					$variants,
					array_flip(['missing', 'null', 'an empty string', 'a text', 'a list', 'an empty list']),
				);
			}

			foreach ($variants as $label => $variant) {
				$data = $sample;

				if ($label === 'missing') {
					unset($data[$field]);
				} else {
					$data[$field] = $variant;
				}

				$cases[$field . ': ' . $label] = $data;
			}
		}

		return $cases;
	}

	/**
	 * Forms a value of this kind can be wrong in, or right in another way
	 *
	 * @return array<string, mixed>
	 */
	private static function variantsOf(string $kind, mixed $sample, mixed $other): array
	{
		$enums = is_array($sample) ? $sample : [];

		return match ($kind) {
			'const' => [
				'a lower case word' => is_string($sample) ? strtolower($sample) : 'x',
				'an upper case word' => is_string($sample) ? strtoupper($sample) : 'X',
				'a word of another type' => 'Other',
				'a word with a space' => ' ' . (is_string($sample) ? $sample : 'x'),
			],
			'enum', 'enum list' => $kind === 'enum'
				? [
					'a word that is no case' => 'Unknown',
					'a case in lower case' => is_string($sample) ? strtolower($sample) : 'x',
					'a case in upper case' => is_string($sample) ? strtoupper($sample) : 'X',
					'a number of a case' => 7,
				]
				: [
					'a list with one that is no case' => array_merge($enums, ['Unknown']),
					'a list with keys' => [3 => $enums[0] ?? 7, 8 => $enums[0] ?? 7],
					'a list with words for keys' => ['a' => $enums[0] ?? 7],
					'a list with a negative key' => [-1 => $enums[0] ?? 7],
					'a case, not a list' => $enums[0] ?? 7,
					'a list of lists' => [$enums],
					'the same case twice' => [$enums[0] ?? 7, $enums[0] ?? 7],
					'a list with a null' => array_merge($enums, [null]),
				],
			'text list' => [
				'a list with a blank' => array_merge($enums, [' ']),
				'a list with a number' => array_merge($enums, [5]),
				'a list with keys' => [3 => 'A', 7 => 'B'],
				'a list with words for keys' => ['a' => 'A'],
				'a list with a negative key' => [-1 => 'A'],
				'a text, not a list' => 'A',
				'a list with a null' => array_merge($enums, [null]),
			],
			'text', 'nullable text' => [
				'a text of one letter' => 'a',
				'a class without a leading backslash' => 'Some\\Class',
				'a number as a text' => '0',
			],
			'bool like' => [
				'the text true' => 'true',
				'the text false' => 'false',
				'the text TRUE' => 'TRUE',
				'the text 1' => '1',
				'the text 0' => '0',
				'the text 01' => '01',
				'the text yes' => 'yes',
				'the int 2' => 2,
				'the float 1.0' => 1.0,
			],
			'index' => [
				'a numeric text' => '1',
				'a numeric text with spaces' => ' 1 ',
				'a text of a float' => '1.5',
				'a text of a float with no decimals' => '2.0',
				'a text of a negative int' => '-1',
				'a float with no decimals' => 2.0,
				'a negative int' => -1,
				'a big int' => 9_007_199_254_740_993,
				'a text of a big number' => '99999999999999999999',
				'a text with a letter' => '1a',
			],
			'property' => [
				'a list of two' => ['_a', '_b'],
				'a list with a blank' => ['_a', ' '],
				'a list with a number' => ['_a', 5],
				'a list with keys' => [2 => '_a'],
				'a list with words for keys' => ['a' => '_a'],
				'a list of lists' => [['_a']],
			],
			'format' => [
				'a text' => 'a,b',
				'a list of texts' => ['a', 'b'],
				'a pair of numbers' => [0, 100],
				'a pair of floats' => [0.5, 1.5],
				'a pair with a null' => [0, null],
				'a pair of nulls' => [null, null],
				'a pair with a data type' => [['u8', 0], ['u8', 100]],
				'a pair with a bad data type' => [['x8', 0], null],
				'a pair with a text' => ['a', 'b'],
				'a pair of three' => [1, 2, 3],
				'rows of three' => [[1, 'a', ['b', 2]], [2, 'b', null]],
				'rows with a data type' => [[['btn', 'btn_clicked'], ['u8', 0], ['u8', 0]]],
				'rows of two' => [[1, 2]],
				'rows with a bool' => [[['b', true], ['u8', 0], ['u8', 0]]],
				'rows with a bad data type' => [[['zz', 'a'], ['u8', 0], ['u8', 0]]],
				'a list with keys' => [3 => 'a'],
				'a list with words for keys' => ['a' => 1, 'b' => 2],
				'a list with a bool' => [true, false],
				'a list of lists of one' => [[1], [2]],
			],
			'scalar' => [
				'a negative int' => -5,
				'a big float' => 1.0E+25,
				'a text of a number' => '21.5',
				'a text with spaces' => ' x ',
			],
			'objects list' => self::objectsOf($sample, $other),
			default => [],
		};
	}

	/**
	 * A list of objects in every shape: the items, one that lacks a field, one of another kind, keys, a text for an item
	 *
	 * @return array<string, mixed>
	 */
	private static function objectsOf(mixed $sample, mixed $other): array
	{
		$items = is_array($sample) ? $sample : [];
		$first = $items[0] ?? [];
		$without = is_array($first)
			? array_filter(
				$first,
				static fn (string|int $key): bool => $key !== 'type',
				ARRAY_FILTER_USE_KEY,
			)
			: [];
		$forms = [
			'the items twice' => array_merge($items, $items),
			'only the first' => [$first],
			'the items with keys' => [3 => $first, 8 => $first],
			'the items with words for keys' => ['a' => $first],
			'the items with a negative key' => [-1 => $first],
			'an item without a type' => [$without],
			'an item that is a text' => ['x'],
			'an item that is null' => [null],
			'an item that is a list' => [[]],
			'an item with an unknown field' => [is_array($first) ? array_merge($first, ['more' => 1]) : $first],
			'a single item, not a list' => $first,
		];

		if (is_array($other)) {
			$forms['an item of another kind'] = [$other];
			$forms['the items and one of another kind'] = array_merge($items, [$other]);
		}

		return $forms;
	}

}
