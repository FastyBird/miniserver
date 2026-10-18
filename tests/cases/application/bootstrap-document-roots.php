<?php declare(strict_types = 1);

/**
 * bootstrap-document-roots.php
 *
 * Boots the application as public/index.php does and gives every concrete document that extends the Devices
 * module's Connector and Device documents, in every package, the same inputs through Core's DocumentFactory. It
 * prints, as JSON on stdout, what each input made: the JSON the exchange and the API encode (Nette's Json::encode of
 * toArray()) and serialize() of the document, or that the input was refused, and the first line of what was said.
 *
 * Run as a child process by DocumentRootsPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-document-roots.php \
 *       > tests/cases/application/fixtures/document-roots.json
 *
 * With --messages it adds the first line of what a refusal said, which DocumentRootsPinTest checks and the pins do not hold.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on 45787d7b4, the last commit
 * on which the two roots and everything extending them were still mapped by orisai (#724 PR4).
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

$loader = require __DIR__ . '/../../../vendor/autoload.php';

use Composer\Autoload\ClassLoader;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions\InvalidArgument;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Types\ConnectorCategory;
use FastyBird\Module\Devices\Types\DeviceCategory;
use Nette\Utils;
use Ramsey\Uuid;

const ID = '176984ad-7cf7-465d-9e53-71668a74a688';
const SIBLING = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';
const CONNECTOR = '0c1d6b77-d1a6-4f55-8d2c-7b7bd2d3c9a1';
const CHILD = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';
const OWNER = 'c5b8a0d2-0a77-4f4b-9d0a-9f1fe3b8e2aa';

/**
 * @return list<class-string>
 */
function findDocuments(ClassLoader $loader, string $root): array
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
function without(array $data, string ...$keys): array
{
	foreach ($keys as $key) {
		unset($data[$key]);
	}

	return $data;
}

/**
 * @return array<string, array<string, mixed>|string|object>
 */
function connectorCases(): array
{
	$stored = [
		'id' => ID,
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
		'properties' => [CHILD, OWNER],
		'controls' => [SIBLING],
		'devices' => [CONNECTOR],
		'owner' => OWNER,
		'created_at' => '2020-04-01T12:00:00+00:00',
		'updated_at' => '2021-11-30T23:59:59+02:00',
	]);

	return [
		'the required fields' => ['id' => ID, 'category' => 'generic', 'identifier' => 'required'],
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
			'id' => Uuid\Uuid::fromString(ID),
			'category' => ConnectorCategory::GENERIC,
			'owner' => Uuid\Uuid::fromString(OWNER),
			'properties' => [Uuid\Uuid::fromString(CHILD)],
		]),
		'lists of UUIDs with keys' => array_merge(
			$stored,
			['properties' => [3 => CHILD], 'controls' => [7 => SIBLING, 2 => OWNER]],
		),
		'lists of UUIDs with words for keys' => array_merge(
			$stored,
			['properties' => ['a' => CHILD], 'controls' => ['b' => SIBLING], 'devices' => ['c' => OWNER]],
		),
		'a day which the month has not' => array_merge($stored, ['created_at' => '2020-02-31T12:00:00+00:00']),
		'dates in Zulu and without a colon' => array_merge(
			$stored,
			['created_at' => '2020-04-01T12:00:00Z', 'updated_at' => '2020-04-01T12:00:00+0000'],
		),
		'a date with fractions of a second' => array_merge($stored, ['created_at' => '2020-04-01T12:00:00.123+00:00']),
		'a date without a time' => array_merge($stored, ['updated_at' => '2020-04-01']),
		'a date as a timestamp' => array_merge($stored, ['created_at' => 1_585_742_400]),
		'an empty date' => array_merge($stored, ['created_at' => '']),
		'without name' => without($stored, 'name'),
		'without comment' => without($stored, 'comment'),
		'without enabled' => without($stored, 'enabled'),
		'without properties, controls and devices' => without($stored, 'properties', 'controls', 'devices'),
		'without owner and dates' => without($stored, 'owner', 'created_at', 'updated_at'),
		'enabled as an int' => array_merge($stored, ['enabled' => 1]),
		'enabled as a text' => array_merge($stored, ['enabled' => 'true']),
		'enabled as null' => array_merge($stored, ['enabled' => null]),
		'no id' => without($stored, 'id'),
		'an id which is not a UUID' => array_merge($stored, ['id' => 'x']),
		'an id of a UUID without dashes' => array_merge($stored, ['id' => '176984ad7cf7465d9e5371668a74a688']),
		'no category' => without($stored, 'category'),
		'an unknown category' => array_merge($stored, ['category' => 'special']),
		'a category which is a number' => array_merge($stored, ['category' => 1]),
		'no identifier' => without($stored, 'identifier'),
		'a blank identifier' => array_merge($stored, ['identifier' => " \t"]),
		'an empty identifier' => array_merge($stored, ['identifier' => '']),
		'an identifier which is a number' => array_merge($stored, ['identifier' => 42]),
		'a name which is a number' => array_merge($stored, ['name' => 42]),
		'a comment which is a list' => array_merge($stored, ['comment' => ['x']]),
		'an owner which is not a UUID' => array_merge($stored, ['owner' => 'somebody']),
		'a property which is not a UUID' => array_merge($stored, ['properties' => ['x']]),
		'properties which are a text' => array_merge($stored, ['properties' => CHILD]),
		'a control which is a number' => array_merge($stored, ['controls' => [1]]),
		'a device which is null' => array_merge($stored, ['devices' => [null]]),
	];
}

/**
 * @return array<string, array<string, mixed>|string|object>
 */
function deviceCases(): array
{
	$stored = [
		'id' => ID,
		'category' => 'generic',
		'identifier' => 'device-identifier',
		'connector' => CONNECTOR,
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
		'parents' => [SIBLING],
		'children' => [CHILD, OWNER],
		'properties' => [CHILD],
		'controls' => [OWNER],
		'channels' => [SIBLING, CHILD],
		'owner' => OWNER,
		'created_at' => '2020-04-01T12:00:00+00:00',
		'updated_at' => '2021-11-30T23:59:59+02:00',
	]);

	return [
		'the required fields' => ['id' => ID, 'category' => 'generic', 'identifier' => 'required', 'connector' => CONNECTOR],
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
			'id' => Uuid\Uuid::fromString(ID),
			'category' => DeviceCategory::GENERIC,
			'connector' => Uuid\Uuid::fromString(CONNECTOR),
			'owner' => Uuid\Uuid::fromString(OWNER),
			'channels' => [Uuid\Uuid::fromString(CHILD)],
		]),
		'lists of UUIDs with keys' => array_merge(
			$stored,
			['parents' => [3 => CHILD], 'channels' => [7 => SIBLING, 2 => OWNER]],
		),
		'lists of UUIDs with words for keys' => array_merge(
			$stored,
			[
				'parents' => ['a' => CHILD],
				'children' => ['b' => SIBLING],
				'properties' => ['c' => OWNER],
				'controls' => ['d' => CHILD],
				'channels' => ['e' => SIBLING],
			],
		),
		'a day which the month has not' => array_merge($stored, ['created_at' => '2020-02-31T12:00:00+00:00']),
		'dates in Zulu and without a colon' => array_merge(
			$stored,
			['created_at' => '2020-04-01T12:00:00Z', 'updated_at' => '2020-04-01T12:00:00+0000'],
		),
		'a date with fractions of a second' => array_merge($stored, ['created_at' => '2020-04-01T12:00:00.123+00:00']),
		'a date as a timestamp' => array_merge($stored, ['created_at' => 1_585_742_400]),
		'an empty date' => array_merge($stored, ['updated_at' => '']),
		'without name' => without($stored, 'name'),
		'without comment' => without($stored, 'comment'),
		'without parents, children, properties, controls and channels' => without(
			$stored,
			'parents',
			'children',
			'properties',
			'controls',
			'channels',
		),
		'without owner and dates' => without($stored, 'owner', 'created_at', 'updated_at'),
		'no id' => without($stored, 'id'),
		'an id which is not a UUID' => array_merge($stored, ['id' => 'x']),
		'no connector' => without($stored, 'connector'),
		'a connector which is not a UUID' => array_merge($stored, ['connector' => 'x']),
		'a connector which is null' => array_merge($stored, ['connector' => null]),
		'no category' => without($stored, 'category'),
		'an unknown category' => array_merge($stored, ['category' => 'special']),
		'no identifier' => without($stored, 'identifier'),
		'a blank identifier' => array_merge($stored, ['identifier' => " \t"]),
		'an identifier which is a number' => array_merge($stored, ['identifier' => 42]),
		'a name which is a number' => array_merge($stored, ['name' => 42]),
		'an owner which is not a UUID' => array_merge($stored, ['owner' => 'somebody']),
		'a parent which is not a UUID' => array_merge($stored, ['parents' => ['x']]),
		'children which are a text' => array_merge($stored, ['children' => CHILD]),
		'a channel which is a number' => array_merge($stored, ['channels' => [1]]),
		'a date in another format' => array_merge($stored, ['created_at' => '2020-04-01 12:00:00']),
	];
}

$withMessages = in_array('--messages', $argv, true);

try {
	$configurator = Boot\Bootstrap::boot();

	// See bootstrap-production-scope.php: the Vite manifest is a frontend artefact a checkout does not have
	$configurator->addConfig([
		'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
	]);

	$container = $configurator->createContainer();
	$factory = $container->getByType(Documents\DocumentFactory::class);

	$report = [];

	foreach (
		[
			DevicesDocuments\Connectors\Connector::class => connectorCases(),
			DevicesDocuments\Devices\Device::class => deviceCases(),
		] as $root => $cases
	) {
		$classes = array_filter(
			findDocuments($loader, dirname(__DIR__, 3)),
			static fn (string $class): bool => is_subclass_of($class, $root) && !(new ReflectionClass(
				$class,
			))->isAbstract(),
		);

		foreach ($classes as $class) {
			// Every input for the generic document of the module, a few for every document that extends the root
			$wanted = $class === DevicesDocuments\Connectors\Generic::class || $class === DevicesDocuments\Devices\Generic::class
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
					$data = Utils\Json::encode(
						array_merge(Utils\Json::decode($data, forceArrays: true), ['type' => $type]),
					);
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

	exit(0);
} catch (Throwable $ex) {
	echo json_encode(['error' => $ex::class . ': ' . $ex->getMessage()]), PHP_EOL;

	exit(1);
}
