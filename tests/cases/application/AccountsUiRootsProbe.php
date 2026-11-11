<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use ArrayIterator;
use ArrayObject;
use Composer\Autoload\ClassLoader;
use DateTime;
use DateTimeImmutable;
use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions\InvalidArgument;
use FastyBird\Module\Accounts\Documents as AccountsDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Types as UiTypes;
use FilesystemIterator;
use Generator;
use JsonException;
use Nette\Utils;
use Orisai\ObjectMapper\MappedObject;
use Ramsey\Uuid;
use Ramsey\Uuid\Exception as UuidExceptions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use stdClass;
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
use function getopt;
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
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtok;
use function strtoupper;
use function substr;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;
use const PHP_INT_MAX;

/**
 * What bootstrap-accounts-ui-roots.php does in the child process: the inputs, the documents, the report. The documents
 * are the four of the Accounts module (Account, Email, Identity, Role) and every document of the Ui module's own
 * interface, in any package: the dashboards, tabs and groups, the widgets with their data sources and displays, the
 * action of a data source, and the data sources of the DevicesModuleUiModule bridge. Each is given its fields one at a
 * time in every wrong form there is (missing, null, blank, a number, a list, a text, and the forms of its own kind: a
 * UUID without dashes, a date in another format, an address without a domain), next to the whole document in a few forms
 */
final class AccountsUiRootsProbe
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string TRIGGER = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';

	private const string CHILD = '6ac3ab7f-b2d4-4f63-8b80-ae72d3cf9ab5';

	private const string WIDGET = '1b7e5c2a-6d8f-4a1e-9c3b-5f2d8e7a4b60';

	private const string DISPLAY = '2c8f6d3b-7e90-4b2f-8d4c-6a3e9f8b5c71';

	private const string DASHBOARD = '3d907e4c-8fa1-4c30-9e5d-7b4fa09c6d82';

	private const string PROPERTY = '4ea18f5d-90b2-4d41-8f6e-8c50b1ad7e93';

	private const string CONNECTOR = '5fb29a6e-a1c3-4e52-9a7f-9d61c2be8fa4';

	private const string DEVICE = '0c1d6b77-d1a6-4f55-8d2c-7b7bd2d3c9a1';

	private const string CHANNEL = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';

	private const string OWNER = 'c5b8a0d2-0a77-4f4b-9d0a-9f1fe3b8e2aa';

	private const string DATE = '2020-04-01T12:00:00+00:00';

	private const array ROOTS = [
		'account' => AccountsDocuments\Accounts\Account::class,
		'email' => AccountsDocuments\Emails\Email::class,
		'identity' => AccountsDocuments\Identities\Identity::class,
		'role' => AccountsDocuments\Roles\Role::class,
		'ui' => UiDocuments\Document::class,
		'widget' => UiDocuments\Widgets\Widget::class,
		'dataSource' => UiDocuments\Widgets\DataSources\DataSource::class,
		'display' => UiDocuments\Widgets\Displays\Display::class,
	];

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

		// With --census: no container, only which side of Mapper::map()'s dispatch every document of the roots is on
		if (is_array($options) && array_key_exists('census', $options)) {
			$census = [];

			foreach (self::findDocuments($loader, dirname(__DIR__, 3)) as $class) {
				foreach (self::ROOTS as $family => $root) {
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
			$factory = $container->getByType(CoreDocuments\DocumentFactory::class);

			$specs = self::specs();

			if (is_array($options) && array_key_exists('objects', $options)) {
				echo json_encode(
					self::objects($factory, $specs),
					JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
				), PHP_EOL;

				return 0;
			}

			$report = [];

			foreach (self::concrete($loader) as $class) {
				$spec = $specs[$class] ?? null;

				if ($spec === null || !is_subclass_of($class, CoreDocuments\Document::class)) {
					throw new UnexpectedValueException('No inputs for ' . $class);
				}

				foreach (self::cases($spec) as $name => $data) {
					$report[$class][$name] = self::create($factory, $class, $data, $withMessages);
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
	 * The documents of the trees which can be built: the abstract classes and the interface of the Ui module are not, their
	 * descendants are
	 *
	 * @return list<class-string>
	 *
	 * @throws UnexpectedValueException
	 */
	private static function concrete(ClassLoader $loader): array
	{
		return array_values(array_filter(
			self::findDocuments($loader, dirname(__DIR__, 3)),
			static function (string $class): bool {
				foreach (self::ROOTS as $root) {
					if (
						($class === $root || is_subclass_of($class, $root))
						&& (new ReflectionClass($class))->isInstantiable()
					) {
						return true;
					}
				}

				return false;
			},
		));
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed>|string|object $data
	 *
	 * @return array<string, mixed>
	 */
	private static function create(
		CoreDocuments\DocumentFactory $factory,
		string $class,
		array|string|object $data,
		bool $withMessages,
	): array
	{
		try {
			$document = $factory->create($class, $data);

			return [
				'json' => Utils\Json::encode($document->toArray()),
				'serialized' => serialize($document),
			];
		} catch (InvalidArgument $ex) {
			// What the factory throws for a refused input
			return $withMessages
				? ['refused' => true, 'message' => (string) strtok($ex->getMessage(), "\n")]
				: ['refused' => true];
		} catch (Throwable $ex) {
			// orisai sets the property of a document without calling its constructor, so that what a rule hands over can be
			// refused by the property's own type: a TypeError, a defect and not a refusal
			return ['error' => $ex::class];
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
	 * The fields of each document and the kind of value each takes: a sample that is valid, and the kind that says which
	 * forms of a wrong value are worth giving it. 'fixed' are fields of the input which are not varied, the discriminator
	 * that the factory reads
	 *
	 * @return array<class-string, array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>, light?: bool}>
	 */
	private static function specs(): array
	{
		$owned = [
			'owner' => [null, 'nullable uuid'],
			'created_at' => [null, 'date null'],
			'updated_at' => [null, 'date null'],
		];
		$page = [
			'id' => [self::ID, 'uuid'],
			'identifier' => ['an-identifier', 'text'],
			'name' => [null, 'nullable text'],
			'comment' => [null, 'nullable text'],
			'priority' => [0, 'int'],
		];
		$widget = [
			'id' => [self::ID, 'uuid'],
			'display' => [self::DISPLAY, 'uuid'],
			'identifier' => ['a-widget', 'text'],
			'name' => [null, 'nullable text'],
			'data_sources' => [[], 'uuids'],
			'tabs' => [[], 'uuids'],
			'groups' => [[], 'uuids'],
		];
		$display = ['id' => [self::ID, 'uuid'], 'widget' => [self::WIDGET, 'uuid']];
		$dataSource = ['id' => [self::ID, 'uuid'], 'widget' => [self::WIDGET, 'uuid']];
		$dataSourceTypes = ['property' => [self::PROPERTY, 'uuid'], 'value' => [null, 'state value']];

		return [
			AccountsDocuments\Accounts\Account::class => [
				'fields' => [
					'id' => [self::ID, 'uuid'],
					'first_name' => ['Jane', 'text'],
					'last_name' => ['Doe', 'text'],
					'language' => ['en', 'text'],
					'state' => ['active', 'enum'],
					'middle_name' => [null, 'nullable text'],
					'email' => [null, 'nullable email'],
					'registered' => [null, 'date blank'],
					'last_visit' => [null, 'date blank'],
					'parent' => [null, 'nullable uuid'],
					'children' => [[], 'uuids'],
				],
				'fixed' => [],
			],
			AccountsDocuments\Emails\Email::class => [
				'fields' => [
					'id' => [self::ID, 'uuid'],
					'account' => [self::TRIGGER, 'uuid'],
					'address' => ['a@example.com', 'email'],
					'default' => [false, 'bool like'],
					'verified' => [false, 'bool like'],
					'private' => [false, 'bool like'],
					'public' => [false, 'bool like'],
				],
				'fixed' => [],
			],
			AccountsDocuments\Identities\Identity::class => [
				'fields' => [
					'id' => [self::ID, 'uuid'],
					'account' => [self::TRIGGER, 'uuid'],
					'state' => ['active', 'enum'],
					'uid' => ['a-uid', 'text'],
					'hash' => [null, 'nullable text'],
				],
				'fixed' => [],
			],
			AccountsDocuments\Roles\Role::class => [
				'fields' => [
					'id' => [self::ID, 'uuid'],
					'name' => ['administrator', 'text'],
					'comment' => [null, 'nullable text'],
					'anonymous' => [false, 'bool like'],
					'authenticated' => [false, 'bool like'],
					'administrator' => [false, 'bool like'],
					'parent' => [null, 'nullable uuid'],
				],
				'fixed' => [],
			],
			UiDocuments\Dashboards\Dashboard::class => [
				'fields' => array_merge($page, ['tabs' => [[], 'uuids']], $owned),
				'fixed' => [],
			],
			UiDocuments\Dashboards\Tabs\Tab::class => [
				'fields' => array_merge(
					$page,
					['dashboard' => [self::DASHBOARD, 'uuid'], 'widgets' => [[], 'uuids']],
					$owned,
				),
				'fixed' => [],
			],
			UiDocuments\Groups\Group::class => [
				'fields' => array_merge($page, ['widgets' => [[], 'uuids']], $owned),
				'fixed' => [],
			],
			UiDocuments\Widgets\AnalogActuator::class => [
				'fields' => array_merge($widget, $owned),
				'fixed' => ['type' => 'analog-actuator'],
			],
			UiDocuments\Widgets\AnalogSensor::class => [
				'fields' => array_merge($widget, $owned),
				'fixed' => ['type' => 'analog-sensor'],
				'light' => true,
			],
			UiDocuments\Widgets\DigitalActuator::class => [
				'fields' => array_merge($widget, $owned),
				'fixed' => ['type' => 'digital-actuator'],
				'light' => true,
			],
			UiDocuments\Widgets\DigitalSensor::class => [
				'fields' => array_merge($widget, $owned),
				'fixed' => ['type' => 'digital-sensor'],
				'light' => true,
			],
			UiDocuments\Widgets\DataSources\Generic::class => [
				'fields' => array_merge($dataSource, $owned),
				'fixed' => ['type' => 'generic'],
			],
			UiDocuments\Widgets\DataSources\Actions\Action::class => [
				'fields' => [
					'action' => ['set', 'enum'],
					'widget' => [self::WIDGET, 'uuid'],
					'data_source' => [self::PROPERTY, 'uuid'],
					'expected_value' => [5.5, 'scalar'],
				],
				'fixed' => [],
			],
			UiDocuments\Widgets\Displays\AnalogValue::class => [
				'fields' => array_merge($display, ['precision' => [2, 'precision']], $owned),
				'fixed' => ['type' => 'analog-value'],
			],
			UiDocuments\Widgets\Displays\Button::class => [
				'fields' => array_merge($display, ['icon' => ['button', 'nullable enum']], $owned),
				'fixed' => ['type' => 'button'],
			],
			UiDocuments\Widgets\Displays\ChartGraph::class => [
				'fields' => array_merge($display, [
					'minimum_value' => [0.0, 'nullable float'],
					'maximum_value' => [100.0, 'nullable float'],
					'step_value' => [0.5, 'nullable float'],
					'precision' => [2, 'precision'],
					'enable_min_max' => [true, 'nullable bool like'],
				], $owned),
				'fixed' => ['type' => 'chart-graph'],
			],
			UiDocuments\Widgets\Displays\DigitalValue::class => [
				'fields' => array_merge($display, $owned),
				'fixed' => ['type' => 'digital-value'],
			],
			UiDocuments\Widgets\Displays\Gauge::class => [
				'fields' => array_merge($display, ['precision' => [2, 'precision']], $owned),
				'fixed' => ['type' => 'gauge'],
			],
			UiDocuments\Widgets\Displays\GroupedButton::class => [
				'fields' => array_merge($display, ['icon' => ['led', 'nullable enum']], $owned),
				'fixed' => ['type' => 'grouped-button'],
			],
			UiDocuments\Widgets\Displays\Slider::class => [
				'fields' => array_merge($display, [
					'minimum_value' => [0.0, 'nullable float'],
					'maximum_value' => [100.0, 'nullable float'],
					'step_value' => [1.0, 'nullable float'],
					'precision' => [2, 'precision'],
				], $owned),
				'fixed' => ['type' => 'slider'],
			],
			DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::class => [
				'fields' => array_merge($dataSource, ['channel' => [self::CHANNEL, 'uuid']], $dataSourceTypes, $owned),
				'fixed' => ['type' => 'channel-property'],
			],
			DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty::class => [
				'fields' => array_merge($dataSource, ['device' => [self::DEVICE, 'uuid']], $dataSourceTypes, $owned),
				'fixed' => ['type' => 'device-property'],
				'light' => true,
			],
			DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty::class => [
				'fields' => array_merge(
					$dataSource,
					['connector' => [self::CONNECTOR, 'uuid']],
					$dataSourceTypes,
					$owned,
				),
				'fixed' => ['type' => 'connector-property'],
				'light' => true,
			],
		];
	}

	/**
	 * @param array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>, light?: bool} $spec
	 *
	 * @return array<string, array<string, mixed>|string|object>
	 *
	 * @throws JsonException
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function cases(array $spec): array
	{
		$stored = [];

		foreach ($spec['fields'] as $field => [$value]) {
			$stored[$field] = $value;
		}

		$stored = array_merge($stored, $spec['fixed']);
		$full = array_merge($stored, self::fullOf($spec['fields']), $spec['fixed']);

		$cases = [
			'the fields of a stored one' => $stored,
			'every field' => $full,
			'unknown fields' => array_merge($stored, ['source' => 'x', 'x' => [1]]),
			'as JSON' => Utils\Json::encode($full),
			'as an object' => Utils\ArrayHash::from($full),
			'nothing' => [],
		];

		foreach ($spec['fields'] as $field => [$value, $kind]) {
			if (array_key_exists($field, $spec['fixed'])) {
				continue;
			}

			// A document which is the same code as another one in the tree is given a few of the forms only
			$light = ($spec['light'] ?? false) === true;
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
				self::variantsOf($kind, $value),
			);

			if ($light) {
				$variants = array_intersect_key(
					$variants,
					array_flip(['missing', 'null', 'an empty string', 'a text', 'a numeric text']),
				);
			}

			foreach ($variants as $label => $variant) {
				$data = $stored;

				if ($label === 'missing') {
					unset($data[$field]);
				} else {
					$data[$field] = $variant;
				}

				$cases[$field . ': ' . $label] = $data;
			}

			if ($kind === 'nullable text') {
				$cases[$field . ': a value'] = array_merge($stored, [$field => 'A value']);
			}
		}

		return $cases;
	}

	/**
	 * The fields of a document filled with a value of their own kind
	 *
	 * @param array<string, array{mixed, string}> $fields
	 *
	 * @return array<string, mixed>
	 */
	private static function fullOf(array $fields): array
	{
		$full = [];

		foreach ($fields as $field => [$value, $kind]) {
			$full[$field] = match ($kind) {
				'nullable text' => 'A text',
				'nullable email' => 'b@example.com',
				'nullable uuid' => self::OWNER,
				'uuids' => [self::CHILD, self::OWNER],
				'date null', 'date blank' => self::DATE,
				'bool like' => true,
				'nullable bool like' => false,
				'int' => 5,
				'precision' => 3,
				'nullable float' => 2.5,
				'nullable enum' => 'fan',
				'state value' => 21.5,
				'scalar' => 'on',
				default => $value,
			};
		}

		return $full;
	}

	/**
	 * Forms a value of this kind can be wrong in, or right in another way
	 *
	 * @return array<string, mixed>
	 *
	 * @throws UuidExceptions\InvalidArgumentException
	 */
	private static function variantsOf(string $kind, mixed $sample): array
	{
		$uuids = [
			'an upper case UUID' => 'F42A8B4C-D5C8-4242-8FF0-6E5F867DCFB1',
			'a UUID without dashes' => 'f42a8b4cd5c842428ff06e5f867dcfb1',
			'a UUID with braces' => '{f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1}',
			'a UUID with a urn prefix' => 'urn:uuid:f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
			'a UUID with spaces around' => ' f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1 ',
			'a UUID as an object' => Uuid\Uuid::fromString(self::OWNER),
			'a short text' => 'not-a-uuid',
		];
		$bools = [
			'the text true' => 'true',
			'the text false' => 'false',
			'the text TRUE' => 'TRUE',
			'the text 1' => '1',
			'the text 0' => '0',
			'the text 01' => '01',
			'the text 2' => '2',
			'the text yes' => 'yes',
			'the int 2' => 2,
			'the float 1.0' => 1.0,
		];
		$emails = [
			'an upper case address' => 'A@EXAMPLE.COM',
			'an address without a domain' => 'a@',
			'an address without a top level domain' => 'a@example',
			'an address with a plus' => 'a+b@example.com',
			'an address with a one letter top level domain' => 'a@example.c',
			'an address with spaces' => 'a b@example.com',
			'an address with a long top level domain' => 'a@example.' . str_repeat('abcdefghij', 7),
			'an address with a new line at the end' => "a@example.com\n",
		];
		$dates = [
			'a date in Zulu' => '2020-04-01T12:00:00Z',
			'a date without a colon' => '2020-04-01T12:00:00+0000',
			'a date with fractions of a second' => '2020-04-01T12:00:00.123+00:00',
			'a date as a timestamp' => 1_585_742_400,
			'a date without a time' => '2020-04-01',
			'a date in another format' => '2020-04-01 12:00:00',
			'a day which the month has not' => '2020-02-31T12:00:00+00:00',
			'a date with an offset' => '2021-11-30T23:59:59+02:00',
		];
		$ints = [
			'a float with no decimals' => 5.0,
			'a negative int' => -1,
			'a big int' => PHP_INT_MAX,
			'an int as a text' => '5',
		];

		return match ($kind) {
			'uuid', 'nullable uuid' => $uuids,
			'bool', 'nullable bool' => ['the text true' => 'true', 'the text 1' => '1'],
			'bool like', 'nullable bool like' => $bools,
			'enum', 'nullable enum' => [
				'an upper case word' => strtoupper(is_string($sample) ? $sample : 'x'),
				'an unknown word' => 'unknown',
				'a word with a space' => ' ' . (is_string($sample) ? $sample : 'x'),
			],
			'email', 'nullable email' => $emails,
			'date null', 'date blank' => $dates,
			'int' => $ints,
			'precision' => $ints + ['zero' => 0, 'a negative precision' => -2],
			'nullable float' => [
				'an int' => 5,
				'a negative int' => -50,
				'a big float' => 1.0E+25,
				'a float as a text' => '1.5',
				'a float with a comma' => '1,5',
			],
			'uuids' => [
				'a list of two UUIDs' => [self::CHILD, self::OWNER],
				'UUIDs with keys' => [3 => self::CHILD, 7 => self::OWNER],
				'UUIDs with words for keys' => ['a' => self::CHILD],
				'an upper case UUID in a list' => ['F42A8B4C-D5C8-4242-8FF0-6E5F867DCFB1'],
				'a UUID as an object in a list' => [Uuid\Uuid::fromString(self::OWNER)],
				'a text which is not a UUID in a list' => ['x'],
				'a null in a list' => [null],
				'a list in a list' => [[self::CHILD]],
				'one UUID as a text' => self::CHILD,
			],
			'scalar' => [
				'a negative int' => -50,
				'a big float' => 1.0E+25,
				'a text of a number' => '21.5',
			],
			'state value' => [
				'a date' => '2020-04-01T12:00:00+00:00',
				'a date in another format' => '2020-04-01 12:00:00',
				'a button payload' => 'btn_pressed',
				'a switcher payload' => 'switch_on',
				'a cover payload' => 'cover_stop',
				'an upper case payload' => 'SWITCH_ON',
				'a negative int' => -50,
				'a float' => 21.5,
				'a big float' => 1.0E+25,
				'a text of a number' => '21.5',
				'a text' => 'on',
				'a date with a NUL byte' => "2020-04-01T12:00:00+00:00\0",
			],
			default => [],
		};
	}

	/**
	 * What a document makes of an object where its scalar or array form is expected, one thing at a time: a date object
	 * for a date, a traversable for the list of days. orisai refused all of these; Valinor takes the object of the type it
	 * was given or any traversable for a list. Nothing in the application builds a document from such a value
	 *
	 * @param array<class-string, array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>, light?: bool}> $specs
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 *
	 * @throws JsonException
	 */
	private static function objects(CoreDocuments\DocumentFactory $factory, array $specs): array
	{
		$report = [];
		$inputs = [
			AccountsDocuments\Accounts\Account::class => [
				'registered as a DateTimeImmutable' => ['registered' => new DateTimeImmutable(self::DATE)],
				'registered as a DateTime' => ['registered' => new DateTime(self::DATE)],
				'registered as an object of no date' => ['registered' => new stdClass()],
				'children as an ArrayObject' => ['children' => new ArrayObject([self::CHILD])],
				'children as an ArrayIterator' => ['children' => new ArrayIterator([self::CHILD, self::OWNER])],
				'children as a Generator' => ['children' => self::generator()],
			],
			UiDocuments\Dashboards\Dashboard::class => [
				'created_at as a DateTimeImmutable' => ['created_at' => new DateTimeImmutable(self::DATE)],
				'created_at as a DateTime' => ['created_at' => new DateTime(self::DATE)],
				'tabs as an ArrayObject' => ['tabs' => new ArrayObject([self::CHILD])],
			],
			UiDocuments\Widgets\Displays\Slider::class => [
				'updated_at as a DateTimeImmutable' => ['updated_at' => new DateTimeImmutable(self::DATE)],
			],
			UiDocuments\Widgets\DataSources\Actions\Action::class => [
				'action as an enum' => ['action' => UiTypes\DataSourceAction::SET],
			],
			DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::class => [
				'value as a DateTimeImmutable' => ['value' => new DateTimeImmutable(self::DATE)],
				'value as an object of no value' => ['value' => new stdClass()],
			],
		];

		foreach ($inputs as $class => $forms) {
			$stored = [];

			foreach ($specs[$class]['fields'] as $field => [$value]) {
				$stored[$field] = $value;
			}

			$stored = array_merge($stored, $specs[$class]['fixed']);

			foreach ($forms as $name => $form) {
				$report[$class][$name] = self::create($factory, $class, array_merge($stored, $form), false);
			}
		}

		return $report;
	}

	private static function generator(): Generator
	{
		yield 1;
	}

}
