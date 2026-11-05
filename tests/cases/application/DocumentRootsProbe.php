<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use ArrayIterator;
use ArrayObject;
use Composer\Autoload\ClassLoader;
use DateTime;
use DateTimeImmutable;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exceptions\InvalidArgument;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Types\ChannelCategory;
use FastyBird\Module\Devices\Types\ConnectorCategory;
use FastyBird\Module\Devices\Types\DeviceCategory;
use FilesystemIterator;
use JsonException;
use Nette\Utils;
use Orisai\ObjectMapper\MappedObject;
use Ramsey\Uuid;
use Ramsey\Uuid\Exception as UuidExceptions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;
use UnexpectedValueException;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function dirname;
use function getopt;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
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
 * What bootstrap-document-roots.php does in the child process: the inputs, the documents, the report
 */
final class DocumentRootsProbe
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string SIBLING = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';

	private const string CONNECTOR = '0c1d6b77-d1a6-4f55-8d2c-7b7bd2d3c9a1';

	private const string CHILD = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';

	private const string OWNER = 'c5b8a0d2-0a77-4f4b-9d0a-9f1fe3b8e2aa';

	private const array GENERIC = [
		DevicesDocuments\Connectors\Generic::class,
		DevicesDocuments\Devices\Generic::class,
		DevicesDocuments\Channels\Generic::class,
	];

	/**
	 * @throws JsonException
	 * @throws UnexpectedValueException
	 * @throws UuidExceptions\InvalidArgumentException
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

		// With --census: no container, only which side of Mapper::map()'s dispatch every document of the three roots is on
		if (is_array($options) && array_key_exists('census', $options)) {
			$census = [];

			foreach (self::findDocuments($loader, dirname(__DIR__, 3)) as $class) {
				foreach (
					[
						'connector' => DevicesDocuments\Connectors\Connector::class,
						'device' => DevicesDocuments\Devices\Device::class,
						'channel' => DevicesDocuments\Channels\Channel::class,
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

		$objectsOnly = is_array($options) && array_key_exists('objects', $options);

		try {
			$configurator = Boot\Bootstrap::boot();

			// See bootstrap-production-scope.php: the Vite manifest is a frontend artefact a checkout does not have
			$configurator->addConfig([
				'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
			]);

			$container = $configurator->createContainer();
			$factory = $container->getByType(CoreDocuments\DocumentFactory::class);

			if ($objectsOnly) {
				echo json_encode(
					self::objects($factory),
					JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
				), PHP_EOL;

				return 0;
			}

			$report = [];

			foreach (
				[
					DevicesDocuments\Connectors\Connector::class => self::connectorCases(),
					DevicesDocuments\Devices\Device::class => self::deviceCases(),
					DevicesDocuments\Channels\Channel::class => self::channelCases(),
				] as $root => $cases
			) {
				$classes = array_filter(
					self::findDocuments($loader, dirname(__DIR__, 3)),
					static fn (string $class): bool => is_subclass_of($class, $root) && !(new ReflectionClass(
						$class,
					))->isAbstract(),
				);

				foreach ($classes as $class) {
					// Every input for the generic document of the module, a few for every document that extends the root
					$wanted = in_array($class, self::GENERIC, true)
						? array_keys($cases)
						: [
							'the fields of a stored one',
							'every field',
							'blank and null fields',
							'as JSON',
							'a UUID and an enum as objects',
							'lists of UUIDs with keys',
							'lists of UUIDs with words for keys',
							'no identifier',
							'an unknown category',
						];

					foreach ($wanted as $name) {
						$data = $cases[$name];
						$type = $class::getType();

						if (is_array($data)) {
							$data['type'] = $type;
						} elseif (is_string($data)) {
							$decoded = Utils\Json::decode($data, forceArrays: true);
							$data = Utils\Json::encode([...(is_array($decoded) ? $decoded : []), 'type' => $type]);
						} else {
							$data = Utils\ArrayHash::from([...(array) $data, 'type' => $type]);
						}

						try {
							$document = $factory->create($class, $data);

							$report[$class][$name] = [
								'json' => Utils\Json::encode($document->toArray()),
								'serialized' => serialize($document),
							];
						} catch (InvalidArgument $ex) {
							// What the factory throws for a refused input; any other exception is a defect and ends the run
							$report[$class][$name] = $withMessages
								? ['refused' => true, 'message' => (string) strtok($ex->getMessage(), "\n")]
								: ['refused' => true];
						}
					}
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
	 * @return list<class-string>
	 *
	 * @throws UnexpectedValueException
	 */
	private static function findDocuments(ClassLoader $loader, string $root): array
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

					if (!str_ends_with($path, '.php') || !str_contains($path, '/Documents/')) {
						continue;
					}

					$class = $prefix . str_replace('/', '\\', substr($path, strlen(rtrim($directory, '/')) + 1, -4));

					if (class_exists($class)) {
						$found[] = $class;
					}
				}
			}
		}

		sort($found);

		return array_values(array_unique($found));
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private static function without(array $data, string ...$keys): array
	{
		foreach ($keys as $key) {
			unset($data[$key]);
		}

		return $data;
	}

	/**
	 * What a document of each of the three roots makes of an object where its scalar or array form is expected, one
	 * thing at a time: a date object for a date, an iterator or an ArrayObject for a list of UUIDs. orisai refused all of
	 * these; Valinor takes the object of the type it was given or any traversable for a list. Nothing in the
	 * application builds a document from such a value, the report documents what happens if something does
	 *
	 * @return array<string, array<string, array<string, string>>>
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws JsonException
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function objects(CoreDocuments\DocumentFactory $factory): array
	{
		$report = [];

		foreach (
			[
				DevicesDocuments\Connectors\Generic::class => [self::connectorCases(), ['properties', 'controls', 'devices']],
				DevicesDocuments\Devices\Generic::class => [
					self::deviceCases(),
					['parents', 'children', 'properties', 'controls', 'channels'],
				],
				DevicesDocuments\Channels\Generic::class => [self::channelCases(), ['properties', 'controls']],
			] as $class => [$cases, $lists]
		) {
			$stored = $cases['the fields of a stored one'];

			if (!is_array($stored)) {
				continue;
			}

			$inputs = [
				'created_at as a DateTimeImmutable' => ['created_at' => new DateTimeImmutable(
					'2020-04-01T12:00:00+00:00',
				)],
				'updated_at as a DateTime' => ['updated_at' => new DateTime('2021-11-30T23:59:59+02:00')],
			];

			foreach ($lists as $list) {
				$inputs[$list . ' as an ArrayObject'] = [$list => new ArrayObject([self::CHILD])];
				$inputs[$list . ' as an ArrayIterator'] = [$list => new ArrayIterator([self::CHILD, self::OWNER])];
			}

			foreach ($inputs as $name => $change) {
				try {
					$document = $factory->create($class, [...$stored, ...$change, 'type' => $class::getType()]);

					$report[$class][$name] = ['json' => Utils\Json::encode($document->toArray())];
				} catch (InvalidArgument $ex) {
					$report[$class][$name] = ['refused' => (string) strtok($ex->getMessage(), "\n")];
				}
			}
		}

		return $report;
	}

	/**
	 * @return array<string, array<string, mixed>|string|object>
	 *
	 * @throws Utils\JsonException
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function channelCases(): array
	{
		$stored = [
			'id' => self::ID,
			'category' => 'generic',
			'identifier' => 'channel-identifier',
			'device' => self::CONNECTOR,
			'name' => null,
			'comment' => null,
			'properties' => [],
			'controls' => [],
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
		];
		$full = array_merge($stored, [
			'name' => 'A name',
			'comment' => 'A comment',
			'properties' => [self::CHILD, self::OWNER],
			'controls' => [self::SIBLING],
			'owner' => self::OWNER,
			'created_at' => '2020-04-01T12:00:00+00:00',
			'updated_at' => '2021-11-30T23:59:59+02:00',
		]);

		return [
			'the required fields' => [
				'id' => self::ID,
				'category' => 'generic',
				'identifier' => 'required',
				'device' => self::CONNECTOR,
			],
			'the fields of a stored one' => $stored,
			'every field' => $full,
			'blank and null fields' => array_merge($stored, ['name' => '', 'comment' => " \t", 'owner' => ' ']),
			'an empty owner' => array_merge($stored, ['owner' => '']),
			'a name with spaces around and a no-break space' => array_merge(
				$stored,
				['name' => ' a ', 'comment' => "\u{00A0}"],
			),
			'unknown fields' => array_merge($stored, ['source' => 'x', 'x' => [1]]),
			'as JSON' => Utils\Json::encode($full),
			'as an object' => Utils\ArrayHash::from($full),
			'a UUID and an enum as objects' => array_merge($stored, [
				'id' => Uuid\Uuid::fromString(self::ID),
				'category' => ChannelCategory::GENERIC,
				'device' => Uuid\Uuid::fromString(self::CONNECTOR),
				'owner' => Uuid\Uuid::fromString(self::OWNER),
				'properties' => [Uuid\Uuid::fromString(self::CHILD)],
			]),
			'lists of UUIDs with keys' => array_merge(
				$stored,
				['properties' => [3 => self::CHILD], 'controls' => [7 => self::SIBLING, 2 => self::OWNER]],
			),
			'lists of UUIDs with words for keys' => array_merge(
				$stored,
				['properties' => ['a' => self::CHILD], 'controls' => ['b' => self::SIBLING]],
			),
			'a day which the month has not' => array_merge($stored, ['created_at' => '2020-02-31T12:00:00+00:00']),
			'dates in Zulu and without a colon' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00Z', 'updated_at' => '2020-04-01T12:00:00+0000'],
			),
			'a date with fractions of a second' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00.123+00:00'],
			),
			'a date as a timestamp' => array_merge($stored, ['created_at' => 1_585_742_400]),
			'an empty date' => array_merge($stored, ['updated_at' => '']),
			'without name' => self::without($stored, 'name'),
			'without comment' => self::without($stored, 'comment'),
			'without properties and controls' => self::without($stored, 'properties', 'controls'),
			'without owner and dates' => self::without($stored, 'owner', 'created_at', 'updated_at'),
			'no id' => self::without($stored, 'id'),
			'an id which is not a UUID' => array_merge($stored, ['id' => 'x']),
			'an id of a UUID without dashes' => array_merge($stored, ['id' => '176984ad7cf7465d9e5371668a74a688']),
			'no device' => self::without($stored, 'device'),
			'a device which is not a UUID' => array_merge($stored, ['device' => 'x']),
			'a device which is null' => array_merge($stored, ['device' => null]),
			'no category' => self::without($stored, 'category'),
			'an unknown category' => array_merge($stored, ['category' => 'special']),
			'a category which is a number' => array_merge($stored, ['category' => 1]),
			'no identifier' => self::without($stored, 'identifier'),
			'a blank identifier' => array_merge($stored, ['identifier' => " \t"]),
			'an empty identifier' => array_merge($stored, ['identifier' => '']),
			'an identifier which is a number' => array_merge($stored, ['identifier' => 42]),
			'a name which is a number' => array_merge($stored, ['name' => 42]),
			'a comment which is a list' => array_merge($stored, ['comment' => ['x']]),
			'an owner which is not a UUID' => array_merge($stored, ['owner' => 'somebody']),
			'a property which is not a UUID' => array_merge($stored, ['properties' => ['x']]),
			'properties which are a text' => array_merge($stored, ['properties' => self::CHILD]),
			'a control which is a number' => array_merge($stored, ['controls' => [1]]),
			'a control which is null' => array_merge($stored, ['controls' => [null]]),
			'a date in another format' => array_merge($stored, ['created_at' => '2020-04-01 12:00:00']),
		];
	}

	/**
	 * @return array<string, array<string, mixed>|string|object>
	 *
	 * @throws Utils\JsonException
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function connectorCases(): array
	{
		$stored = [
			'id' => self::ID,
			'category' => 'generic',
			'identifier' => 'connector-identifier',
			'name' => null,
			'comment' => null,
			'enabled' => true,
			'properties' => [],
			'controls' => [],
			'devices' => [],
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
		];
		$full = array_merge($stored, [
			'name' => 'A name',
			'comment' => 'A comment',
			'enabled' => false,
			'properties' => [self::CHILD, self::OWNER],
			'controls' => [self::SIBLING],
			'devices' => [self::CONNECTOR],
			'owner' => self::OWNER,
			'created_at' => '2020-04-01T12:00:00+00:00',
			'updated_at' => '2021-11-30T23:59:59+02:00',
		]);

		return [
			'the required fields' => ['id' => self::ID, 'category' => 'generic', 'identifier' => 'required'],
			'the fields of a stored one' => $stored,
			'every field' => $full,
			'blank and null fields' => array_merge($stored, ['name' => '', 'comment' => " \t", 'owner' => ' ']),
			'an empty owner' => array_merge($stored, ['owner' => '']),
			'a name with spaces around and a no-break space' => array_merge(
				$stored,
				['name' => ' a ', 'comment' => "\u{00A0}"],
			),
			'unknown fields' => array_merge(
				$stored,
				['source' => 'x', 'x' => [1], 'created_at' => '2020-04-01T12:00:00+00:00'],
			),
			'as JSON' => Utils\Json::encode($full),
			'as an object' => Utils\ArrayHash::from($full),
			'a UUID and an enum as objects' => array_merge($stored, [
				'id' => Uuid\Uuid::fromString(self::ID),
				'category' => ConnectorCategory::GENERIC,
				'owner' => Uuid\Uuid::fromString(self::OWNER),
				'properties' => [Uuid\Uuid::fromString(self::CHILD)],
			]),
			'lists of UUIDs with keys' => array_merge(
				$stored,
				['properties' => [3 => self::CHILD], 'controls' => [7 => self::SIBLING, 2 => self::OWNER]],
			),
			'lists of UUIDs with words for keys' => array_merge(
				$stored,
				['properties' => ['a' => self::CHILD], 'controls' => ['b' => self::SIBLING], 'devices' => ['c' => self::OWNER]],
			),
			'a day which the month has not' => array_merge($stored, ['created_at' => '2020-02-31T12:00:00+00:00']),
			'dates in Zulu and without a colon' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00Z', 'updated_at' => '2020-04-01T12:00:00+0000'],
			),
			'a date with fractions of a second' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00.123+00:00'],
			),
			'a date without a time' => array_merge($stored, ['updated_at' => '2020-04-01']),
			'a date as a timestamp' => array_merge($stored, ['created_at' => 1_585_742_400]),
			'an empty date' => array_merge($stored, ['created_at' => '']),
			'without name' => self::without($stored, 'name'),
			'without comment' => self::without($stored, 'comment'),
			'without enabled' => self::without($stored, 'enabled'),
			'without properties, controls and devices' => self::without($stored, 'properties', 'controls', 'devices'),
			'without owner and dates' => self::without($stored, 'owner', 'created_at', 'updated_at'),
			'enabled as an int' => array_merge($stored, ['enabled' => 1]),
			'enabled as a text' => array_merge($stored, ['enabled' => 'true']),
			'enabled as null' => array_merge($stored, ['enabled' => null]),
			'no id' => self::without($stored, 'id'),
			'an id which is not a UUID' => array_merge($stored, ['id' => 'x']),
			'an id of a UUID without dashes' => array_merge($stored, ['id' => '176984ad7cf7465d9e5371668a74a688']),
			'no category' => self::without($stored, 'category'),
			'an unknown category' => array_merge($stored, ['category' => 'special']),
			'a category which is a number' => array_merge($stored, ['category' => 1]),
			'no identifier' => self::without($stored, 'identifier'),
			'a blank identifier' => array_merge($stored, ['identifier' => " \t"]),
			'an empty identifier' => array_merge($stored, ['identifier' => '']),
			'an identifier which is a number' => array_merge($stored, ['identifier' => 42]),
			'a name which is a number' => array_merge($stored, ['name' => 42]),
			'a comment which is a list' => array_merge($stored, ['comment' => ['x']]),
			'an owner which is not a UUID' => array_merge($stored, ['owner' => 'somebody']),
			'a property which is not a UUID' => array_merge($stored, ['properties' => ['x']]),
			'properties which are a text' => array_merge($stored, ['properties' => self::CHILD]),
			'a control which is a number' => array_merge($stored, ['controls' => [1]]),
			'a device which is null' => array_merge($stored, ['devices' => [null]]),
		];
	}

	/**
	 * @return array<string, array<string, mixed>|string|object>
	 *
	 * @throws Utils\JsonException
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function deviceCases(): array
	{
		$stored = [
			'id' => self::ID,
			'category' => 'generic',
			'identifier' => 'device-identifier',
			'connector' => self::CONNECTOR,
			'name' => null,
			'comment' => null,
			'parents' => [],
			'children' => [],
			'properties' => [],
			'controls' => [],
			'channels' => [],
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
		];
		$full = array_merge($stored, [
			'name' => 'A name',
			'comment' => 'A comment',
			'parents' => [self::SIBLING],
			'children' => [self::CHILD, self::OWNER],
			'properties' => [self::CHILD],
			'controls' => [self::OWNER],
			'channels' => [self::SIBLING, self::CHILD],
			'owner' => self::OWNER,
			'created_at' => '2020-04-01T12:00:00+00:00',
			'updated_at' => '2021-11-30T23:59:59+02:00',
		]);

		return [
			'the required fields' => ['id' => self::ID, 'category' => 'generic', 'identifier' => 'required', 'connector' => self::CONNECTOR],
			'the fields of a stored one' => $stored,
			'every field' => $full,
			'blank and null fields' => array_merge($stored, ['name' => '', 'comment' => " \t", 'owner' => ' ']),
			'an empty owner' => array_merge($stored, ['owner' => '']),
			'a name with spaces around and a no-break space' => array_merge(
				$stored,
				['name' => ' a ', 'comment' => "\u{00A0}"],
			),
			'unknown fields' => array_merge($stored, ['source' => 'x', 'x' => [1]]),
			'as JSON' => Utils\Json::encode($full),
			'as an object' => Utils\ArrayHash::from($full),
			'a UUID and an enum as objects' => array_merge($stored, [
				'id' => Uuid\Uuid::fromString(self::ID),
				'category' => DeviceCategory::GENERIC,
				'connector' => Uuid\Uuid::fromString(self::CONNECTOR),
				'owner' => Uuid\Uuid::fromString(self::OWNER),
				'channels' => [Uuid\Uuid::fromString(self::CHILD)],
			]),
			'lists of UUIDs with keys' => array_merge(
				$stored,
				['parents' => [3 => self::CHILD], 'channels' => [7 => self::SIBLING, 2 => self::OWNER]],
			),
			'lists of UUIDs with words for keys' => array_merge(
				$stored,
				[
					'parents' => ['a' => self::CHILD],
					'children' => ['b' => self::SIBLING],
					'properties' => ['c' => self::OWNER],
					'controls' => ['d' => self::CHILD],
					'channels' => ['e' => self::SIBLING],
				],
			),
			'a day which the month has not' => array_merge($stored, ['created_at' => '2020-02-31T12:00:00+00:00']),
			'dates in Zulu and without a colon' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00Z', 'updated_at' => '2020-04-01T12:00:00+0000'],
			),
			'a date with fractions of a second' => array_merge(
				$stored,
				['created_at' => '2020-04-01T12:00:00.123+00:00'],
			),
			'a date as a timestamp' => array_merge($stored, ['created_at' => 1_585_742_400]),
			'an empty date' => array_merge($stored, ['updated_at' => '']),
			'without name' => self::without($stored, 'name'),
			'without comment' => self::without($stored, 'comment'),
			'without parents, children, properties, controls and channels' => self::without(
				$stored,
				'parents',
				'children',
				'properties',
				'controls',
				'channels',
			),
			'without owner and dates' => self::without($stored, 'owner', 'created_at', 'updated_at'),
			'no id' => self::without($stored, 'id'),
			'an id which is not a UUID' => array_merge($stored, ['id' => 'x']),
			'no connector' => self::without($stored, 'connector'),
			'a connector which is not a UUID' => array_merge($stored, ['connector' => 'x']),
			'a connector which is null' => array_merge($stored, ['connector' => null]),
			'no category' => self::without($stored, 'category'),
			'an unknown category' => array_merge($stored, ['category' => 'special']),
			'no identifier' => self::without($stored, 'identifier'),
			'a blank identifier' => array_merge($stored, ['identifier' => " \t"]),
			'an identifier which is a number' => array_merge($stored, ['identifier' => 42]),
			'a name which is a number' => array_merge($stored, ['name' => 42]),
			'an owner which is not a UUID' => array_merge($stored, ['owner' => 'somebody']),
			'a parent which is not a UUID' => array_merge($stored, ['parents' => ['x']]),
			'children which are a text' => array_merge($stored, ['children' => self::CHILD]),
			'a channel which is a number' => array_merge($stored, ['channels' => [1]]),
			'a date in another format' => array_merge($stored, ['created_at' => '2020-04-01 12:00:00']),
		];
	}

}
