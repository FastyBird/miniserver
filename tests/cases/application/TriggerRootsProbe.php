<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use ArrayIterator;
use ArrayObject;
use Composer\Autoload\ClassLoader;
use DateTime;
use DateTimeImmutable;
use FastyBird\Automator\DateTime\Documents as DateTimeDocuments;
use FastyBird\Automator\DevicesModule\Documents as DevicesModuleDocuments;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions\InvalidArgument;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Types as TriggersTypes;
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
use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function dirname;
use function getopt;
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
use function str_repeat;
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
 * What bootstrap-trigger-roots.php does in the child process: the inputs, the documents, the report. The documents are
 * the six roots of the Triggers module (Trigger, Notification, Action, Condition, Control and the control's Action) and
 * every document of any package that extends one. Each is given its fields one at a time in every wrong form there is
 * (missing, null, blank, a number, a list, a text, and the forms of its own kind: a UUID without dashes, a date in
 * another format, a day out of the week), next to the whole document in a few forms
 */
final class TriggerRootsProbe
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string TRIGGER = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';

	private const string DEVICE = '0c1d6b77-d1a6-4f55-8d2c-7b7bd2d3c9a1';

	private const string CHANNEL = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';

	private const string OWNER = 'c5b8a0d2-0a77-4f4b-9d0a-9f1fe3b8e2aa';

	private const string DATE = '2020-04-01T12:00:00+00:00';

	private const array ROOTS = [
		'trigger' => TriggersDocuments\Triggers\Trigger::class,
		'notification' => TriggersDocuments\Notifications\Notification::class,
		'action' => TriggersDocuments\Actions\Action::class,
		'condition' => TriggersDocuments\Conditions\Condition::class,
		'control' => TriggersDocuments\Triggers\Controls\Control::class,
		'controlAction' => TriggersDocuments\Triggers\Controls\Action::class,
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

				if ($spec === null) {
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
	 * The documents of the six trees which can be built: the root of the Control and of its Action are final, the others
	 * are abstract and their descendants are the concrete ones
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
						&& !(new ReflectionClass($class))->isAbstract()
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
	 * The fields of each document and the kind of value each takes: a sample that is valid, the kind that says which forms
	 * of a wrong value are worth giving it, and the fields with a discriminator. 'fixed' are fields of the input the document
	 * does not declare, which the factory reads (the discriminator of a document with no type of its own)
	 *
	 * @return array<class-string, array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>}>
	 */
	private static function specs(): array
	{
		$trigger = [
			'id' => [self::ID, 'uuid'],
			'type' => ['automatic', 'type'],
			'name' => ['A trigger', 'text'],
			'comment' => [null, 'nullable text'],
			'enabled' => [true, 'bool'],
			'is_triggered' => [null, 'nullable bool'],
			'owner' => [null, 'nullable uuid'],
		];
		$action = [
			'id' => [self::ID, 'uuid'],
			'trigger' => [self::TRIGGER, 'uuid'],
			'enabled' => [true, 'bool'],
			'is_triggered' => [null, 'nullable bool'],
			'owner' => [null, 'nullable uuid'],
		];
		$condition = [
			'id' => [self::ID, 'uuid'],
			'trigger' => [self::TRIGGER, 'uuid'],
			'enabled' => [true, 'bool'],
			'is_fulfilled' => [null, 'nullable bool'],
			'owner' => [null, 'nullable uuid'],
		];
		$notification = [
			'id' => [self::ID, 'uuid'],
			'trigger' => [self::TRIGGER, 'uuid'],
			'type' => ['email', 'type'],
			'enabled' => [true, 'bool'],
			'owner' => [null, 'nullable uuid'],
		];

		return [
			TriggersDocuments\Triggers\Automatic::class => [
				'fields' => array_merge($trigger, ['is_fulfilled' => [null, 'nullable bool']]),
				'fixed' => ['type' => 'automatic'],
			],
			TriggersDocuments\Triggers\Manual::class => [
				'fields' => array_merge($trigger, ['type' => ['manual', 'type']]),
				'fixed' => ['type' => 'manual'],
			],
			TriggersDocuments\Triggers\Controls\Control::class => [
				'fields' => [
					'id' => [self::ID, 'uuid'],
					'trigger' => [self::TRIGGER, 'uuid'],
					'name' => ['A control', 'text'],
					'owner' => [self::OWNER, 'nullable uuid'],
				],
				'fixed' => [],
			],
			TriggersDocuments\Triggers\Controls\Action::class => [
				'fields' => [
					'action' => ['set', 'enum'],
					'trigger' => [self::TRIGGER, 'uuid'],
					'control' => [self::OWNER, 'uuid'],
					'expected_value' => [5.5, 'scalar'],
				],
				'fixed' => [],
			],
			TriggersDocuments\Notifications\Email::class => [
				'fields' => array_merge($notification, ['email' => ['a@example.com', 'email']]),
				'fixed' => ['type' => 'email'],
			],
			TriggersDocuments\Notifications\Sms::class => [
				'fields' => array_merge(
					$notification,
					['type' => ['sms', 'type'], 'phone' => ['+420603123456', 'phone']],
				),
				'fixed' => ['type' => 'sms'],
			],
			DevicesModuleDocuments\Actions\ChannelPropertyAction::class => [
				'fields' => array_merge($action, [
					'device' => [self::DEVICE, 'uuid'],
					'channel' => [self::CHANNEL, 'uuid'],
					'property' => [self::OWNER, 'uuid'],
					'value' => ['on', 'text'],
				]),
				'fixed' => ['type' => 'channel-property'],
			],
			DevicesModuleDocuments\Actions\DevicePropertyAction::class => [
				'fields' => array_merge($action, [
					'device' => [self::DEVICE, 'uuid'],
					'property' => [self::OWNER, 'uuid'],
					'value' => ['on', 'text'],
				]),
				'fixed' => ['type' => 'device-property'],
			],
			DevicesModuleDocuments\Conditions\ChannelPropertyCondition::class => [
				'fields' => array_merge($condition, [
					'device' => [self::DEVICE, 'uuid'],
					'channel' => [self::CHANNEL, 'uuid'],
					'property' => [self::OWNER, 'uuid'],
					'operand' => ['10', 'text'],
					'operator' => ['eq', 'operator'],
				]),
				'fixed' => ['type' => 'channel-property'],
			],
			DevicesModuleDocuments\Conditions\DevicePropertyCondition::class => [
				'fields' => array_merge($condition, [
					'device' => [self::DEVICE, 'uuid'],
					'property' => [self::OWNER, 'uuid'],
					'operand' => ['10', 'text'],
					'operator' => ['above', 'operator'],
				]),
				'fixed' => ['type' => 'device-property'],
			],
			DateTimeDocuments\Conditions\DateCondition::class => [
				'fields' => array_merge($condition, ['date' => [self::DATE, 'date']]),
				'fixed' => ['type' => 'date'],
			],
			DateTimeDocuments\Conditions\TimeCondition::class => [
				'fields' => array_merge($condition, [
					'time' => ['1970-01-01T08:30:00+00:00', 'date'],
					'days' => [[1, 2, 3, 4, 5], 'days'],
				]),
				'fixed' => ['type' => 'time'],
			],
		];
	}

	/**
	 * @param array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>} $spec
	 *
	 * @return array<string, array<string, mixed>|string|object>
	 *
	 * @throws JsonException
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

		foreach ($spec['fields'] as $field => [, $kind]) {
			if (array_key_exists($field, $spec['fixed'])) {
				continue;
			}

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
				self::variantsOf($kind),
			);

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
				'nullable text' => 'A comment',
				'nullable bool' => false,
				'nullable uuid' => self::OWNER,
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
	private static function variantsOf(string $kind): array
	{
		return match ($kind) {
			'uuid', 'nullable uuid' => [
				'an upper case UUID' => 'F42A8B4C-D5C8-4242-8FF0-6E5F867DCFB1',
				'a UUID without dashes' => 'f42a8b4cd5c842428ff06e5f867dcfb1',
				'a UUID with braces' => '{f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1}',
				'a UUID with a urn prefix' => 'urn:uuid:f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
				'a UUID with spaces around' => ' f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1 ',
				'a UUID as an object' => Uuid\Uuid::fromString(self::OWNER),
				'a short text' => 'not-a-uuid',
			],
			'bool', 'nullable bool' => [
				'the text true' => 'true',
				'the text false' => 'false',
				'the text 1' => '1',
				'the text 0' => '0',
				'the text yes' => 'yes',
				'the text TRUE' => 'TRUE',
			],
			'type' => [
				'an upper case type' => 'AUTOMATIC',
				'a type of another document' => 'sms',
			],
			'enum' => [
				'an upper case word' => 'SET',
				'an unknown word' => 'unset',
			],
			'operator' => [
				'an upper case word' => 'EQ',
				'an unknown word' => 'gt',
				'above' => 'above',
				'below' => 'below',
				'an enum as an object' => TriggersTypes\ConditionOperator::BELOW,
			],
			'scalar' => [
				'a text' => 'on',
				'a negative int' => -50,
				'a big float' => 1.0E+25,
				'a text of a number' => '21.5',
			],
			'email' => [
				'an upper case address' => 'A@EXAMPLE.COM',
				'an address without a domain' => 'a@',
				'an address without a top level domain' => 'a@example',
				'an address with a plus' => 'a+b@example.com',
				'an address with a one letter top level domain' => 'a@example.c',
				'an address with spaces' => 'a b@example.com',
				'an address with a long top level domain' => 'a@example.' . str_repeat('abcdefghij', 7),
				'an address with a new line at the end' => "a@example.com\n",
			],
			'phone' => [
				'a number without a plus' => '603123456',
				'a number with spaces' => '+420 603 123 456',
				'letters' => 'abc',
				'a number of another country' => '+12025550123',
			],
			'date' => [
				'a date in Zulu' => '2020-04-01T12:00:00Z',
				'a date without a colon' => '2020-04-01T12:00:00+0000',
				'a date with fractions of a second' => '2020-04-01T12:00:00.123+00:00',
				'a date as a timestamp' => 1_585_742_400,
				'a date without a time' => '2020-04-01',
				'a date in another format' => '2020-04-01 12:00:00',
				'a day which the month has not' => '2020-02-31T12:00:00+00:00',
				'a date with an offset' => '2021-11-30T23:59:59+02:00',
			],
			'days' => [
				'one day' => [3],
				'all seven days' => [1, 2, 3, 4, 5, 6, 7],
				'a day out of the week' => [0],
				'the eighth day' => [8],
				'a negative day' => [-1],
				'the same day twice' => [1, 1],
				'days as texts' => ['1', '2'],
				'days with keys' => [3 => 1, 7 => 2],
				'days with words for keys' => ['a' => 1],
				'eight days' => [1, 2, 3, 4, 5, 6, 7, 1],
				'a day as a float' => [1.0],
				'a text of days' => '1,2',
			],
			default => [],
		};
	}

	/**
	 * What a document makes of an object where its scalar or array form is expected, one thing at a time: a date object
	 * for a date, a traversable for the list of days. orisai refused all of these; Valinor takes the object of the type it
	 * was given or any traversable for a list. Nothing in the application builds a document from such a value
	 *
	 * @param array<class-string, array{fields: array<string, array{mixed, string}>, fixed: array<string, mixed>}> $specs
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 *
	 * @throws JsonException
	 */
	private static function objects(CoreDocuments\DocumentFactory $factory, array $specs): array
	{
		$report = [];
		$inputs = [
			TriggersDocuments\Triggers\Controls\Action::class => [
				'action as an enum' => ['action' => TriggersTypes\TriggerAction::SET],
			],
			DateTimeDocuments\Conditions\DateCondition::class => [
				'date as a DateTimeImmutable' => ['date' => new DateTimeImmutable(self::DATE)],
				'date as a DateTime' => ['date' => new DateTime(self::DATE)],
				'date as an object of no date' => ['date' => new stdClass()],
			],
			DateTimeDocuments\Conditions\TimeCondition::class => [
				'time as a DateTimeImmutable' => ['time' => new DateTimeImmutable('1970-01-01T08:30:00+00:00')],
				'time as a DateTime' => ['time' => new DateTime('1970-01-01T08:30:00+00:00')],
				'days as an ArrayObject' => ['days' => new ArrayObject([1, 2])],
				'days as an ArrayIterator' => ['days' => new ArrayIterator([3, 4])],
				'days as a Generator' => ['days' => self::generator()],
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
