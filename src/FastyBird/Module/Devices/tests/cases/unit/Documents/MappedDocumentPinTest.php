<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use DateTimeImmutable;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use Throwable;
use function array_merge;
use function assert;
use function file_get_contents;
use function is_array;
use function is_string;
use function serialize;

/**
 * What the Devices documents that stay inside Devices map to, pinned. Each input goes through DocumentFactory, which
 * hands it to the mapper, and the result must be what orisai made of the same input when these classes were still
 * mapped by it (#724 PR3): the JSON the exchange, the API and the WebSockets encode, with Nette's Json::encode(), and
 * serialize() of the document, so that an int is not a float and an unset property is not a null one.
 * tests/fixtures/Documents/pinned.json holds both strings per input, taken from the orisai mapping at 2c227208e
 */
final class MappedDocumentPinTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string PARENT = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';

	private const string CHANNEL = '247fd6b5-8466-4323-81de-cec3e315015a';

	private const string DEVICE = '5a6a40a8-bb14-4188-9958-a7b2640cd1b1';

	private const string CONNECTOR = '0c1d6b77-d1a6-4f55-8d2c-7b7bd2d3c9a1';

	private const string CONTROL = '9f2b2c0e-4c63-4f6e-b7a0-6a0cf0a8ae51';

	private const string OWNER = 'c5b8a0d2-0a77-4f4b-9d0a-9f1fe3b8e2aa';

	private const string CHILD = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed>|string|object $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('documents')]
	public function testTheDocumentIsWhatOrisaiMade(string $name, string $class, array|string|object $data): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);
		$pinned = self::pinned($name);

		if ($pinned === null) {
			try {
				$factory->create($class, $data);

				self::fail('the document was accepted');
			} catch (CoreExceptions\InvalidArgument $ex) {
				self::assertStringContainsString('Could not map data to document', $ex->getMessage());
			}

			return;
		}

		$document = $factory->create($class, $data);

		self::assertSame($pinned['json'], Utils\Json::encode($document->toArray()));
		self::assertSame($pinned['serialized'], serialize($document));
	}

	/**
	 * The known-positive control: the comparison above has to tell two documents apart when one field differs, and an
	 * int from a float
	 *
	 * @throws Throwable
	 */
	public function testOneChangedFieldIsTold(): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$changed = $factory->create(
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(self::variable(), ['identifier' => 'another', 'value' => 5]),
		);
		$pinned = self::pinned('variable of a channel, the value 5');
		self::assertNotNull($pinned);

		self::assertNotSame($pinned['json'], Utils\Json::encode($changed->toArray()));
		self::assertNotSame($pinned['serialized'], serialize($changed));

		$float = $factory->create(
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(self::variable(), ['value' => 5.0]),
		);

		// toArray() reads the value as its data type says, so the JSON is the same; what the document holds is not
		self::assertNotSame($pinned['serialized'], serialize($float));
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('invalid')]
	public function testTheDocumentIsRefusedWhatOrisaiRefused(string $class, array $data): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		try {
			$factory->create($class, $data);

			self::fail('the document was accepted');
		} catch (CoreExceptions\InvalidArgument $ex) {
			self::assertStringContainsString('Could not map data to document', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{string, class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function documents(): array
	{
		$cases = [
			...self::controls(),
			...self::controlActions(),
			...self::properties(),
			...self::formats(),
			...self::scalars(),
			...self::states(),
			...self::actions(),
		];

		foreach ($cases as $name => $case) {
			$cases[$name] = [$name, ...$case];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>}>
	 */
	public static function invalid(): array
	{
		$cases = [
			'a property without an id' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				self::without(self::variable(), 'id'),
			],
			'a property whose id is not a UUID' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['id' => 'not-a-uuid']),
			],
			'a property whose id has no dashes' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['id' => '176984ad7cf7465d9e5371668a74a688']),
			],
			'a property without a channel' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				self::without(self::variable(), 'channel'),
			],
			'a mapped property without a parent' => [
				DevicesDocuments\Channels\Properties\Mapped::class,
				self::without(array_merge(self::variable(), ['type' => 'mapped']), 'parent'),
			],
			'a property without an identifier' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				self::without(self::variable(), 'identifier'),
			],
			'a property with a blank identifier' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['identifier' => " \t "]),
			],
			'a property with an empty identifier' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['identifier' => '']),
			],
			'a property with an identifier which is not a string' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['identifier' => 42]),
			],
			'a property without a category' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				self::without(self::variable(), 'category'),
			],
			'a property with an unknown category' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['category' => 'special']),
			],
			'a property without a data type' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				self::without(self::variable(), 'data_type'),
			],
			'a property with an unknown data type' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['data_type' => 'number']),
			],
			'a property with a name which is not a string' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['name' => 42]),
			],
			'a property with a unit which is not a string' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['unit' => 42]),
			],
			'a property with a scale which is a float' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['scale' => 1.5]),
			],
			'a property with a scale which is a numeric string' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['scale' => '2']),
			],
			'a property with a step which is a word' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['step' => 'wide']),
			],
			'a property with an invalid value which is a list' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['invalid' => [1]]),
			],
			'a property with a default which is a list' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['default' => [1]]),
			],
			'a variable with a value which is a list' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['value' => [1]]),
			],
			'a property with a value transformer which is a number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['value_transformer' => 5]),
			],
			'a property with an owner which is not a UUID' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['owner' => 'somebody']),
			],
			'a property with a date in another format' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['created_at' => '2020-04-01 12:00:00']),
			],
			'a property with updated_at as a timestamp' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['updated_at' => 1_585_742_400]),
			],
			'a property with an empty created_at' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['created_at' => '']),
			],
			'a format which is a number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => 5]),
			],
			'a format which is a bool' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => true]),
			],
			'a format of three numbers' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [1, 2, 3]]),
			],
			'a format of four numbers' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [1, 2, 3, 4]]),
			],
			'a format of one number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [1]]),
			],
			'a format of a string and a number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => ['one', 5]]),
			],
			'a format of a word and a number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => ['min', 5]]),
			],
			'a format with a key which is a word' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => ['first' => 'one', 'second' => 'two']]),
			],
			'a format of a list with a bool' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [true, false]]),
			],
			'a range with a data type that is not a number' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [['b', 1], 5]]),
			],
			'a range with an unknown data type' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [['zz', 1], 5]]),
			],
			'a range with a triple as its bound' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [['u8', 1, 2], 5]]),
			],
			'a range with a bound which is a string' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => ['1', 5]]),
			],
			'a range with a bound which is a bool' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [true, 5]]),
			],
			'a combined enum with a pair of three' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [[['sw', 'switch_on', 'x'], '1', ['s', 'on']]]]),
			],
			'a combined enum row of two' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [[['sw', 'switch_on'], '1'], [['sw', 'switch_off'], '2']]]),
			],
			'a combined enum row of four' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [[['sw', 'switch_on'], '1', ['s', 'on'], '4']]]),
			],
			'a combined enum row with an object' => [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['format' => [[new DateTimeImmutable(), '1', ['s', 'on']]]]),
			],
			'a dynamic property with a settable which is a word' => [
				DevicesDocuments\Channels\Properties\Dynamic::class,
				array_merge(self::dynamic(), ['settable' => 'yes']),
			],
			'a dynamic property with a queryable which is an int' => [
				DevicesDocuments\Channels\Properties\Dynamic::class,
				array_merge(self::dynamic(), ['queryable' => 1]),
			],
			'a dynamic property with a child which is not a UUID' => [
				DevicesDocuments\Channels\Properties\Dynamic::class,
				array_merge(self::dynamic(), ['children' => ['child']]),
			],
			'a dynamic property with children which are not a list' => [
				DevicesDocuments\Channels\Properties\Dynamic::class,
				array_merge(self::dynamic(), ['children' => self::CHILD]),
			],
			'a mapped property with a settable which is an int' => [
				DevicesDocuments\Channels\Properties\Mapped::class,
				array_merge(self::mapped(), ['settable' => 1]),
			],
			'a mapped property with a value which is a list' => [
				DevicesDocuments\Channels\Properties\Mapped::class,
				array_merge(self::mapped(), ['value' => []]),
			],
			'a connector property without a connector' => [
				DevicesDocuments\Connectors\Properties\Variable::class,
				self::without(self::variable('connector', self::CONNECTOR), 'connector'),
			],
			'a device property without a device' => [
				DevicesDocuments\Devices\Properties\Dynamic::class,
				self::without(self::dynamic('device', self::DEVICE), 'device'),
			],
			'a control without a name' => [
				DevicesDocuments\Channels\Controls\Control::class,
				self::without(self::control('channel', self::CHANNEL), 'name'),
			],
			'a control with a blank name' => [
				DevicesDocuments\Channels\Controls\Control::class,
				array_merge(self::control('channel', self::CHANNEL), ['name' => ' ']),
			],
			'a control of a connector with a blank name' => [
				DevicesDocuments\Connectors\Controls\Control::class,
				array_merge(self::control('connector', self::CONNECTOR), ['name' => "\t"]),
			],
			'a control with an empty name' => [
				DevicesDocuments\Devices\Controls\Control::class,
				array_merge(self::control('device', self::DEVICE), ['name' => '']),
			],
			'a control with a name which is not a string' => [
				DevicesDocuments\Connectors\Controls\Control::class,
				array_merge(self::control('connector', self::CONNECTOR), ['name' => 5]),
			],
			'a control without a parent' => [
				DevicesDocuments\Connectors\Controls\Control::class,
				self::without(self::control('connector', self::CONNECTOR), 'connector'),
			],
			'a control with an id which is not a UUID' => [
				DevicesDocuments\Devices\Controls\Control::class,
				array_merge(self::control('device', self::DEVICE), ['id' => 'x']),
			],
			'a control with an owner which is not a UUID' => [
				DevicesDocuments\Channels\Controls\Control::class,
				array_merge(self::control('channel', self::CHANNEL), ['owner' => 'x']),
			],
			'a control with a date in another format' => [
				DevicesDocuments\Channels\Controls\Control::class,
				array_merge(self::control('channel', self::CHANNEL), ['created_at' => '2020-04-01']),
			],
			'a control action without an action' => [
				DevicesDocuments\Channels\Controls\Actions\Action::class,
				self::without(self::controlAction('channel', self::CHANNEL), 'action'),
			],
			'a control action with an unknown action' => [
				DevicesDocuments\Channels\Controls\Actions\Action::class,
				array_merge(self::controlAction('channel', self::CHANNEL), ['action' => 'get']),
			],
			'a control action without a control' => [
				DevicesDocuments\Devices\Controls\Actions\Action::class,
				self::without(self::controlAction('device', self::DEVICE), 'control'),
			],
			'a control action without a connector' => [
				DevicesDocuments\Connectors\Controls\Actions\Action::class,
				self::without(self::controlAction('connector', self::CONNECTOR), 'connector'),
			],
			'a control action with an expected value which is a list' => [
				DevicesDocuments\Channels\Controls\Actions\Action::class,
				array_merge(self::controlAction('channel', self::CHANNEL), ['expected_value' => [1]]),
			],
			'a state without a read' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				self::without(self::state('channel', self::CHANNEL), 'read'),
			],
			'a state with a get which is a string' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['get' => 'on']),
			],
			'a state whose read lacks the expected value' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['read' => ['actual_value' => 1]]),
			],
			'a state with a pending which is a word' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['pending' => 'yes']),
			],
			'a state with a pending which is an int' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['pending' => 1]),
			],
			'a state with a pending in another date format' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['pending' => '2020-04-01 12:00:00']),
			],
			'a state with a valid which is an int' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['valid' => 1]),
			],
			'a state with a value which is a list' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(
					self::state('channel', self::CHANNEL),
					['read' => ['actual_value' => [1], 'expected_value' => 1]],
				),
			],
			'a state without a channel' => [
				DevicesDocuments\States\Channels\Properties\Property::class,
				self::without(self::state('channel', self::CHANNEL), 'channel'),
			],
			'a state without an id' => [
				DevicesDocuments\States\Devices\Properties\Property::class,
				self::without(self::state('device', self::DEVICE), 'id'),
			],
			'a state with a date in another format' => [
				DevicesDocuments\States\Connectors\Properties\Property::class,
				array_merge(self::state('connector', self::CONNECTOR), ['updated_at' => 'yesterday']),
			],
			'an action without an action' => [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				self::without(self::action('channel', self::CHANNEL), 'action'),
			],
			'an action which is unknown' => [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				array_merge(self::action('channel', self::CHANNEL), ['action' => 'report']),
			],
			'an action without a property' => [
				DevicesDocuments\States\Devices\Properties\Actions\Action::class,
				self::without(self::action('device', self::DEVICE), 'property'),
			],
			'an action without a connector' => [
				DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
				self::without(self::action('connector', self::CONNECTOR), 'connector'),
			],
			'an action whose set is a string' => [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				array_merge(self::action('channel', self::CHANNEL), ['set' => 'on']),
			],
			'an action whose write is a number' => [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				array_merge(self::action('channel', self::CHANNEL), ['write' => 5]),
			],
			'an action whose set holds a list' => [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				array_merge(self::action('channel', self::CHANNEL), ['set' => ['expected_value' => [1]]]),
			],
		];

		foreach (self::propertyClasses() as $parent => [$parentId, $variable, $dynamic, $mapped]) {
			$cases['a blank identifier of a variable property of a ' . $parent] = [
				$variable,
				array_merge(self::variable($parent, $parentId), ['identifier' => " \t"]),
			];
			$cases['a blank identifier of a dynamic property of a ' . $parent] = [
				$dynamic,
				array_merge(self::dynamic($parent, $parentId), ['identifier' => " \t"]),
			];

			if ($mapped === null) {
				continue;
			}

			$cases['a blank identifier of a mapped property of a ' . $parent] = [
				$mapped,
				array_merge(self::mapped($parent, $parentId), ['identifier' => " \t"]),
			];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function controls(): array
	{
		$cases = [];

		foreach (
			[
				'channel' => [DevicesDocuments\Channels\Controls\Control::class, self::CHANNEL],
				'device' => [DevicesDocuments\Devices\Controls\Control::class, self::DEVICE],
				'connector' => [DevicesDocuments\Connectors\Controls\Control::class, self::CONNECTOR],
			] as $parent => [$class, $parentId]
		) {
			$base = self::control($parent, $parentId);

			$cases['control of a ' . $parent . ', the required fields'] = [$class, $base];
			$cases['control of a ' . $parent . ', every field'] = [
				$class,
				array_merge($base, [
					'owner' => self::OWNER,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				]),
			];
			$cases['control of a ' . $parent . ', blank and null fields'] = [
				$class,
				array_merge($base, ['owner' => " \t", 'created_at' => null, 'updated_at' => null]),
			];
			$cases['control of a ' . $parent . ', an empty owner'] = [$class, array_merge($base, ['owner' => ''])];
			$cases['control of a ' . $parent . ', a name with spaces around'] = [
				$class,
				array_merge($base, ['name' => ' reboot ']),
			];
			$cases['control of a ' . $parent . ', a name of a no-break space'] = [
				$class,
				array_merge($base, ['name' => "\u{00A0}"]),
			];
			$cases['control of a ' . $parent . ', unknown fields'] = [
				$class,
				array_merge($base, ['source' => 'com.fastybird.module.devices', 'type' => 'control', 'x' => [1]]),
			];
		}

		$cases['control of a channel, as JSON'] = [
			DevicesDocuments\Channels\Controls\Control::class,
			Utils\Json::encode(array_merge(self::control('channel', self::CHANNEL), ['owner' => self::OWNER])),
		];
		$cases['control of a channel, as an object'] = [
			DevicesDocuments\Channels\Controls\Control::class,
			Utils\ArrayHash::from(
				array_merge(self::control('channel', self::CHANNEL), ['created_at' => '2020-04-01T12:00:00+00:00']),
			),
		];
		$cases['control of a device, UUID objects'] = [
			DevicesDocuments\Devices\Controls\Control::class,
			array_merge(self::control('device', self::DEVICE), [
				'id' => Uuid\Uuid::fromString(self::ID),
				'device' => Uuid\Uuid::fromString(self::DEVICE),
				'owner' => Uuid\Uuid::fromString(self::OWNER),
			]),
		];

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 */
	private static function controlActions(): array
	{
		$cases = [];

		foreach (
			[
				'channel' => [DevicesDocuments\Channels\Controls\Actions\Action::class, self::CHANNEL],
				'device' => [DevicesDocuments\Devices\Controls\Actions\Action::class, self::DEVICE],
				'connector' => [DevicesDocuments\Connectors\Controls\Actions\Action::class, self::CONNECTOR],
			] as $parent => [$class, $parentId]
		) {
			$base = self::controlAction($parent, $parentId);

			$cases['control action of a ' . $parent . ', no value'] = [$class, $base];
			$cases['control action of a ' . $parent . ', a null value'] = [
				$class,
				array_merge($base, ['expected_value' => null]),
			];
			$cases['control action of a ' . $parent . ', the value true'] = [
				$class,
				array_merge($base, ['expected_value' => true]),
			];
			$cases['control action of a ' . $parent . ', the value 5'] = [
				$class,
				array_merge($base, ['expected_value' => 5]),
			];
			$cases['control action of a ' . $parent . ', the value 5.5'] = [
				$class,
				array_merge($base, ['expected_value' => 5.5]),
			];
			$cases['control action of a ' . $parent . ', the value 5.0'] = [
				$class,
				array_merge($base, ['expected_value' => 5.0]),
			];
			$cases['control action of a ' . $parent . ', a negative int'] = [
				$class,
				array_merge($base, ['expected_value' => -50]),
			];
			$cases['control action of a ' . $parent . ', zero'] = [$class, array_merge($base, ['expected_value' => 0])];
			$cases['control action of a ' . $parent . ', the text on'] = [
				$class,
				array_merge($base, ['expected_value' => 'on']),
			];
			$cases['control action of a ' . $parent . ', a numeric text'] = [
				$class,
				array_merge($base, ['expected_value' => '5']),
			];
			$cases['control action of a ' . $parent . ', the text true'] = [
				$class,
				array_merge($base, ['expected_value' => 'true']),
			];
			$cases['control action of a ' . $parent . ', an empty text'] = [
				$class,
				array_merge($base, ['expected_value' => '']),
			];
			$cases['control action of a ' . $parent . ', a blank text'] = [
				$class,
				array_merge($base, ['expected_value' => " \n"]),
			];
			$cases['control action of a ' . $parent . ', unknown fields'] = [
				$class,
				array_merge($base, ['id' => self::ID, 'source' => 'x']),
			];
		}

		$cases['control action of a channel, as JSON'] = [
			DevicesDocuments\Channels\Controls\Actions\Action::class,
			Utils\Json::encode(array_merge(self::controlAction('channel', self::CHANNEL), ['expected_value' => 12])),
		];

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function properties(): array
	{
		$cases = [];

		foreach (self::propertyClasses() as $parent => [$parentId, $variable, $dynamic, $mapped]) {
			$cases['variable of a ' . $parent . ', the required fields'] = [
				$variable,
				self::without(self::without(self::variable($parent, $parentId), 'value'), 'default'),
			];
			$cases['variable of a ' . $parent . ', the fields of a stored one'] = [
				$variable,
				self::variable($parent, $parentId),
			];
			$cases['variable of a ' . $parent . ', every field'] = [
				$variable,
				array_merge(self::variable($parent, $parentId), [
					'name' => 'A name',
					'unit' => '%',
					'format' => '0:100',
					'invalid' => 99,
					'scale' => 2,
					'step' => 0.5,
					'default' => 10,
					'value_transformer' => 'equation:x=y*2',
					'value' => 20,
					'children' => [self::CHILD],
					'owner' => self::OWNER,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				]),
			];
			$cases['variable of a ' . $parent . ', blank and null fields'] = [
				$variable,
				array_merge(self::variable($parent, $parentId), [
					'name' => '',
					'unit' => ' ',
					'format' => " \t",
					'invalid' => '',
					'scale' => ' ',
					'step' => '',
					'default' => "\n",
					'value_transformer' => ' ',
					'value' => '',
					'owner' => ' ',
					'created_at' => null,
					'updated_at' => null,
				]),
			];

			$cases['variable of a ' . $parent . ', every converter'] = [
				$variable,
				array_merge(
					self::variable($parent, $parentId),
					self::converted(),
					['value' => '', 'children' => ['first' => self::CHILD]],
				),
			];

			$cases['dynamic of a ' . $parent . ', the required fields'] = [
				$dynamic,
				self::without(self::dynamic($parent, $parentId), 'children'),
			];
			$cases['dynamic of a ' . $parent . ', the fields of a stored one'] = [
				$dynamic,
				self::dynamic($parent, $parentId),
			];
			$cases['dynamic of a ' . $parent . ', every field'] = [
				$dynamic,
				array_merge(self::dynamic($parent, $parentId), [
					'name' => 'A name',
					'unit' => '°C',
					'format' => [['i8', -10.5], ['i8', 40]],
					'invalid' => 99.5,
					'scale' => 1,
					'step' => 1,
					'default' => 21.5,
					'value_transformer' => self::CHILD,
					'settable' => true,
					'queryable' => true,
					'children' => [self::CHILD, self::OWNER],
					'owner' => self::OWNER,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				]),
			];

			$cases['dynamic of a ' . $parent . ', every converter'] = [
				$dynamic,
				array_merge(
					self::dynamic($parent, $parentId),
					self::converted(),
					['settable' => true, 'queryable' => true, 'children' => ['first' => self::CHILD]],
				),
			];

			if ($mapped === null) {
				continue;
			}

			$cases['mapped of a ' . $parent . ', the required fields'] = [
				$mapped,
				self::without(self::mapped($parent, $parentId), 'value'),
			];
			$cases['mapped of a ' . $parent . ', the fields of a stored one'] = [
				$mapped,
				self::mapped($parent, $parentId),
			];
			$cases['mapped of a ' . $parent . ', every field'] = [
				$mapped,
				array_merge(self::mapped($parent, $parentId), [
					'settable' => false,
					'queryable' => true,
					'value' => 1.5,
					'owner' => self::OWNER,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				]),
			];
			$cases['mapped of a ' . $parent . ', every converter'] = [
				$mapped,
				array_merge(self::mapped($parent, $parentId), self::converted(), ['value' => ' ']),
			];

			$cases['mapped of a ' . $parent . ', nulls'] = [
				$mapped,
				array_merge(
					self::mapped($parent, $parentId),
					['settable' => null, 'queryable' => null, 'value' => null],
				),
			];
			$cases['mapped of a ' . $parent . ', a blank value'] = [
				$mapped,
				array_merge(self::mapped($parent, $parentId), ['value' => '  ']),
			];
		}

		$cases['variable of a channel, the value 5'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(self::variable(), ['value' => 5]),
		];
		$cases['variable of a channel, as JSON'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			Utils\Json::encode(self::variable()),
		];
		$cases['variable of a channel, as an object'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			Utils\ArrayHash::from(
				array_merge(self::variable(), ['format' => ['one', 'two'], 'children' => [self::CHILD]]),
			),
		];
		$cases['variable of a channel, a UUID and an enum as objects'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(self::variable(), [
				'id' => Uuid\Uuid::fromString(self::ID),
				'channel' => Uuid\Uuid::fromString(self::CHANNEL),
				'category' => DevicesTypes\PropertyCategory::GENERIC,
				'data_type' => ValuesTypes\DataType::INT,
				'value_transformer' => Uuid\Uuid::fromString(self::CHILD),
				'owner' => Uuid\Uuid::fromString(self::OWNER),
				'children' => [Uuid\Uuid::fromString(self::CHILD)],
			]),
		];
		$cases['variable of a channel, a stored one with the discriminator and the unknown'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(
				self::variable(),
				['source' => 'com.fastybird.module.devices', 'something' => ['created_at' => 'no']],
			),
		];

		return $cases;
	}

	/**
	 * The shapes a format takes: a string, a list of strings, a pair for a range, a list of rows for a combined enum.
	 * What every field of a row may be, and what is refused, is pinned beside in invalid()
	 *
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 */
	private static function formats(): array
	{
		$class = DevicesDocuments\Channels\Properties\Variable::class;
		$cases = [];

		$formats = [
			'null' => null,
			'a range as a string' => '0:100',
			'an enum as a string' => 'one,two,three',
			'a string which fits neither' => '???',
			'an empty string' => '',
			'a blank string' => " \t ",
			'a no-break space' => "\u{00A0}",
			'an empty list' => [],
			'a list of strings' => ['one', 'two', 'three'],
			'a list of one string' => ['one'],
			'a list of strings with a blank' => ['one', ' ', 'three'],
			'a list of strings with keys' => [3 => 'one', 1 => 'two'],
			'a range of ints' => [0, 100],
			'a range of floats' => [0.5, 99.5],
			'a range of an int and a float' => [0, 99.5],
			'a range of a float and an int' => [0.5, 100],
			'a range with a float that has no fraction' => [1.0, 2.0],
			'a range with a bound that is null' => [null, 100],
			'a range with both bounds null' => [null, null],
			'a range with a bound that is blank' => [' ', 100],
			'a range with a bound that is empty' => [0, ''],
			'a range with typed bounds' => [['u8', 10], ['u8', 50]],
			'a range with a typed float bound' => [['f', 10.5], 50],
			'a range with a typed bound of every number type' => [['i8', 1], ['u16', 2.5]],
			'a range with a typed bound of i16' => [['i16', 1], ['u32', 2]],
			'a range with a typed bound of i32' => [['i32', 1], ['f', 2]],
			'a range with a typed bound written big' => [['U8', 1], 5],
			'a range as in the fixture' => [['u8', 10.5], 50],
			'a range as a list with keys' => [1 => 0, 0 => 100],
			'a range with a bound that is a string number' => [['u8', 10], '50'],
			'a combined enum' => [
				[['sw', 'switch_on'], '1000', ['s', 'on']],
				[['sw', 'switch_off'], '2000', ['s', 'off']],
				[['sw', 'switch_toggle'], null, ['s', 'toggle']],
			],
			'a combined enum with numbers' => [[1, 2, 3], [1.5, 2.5, 3.5]],
			'a combined enum with an int and a float' => [[1, 2.0, 3], [1.0, 2, 3.0]],
			'a combined enum of one row' => [[['btn', 'btn_pressed'], 'a', ['s', 'b']]],
			'a combined enum with nulls' => [[null, null, null]],
			'a combined enum with blanks' => [['', ' ', "\t"]],
			'a combined enum with typed items' => [
				[['b', true], ['i8', 5], ['s', 'x']],
				[['f', 1.5], ['u8', 4], ['cvr', 'cover_open']],
			],
			'a combined enum with a typed bool' => [[['b', false], 'x', ['b', true]]],
			'a combined enum with a typed null' => [[['s', null], 'x', ['s', 'y']]],
			'a combined enum with every type name' => [
				[['i8', 1], ['u8', 1], ['i16', 1]],
				[['u16', 1], ['i32', 1], ['u32', 1]],
				[['f', 1], ['b', 1], ['s', 'x']],
				[['btn', 'x'], ['sw', 'x'], ['cvr', 'x']],
			],
			'a combined enum with a type that is not a combined type' => [[['e', 'x'], 'a', 'b']],
			'a combined enum with a string number' => [['1', '2.5', '3']],
			'a combined enum with an int key' => [4 => [1, 2, 3], 2 => [4, 5, 6]],
		];

		foreach ($formats as $name => $format) {
			$cases['format, ' . $name] = [$class, array_merge(self::variable(), ['format' => $format])];
		}

		$cases['format, a range on a dynamic device property'] = [
			DevicesDocuments\Devices\Properties\Dynamic::class,
			array_merge(
				self::dynamic('device', self::DEVICE),
				['format' => [['u8', 10.5], 50], 'data_type' => 'uchar'],
			),
		];
		$cases['format, a combined enum on a mapped channel property'] = [
			DevicesDocuments\Channels\Properties\Mapped::class,
			array_merge(
				self::mapped(),
				['format' => [[['sw', 'switch_on'], '1', ['s', 'on']]], 'data_type' => 'switch'],
			),
		];
		$cases['format, the fixture of a dynamic property, as JSON'] = [
			DevicesDocuments\Channels\Properties\Dynamic::class,
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.dynamic.json'),
		];
		$cases['format, the fixture of a mapped property, as JSON'] = [
			DevicesDocuments\Channels\Properties\Mapped::class,
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.mapped.json'),
		];
		$cases['format, the fixture of a variable property, as JSON'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.variable.json'),
		];

		return $cases;
	}

	/**
	 * The scalar fields a property has: what an int, a float, a bool, a string and a blank become in each of them
	 *
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function scalars(): array
	{
		$cases = [];

		$values = [
			'true' => true,
			'false' => false,
			'0' => 0,
			'5' => 5,
			'-50' => -50,
			'5.0' => 5.0,
			'5.5' => 5.5,
			'text' => 'text',
			'a numeric text' => '5',
			'the text true' => 'true',
			'a text with spaces' => ' padded ',
			'null' => null,
			'an empty text' => '',
			'a text of spaces' => '   ',
			'a text of a form feed' => "\f",
			'a no-break space' => "\u{00A0}",
			'a UUID text' => self::CHILD,
			'a date text' => '2020-04-01T12:00:00+00:00',
			'a payload text' => 'switch_on',
		];

		foreach (
			[
				'invalid' => DevicesDocuments\Channels\Properties\Variable::class,
				'default' => DevicesDocuments\Devices\Properties\Dynamic::class,
				'value' => DevicesDocuments\Connectors\Properties\Variable::class,
				'step' => DevicesDocuments\Channels\Properties\Mapped::class,
				'scale' => DevicesDocuments\Devices\Properties\Variable::class,
			] as $field => $class
		) {
			$base = match ($class) {
				DevicesDocuments\Devices\Properties\Dynamic::class => self::dynamic('device', self::DEVICE),
				DevicesDocuments\Connectors\Properties\Variable::class => self::variable('connector', self::CONNECTOR),
				DevicesDocuments\Channels\Properties\Mapped::class => self::mapped(),
				DevicesDocuments\Devices\Properties\Variable::class => self::variable('device', self::DEVICE),
				default => self::variable(),
			};

			foreach ($values as $name => $value) {
				$cases['the field ' . $field . ' is ' . $name] = [$class, array_merge($base, [$field => $value])];
			}
		}

		foreach (
			[
				'a UUID' => self::CHILD,
				'a UUID in capitals' => '3A9D0F6C-6E0C-4C31-A9C5-0F8D2F4B7F10',
				'a UUID in braces' => '{' . self::CHILD . '}',
				'a UUID with no dashes' => '3a9d0f6c6e0c4c31a9c50f8d2f4b7f10',
				'a URN' => 'urn:uuid:' . self::CHILD,
				'a transformer' => 'equation:x=y*2',
				'a word' => 'something',
				'null' => null,
				'blank' => ' ',
			] as $name => $value
		) {
			$cases['the value transformer is ' . $name] = [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['value_transformer' => $value]),
			];
		}

		foreach (
			[
				'a UUID' => self::OWNER,
				'null' => null,
				'blank' => " \t",
				'a UUID in capitals' => 'C5B8A0D2-0A77-4F4B-9D0A-9F1FE3B8E2AA',
			] as $name => $value
		) {
			$cases['the owner of a property is ' . $name] = [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['owner' => $value]),
			];
		}

		foreach (
			[
				'a date' => '2020-04-01T12:00:00+00:00',
				'a date with an offset' => '2021-11-30T23:59:59+02:00',
				'a date in Zulu' => '2021-11-30T23:59:59Z',
				'an offset without a colon' => '2020-04-01T12:00:00+0000',
				'a day which the month has not' => '2020-02-31T12:00:00+00:00',
				'an hour of 24' => '2020-04-01T24:00:00+00:00',
				'a month of 13' => '2020-13-01T12:00:00+00:00',
				'a date with a space before it' => ' 2020-04-01T12:00:00+00:00',
				'a date with a newline after it' => "2020-04-01T12:00:00+00:00\n",
				'a date with fractions of a second' => '2020-04-01T12:00:00.123+00:00',
				'a date without a time' => '2020-04-01',
				'a date with a lower case T' => '2020-04-01t12:00:00+00:00',
				'null' => null,
			] as $name => $value
		) {
			$cases['the dates of a property are ' . $name] = [
				DevicesDocuments\Channels\Properties\Variable::class,
				array_merge(self::variable(), ['created_at' => $value, 'updated_at' => $value]),
			];
		}

		foreach (
			[
				'a list of UUIDs' => [self::CHILD, self::OWNER],
				'an empty list' => [],
				'a list with keys' => [3 => self::CHILD, 1 => self::OWNER],
				'a map' => ['first' => self::CHILD],
				'UUID objects' => [Uuid\Uuid::fromString(self::CHILD)],
			] as $name => $value
		) {
			$cases['the children of a dynamic property are ' . $name] = [
				DevicesDocuments\Channels\Properties\Dynamic::class,
				array_merge(self::dynamic(), ['children' => $value]),
			];
		}

		foreach (
			[
				'false' => false,
				'true' => true,
				'null' => null,
			] as $name => $value
		) {
			$cases['a mapped property has settable ' . $name . ' and queryable ' . $name] = [
				DevicesDocuments\Channels\Properties\Mapped::class,
				array_merge(self::mapped(), ['settable' => $value, 'queryable' => $value]),
			];
		}

		$cases['a name and a unit with spaces around'] = [
			DevicesDocuments\Channels\Properties\Variable::class,
			array_merge(self::variable(), ['name' => ' Name ', 'unit' => ' °C ']),
		];

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function states(): array
	{
		$cases = [];

		foreach (
			[
				'channel' => [DevicesDocuments\States\Channels\Properties\Property::class, self::CHANNEL],
				'device' => [DevicesDocuments\States\Devices\Properties\Property::class, self::DEVICE],
				'connector' => [DevicesDocuments\States\Connectors\Properties\Property::class, self::CONNECTOR],
			] as $parent => [$class, $parentId]
		) {
			$base = self::state($parent, $parentId);

			$cases['state of a ' . $parent . ', the required fields'] = [$class, $base];
			$cases['state of a ' . $parent . ', every field'] = [
				$class,
				array_merge($base, [
					'read' => ['actual_value' => 5, 'expected_value' => 6],
					'get' => ['actual_value' => 'on', 'expected_value' => 'off'],
					'pending' => '2020-04-01T12:00:00+00:00',
					'valid' => true,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				]),
			];
			$cases['state of a ' . $parent . ', pending'] = [$class, array_merge($base, ['pending' => true])];
			$cases['state of a ' . $parent . ', null values'] = [
				$class,
				array_merge($base, [
					'read' => ['actual_value' => null, 'expected_value' => null],
					'get' => ['actual_value' => '', 'expected_value' => ' '],
				]),
			];
		}

		$cases['state of a channel, as JSON'] = [
			DevicesDocuments\States\Channels\Properties\Property::class,
			Utils\Json::encode(self::state('channel', self::CHANNEL)),
		];
		$cases['state of a channel, as an object'] = [
			DevicesDocuments\States\Channels\Properties\Property::class,
			Utils\ArrayHash::from(self::state('channel', self::CHANNEL)),
		];
		$cases['state of a channel, UUID objects'] = [
			DevicesDocuments\States\Channels\Properties\Property::class,
			array_merge(self::state('channel', self::CHANNEL), [
				'id' => Uuid\Uuid::fromString(self::ID),
				'channel' => Uuid\Uuid::fromString(self::CHANNEL),
			]),
		];
		$cases['state of a channel, unknown fields'] = [
			DevicesDocuments\States\Channels\Properties\Property::class,
			array_merge(self::state('channel', self::CHANNEL), [
				'source' => 'x',
				'read' => ['actual_value' => 1, 'expected_value' => 2, 'x' => 3],
			]),
		];

		$values = [
			'true' => true,
			'false' => false,
			'0' => 0,
			'5' => 5,
			'-50' => -50,
			'5.0' => 5.0,
			'5.5' => 5.5,
			'text' => 'text',
			'a numeric text' => '5',
			'the text true' => 'true',
			'null' => null,
			'an empty text' => '',
			'a text of spaces' => '   ',
			'a date text' => '2020-04-01T12:00:00+00:00',
			'a date text with an offset' => '2021-11-30T23:59:59+02:00',
			'a text which is a date in another format' => '2020-04-01 12:00:00',
			'a text of a date without a time' => '2020-04-01',
			'the switch_on text' => 'switch_on',
			'the switch_toggle text' => 'switch_toggle',
			'the btn_pressed text' => 'btn_pressed',
			'the btn_extra_long_clicked text' => 'btn_extra_long_clicked',
			'the cover_open text' => 'cover_open',
			'the cover_lock text' => 'cover_lock',
			'a text that looks like a payload' => 'switch_flip',
			'a text in capitals that is a payload' => 'SWITCH_ON',
			'the not set text' => 'N/A',
		];

		foreach ($values as $name => $value) {
			$cases['a state with the value ' . $name] = [
				DevicesDocuments\States\Devices\Properties\Property::class,
				array_merge(self::state('device', self::DEVICE), [
					'read' => ['actual_value' => $value, 'expected_value' => $value],
					'get' => ['actual_value' => $value, 'expected_value' => $value],
				]),
			];
		}

		foreach (
			[
				'a date' => '2020-04-01T12:00:00+00:00',
				'a date with an offset' => '2021-11-30T23:59:59+02:00',
				'true' => true,
				'false' => false,
			] as $name => $value
		) {
			$cases['a state which is pending as ' . $name] = [
				DevicesDocuments\States\Channels\Properties\Property::class,
				array_merge(self::state('channel', self::CHANNEL), ['pending' => $value]),
			];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>|string|object}>
	 *
	 * @throws Utils\JsonException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function actions(): array
	{
		$cases = [];

		foreach (
			[
				'channel' => [DevicesDocuments\States\Channels\Properties\Actions\Action::class, self::CHANNEL],
				'device' => [DevicesDocuments\States\Devices\Properties\Actions\Action::class, self::DEVICE],
				'connector' => [DevicesDocuments\States\Connectors\Properties\Actions\Action::class, self::CONNECTOR],
			] as $parent => [$class, $parentId]
		) {
			$base = self::action($parent, $parentId);

			$cases['action of a ' . $parent . ', get'] = [$class, $base];
			$cases['action of a ' . $parent . ', get with a set which is ignored'] = [
				$class,
				array_merge($base, ['set' => ['actual_value' => 1]]),
			];
			$cases['action of a ' . $parent . ', set, no values'] = [$class, array_merge($base, ['action' => 'set'])];
			$cases['action of a ' . $parent . ', set, null values'] = [
				$class,
				array_merge($base, ['action' => 'set', 'set' => null, 'write' => null]),
			];
			$cases['action of a ' . $parent . ', set, both'] = [
				$class,
				array_merge($base, [
					'action' => 'set',
					'set' => ['actual_value' => 1, 'expected_value' => 2],
					'write' => ['actual_value' => 'on', 'expected_value' => 'off'],
				]),
			];
			$cases['action of a ' . $parent . ', set, one value'] = [
				$class,
				array_merge(
					$base,
					['action' => 'set', 'set' => ['expected_value' => 2], 'write' => ['actual_value' => 1]],
				),
			];
			$cases['action of a ' . $parent . ', set, empty values'] = [
				$class,
				array_merge($base, ['action' => 'set', 'set' => [], 'write' => []]),
			];
			$cases['action of a ' . $parent . ', set, nulls in the values'] = [
				$class,
				array_merge($base, [
					'action' => 'set',
					'set' => ['actual_value' => null, 'expected_value' => ''],
					'write' => ['actual_value' => ' ', 'expected_value' => null],
				]),
			];
			$cases['action of a ' . $parent . ', set, unknown fields'] = [
				$class,
				array_merge($base, [
					'action' => 'set',
					'id' => self::ID,
					'device' => self::DEVICE,
					'expected_value' => 'on',
					'set' => ['actual_value' => 1, 'x' => 2],
				]),
			];
		}

		$cases['action of a channel, as JSON'] = [
			DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			Utils\Json::encode(array_merge(self::action('channel', self::CHANNEL), [
				'action' => 'set',
				'set' => ['actual_value' => 1.5, 'expected_value' => 2],
			])),
		];
		$cases['action of a channel, the fixture of a get, as JSON'] = [
			DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.get.json'),
		];
		$cases['action of a channel, the fixture of a set, as JSON'] = [
			DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.set.json'),
		];
		$cases['action of a channel, UUID objects'] = [
			DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			[
				'action' => 'set',
				'channel' => Uuid\Uuid::fromString(self::CHANNEL),
				'property' => Uuid\Uuid::fromString(self::ID),
				'set' => ['actual_value' => 'switch_on'],
			],
		];

		$values = [
			'true' => true,
			'false' => false,
			'0' => 0,
			'5' => 5,
			'-50' => -50,
			'5.0' => 5.0,
			'5.5' => 5.5,
			'text' => 'text',
			'a numeric text' => '5',
			'null' => null,
			'an empty text' => '',
			'a date text' => '2020-04-01T12:00:00+00:00',
			'the switch_on text' => 'switch_on',
			'the btn_pressed text' => 'btn_pressed',
			'the cover_open text' => 'cover_open',
			'the not set text' => 'N/A',
		];

		foreach ($values as $name => $value) {
			$cases['an action with the value ' . $name] = [
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				array_merge(self::action('channel', self::CHANNEL), [
					'action' => 'set',
					'set' => ['actual_value' => $value, 'expected_value' => $value],
					'write' => ['expected_value' => $value],
				]),
			];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{string, class-string<CoreDocuments\Document>, class-string<CoreDocuments\Document>, class-string<CoreDocuments\Document>|null}>
	 */
	private static function propertyClasses(): array
	{
		return [
			'channel' => [
				self::CHANNEL,
				DevicesDocuments\Channels\Properties\Variable::class,
				DevicesDocuments\Channels\Properties\Dynamic::class,
				DevicesDocuments\Channels\Properties\Mapped::class,
			],
			'device' => [
				self::DEVICE,
				DevicesDocuments\Devices\Properties\Variable::class,
				DevicesDocuments\Devices\Properties\Dynamic::class,
				DevicesDocuments\Devices\Properties\Mapped::class,
			],
			'connector' => [
				self::CONNECTOR,
				DevicesDocuments\Connectors\Properties\Variable::class,
				DevicesDocuments\Connectors\Properties\Dynamic::class,
				null,
			],
		];
	}

	/**
	 * The fields of a property that carry a converter or an attribute, each given what the converter or the attribute
	 * changes: a blank becomes null, a text that is a UUID becomes one, a format is read, a key is renamed. A class that
	 * lacks any one of them maps this differently
	 *
	 * @return array<string, mixed>
	 */
	private static function converted(): array
	{
		return [
			'name' => ' ',
			'unit' => '',
			'data_type' => 'float',
			'format' => [['u8', 10.5], ' '],
			'invalid' => ' ',
			'scale' => '',
			'step' => ' ',
			'default' => "\t",
			'value_transformer' => self::CHILD,
			'owner' => ' ',
			'created_at' => '2020-04-01T12:00:00+00:00',
			'updated_at' => '2021-11-30T23:59:59+02:00',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function control(string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return [
			'id' => self::ID,
			$parent => $parentId,
			'name' => 'reboot',
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function controlAction(string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return [
			'action' => 'set',
			$parent => $parentId,
			'control' => self::CONTROL,
		];
	}

	/**
	 * What a property's entity gives the document: the keys of its toArray(), every field present
	 *
	 * @return array<string, mixed>
	 */
	private static function property(string $type, string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return [
			'id' => self::ID,
			'type' => $type,
			'category' => 'generic',
			'identifier' => 'property-identifier',
			'name' => null,
			'data_type' => 'int',
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'scale' => null,
			'step' => null,
			'default' => null,
			'value_transformer' => null,
			$parent => $parentId,
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function variable(string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return array_merge(self::property('variable', $parent, $parentId), ['value' => 'two', 'children' => []]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function dynamic(string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return array_merge(
			self::property('dynamic', $parent, $parentId),
			['settable' => true, 'queryable' => false, 'children' => []],
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function mapped(string $parent = 'channel', string $parentId = self::CHANNEL): array
	{
		return array_merge(
			self::property('mapped', $parent, $parentId),
			['parent' => self::PARENT, 'settable' => true, 'queryable' => false, 'value' => null],
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function state(string $parent, string $parentId): array
	{
		return [
			'id' => self::ID,
			$parent => $parentId,
			'read' => ['actual_value' => 1, 'expected_value' => 2],
			'get' => ['actual_value' => 1, 'expected_value' => 2],
			'pending' => false,
			'valid' => false,
			'created_at' => null,
			'updated_at' => null,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function action(string $parent, string $parentId): array
	{
		return [
			'action' => 'get',
			$parent => $parentId,
			'property' => self::ID,
		];
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private static function without(array $data, string $key): array
	{
		unset($data[$key]);

		return $data;
	}

	/**
	 * What orisai made of the input: null when it refused it
	 *
	 * @return array{json: string, serialized: string}|null
	 *
	 * @throws Utils\JsonException
	 */
	private static function pinned(string $name): array|null
	{
		$pinned = Utils\Json::decode(
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/pinned.json'),
			forceArrays: true,
		);
		assert(is_array($pinned) && isset($pinned[$name]) && is_array($pinned[$name]));

		if (isset($pinned[$name]['refused'])) {
			return null;
		}

		assert(is_string($pinned[$name]['json']) && is_string($pinned[$name]['serialized']));

		return ['json' => $pinned[$name]['json'], 'serialized' => $pinned[$name]['serialized']];
	}

}
