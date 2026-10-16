<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Entities;

use BackedEnum;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Tests;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use function array_key_exists;
use function array_map;
use function array_shift;
use function assert;
use function is_array;
use function is_object;
use function ksort;
use function sort;
use function sprintf;
use function str_replace;

/**
 * Ui's six entity managers take a plain array (#723).
 *
 * Each test makes one change from plain arrays, inside a transaction it rolls back, and checks the
 * result field by field: every mapped field and every association of the entities the change
 * reaches, as the manager returns them and again as read back from the database. The same
 * assertions passed against the code before #723, which built the same graph from an ArrayHash.
 * An ArrayHash is refused with a TypeError.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ManagersInputTest extends Tests\Cases\Unit\DbTestCase
{

	private const string DASHBOARD = '272379d8-8351-44b6-ad8d-73a0abcb7f9c';

	private const string TAB = '3333dd94-0605-45b5-ac51-c08cfb604e64';

	private const string GROUP = 'c74a16b1-67f4-4ffd-812a-9e5ec4bd5263';

	// Room temperature (an analog sensor) and TV light (a digital actuator)
	private const string WIDGET = '15553443-4564-454d-af04-0dfeef08aa96';

	private const string OTHER_WIDGET = '5626e7a1-c42c-4a31-9b5d-848e3cf0e82a';

	private const string DATA_SOURCE = '32dd50e4-4b66-4dea-9bc5-e835f8543dc4';

	// The slider of Ambient light
	private const string DISPLAY = '56a01a18-8da1-4368-824e-3e1b2e28bdf1';

	private const string NEW_DASHBOARD = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d01';

	private const string NEW_NESTED_TAB = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d02';

	private const string NEW_TAB = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d03';

	private const string NEW_GROUP = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d04';

	private const string NEW_WIDGET = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d05';

	private const string NEW_NESTED_DISPLAY = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d06';

	private const string NEW_NESTED_DATA_SOURCE = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d07';

	private const string NEW_DATA_SOURCE = 'e3b7d1c5-4a2f-4d8e-9b6c-0f1a2b3c4d08';

	/**
	 * A dashboard with nested params and a nested tab (a one-to-many item created with it)
	 *
	 * @throws Throwable
	 */
	public function testADashboardCreatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Dashboards\Manager::class);

		$values = static fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_DASHBOARD),
			'identifier' => 'garden',
			'name' => 'Garden',
			'comment' => 'Outside',
			'priority' => 3,
			'params' => ['layout' => ['columns' => 4, 'order' => ['lights', 'heating']]],
			'tabs' => [
				[
					'id' => Uuid\Uuid::fromString(self::NEW_NESTED_TAB),
					'entity' => Entities\Dashboards\Tabs\Tab::class,
					'identifier' => 'pool',
					'name' => 'Pool',
					'priority' => 1,
				],
			],
		];

		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		$dashboard = $fromArray['reloaded']['Dashboards\Dashboard:' . self::NEW_DASHBOARD];
		self::assertSame('garden', $dashboard['identifier']);
		self::assertSame(['layout' => ['columns' => 4, 'order' => ['lights', 'heating']]], $dashboard['params']);
		// The module adds a default tab to every new dashboard
		self::assertSame(
			[
				'Dashboards\Tabs\Tab of ' . self::NEW_DASHBOARD . '/default',
				'Dashboards\Tabs\Tab of ' . self::NEW_DASHBOARD . '/pool',
			],
			$dashboard['tabs'],
		);
		self::assertSame(
			'Pool',
			$fromArray['reloaded']['Dashboards\Tabs\Tab of ' . self::NEW_DASHBOARD . '/pool']['name'],
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testADashboardUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Dashboards\Manager::class);

		$values = static fn (): array => [
			'name' => 'Renamed dashboard',
			'comment' => 'Changed',
			'priority' => 7,
			'params' => ['layout' => ['columns' => 2]],
		];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$dashboard = $this->find(Entities\Dashboards\Dashboard::class, self::DASHBOARD);

			return [$manager->update($dashboard, $values())];
		});

		$dashboard = $fromArray['reloaded']['Dashboards\Dashboard:' . self::DASHBOARD];
		self::assertSame('Renamed dashboard', $dashboard['name']);
		self::assertSame(7, $dashboard['priority']);
		self::assertSame(['layout' => ['columns' => 2]], $dashboard['params']);
	}

	/**
	 * A tab is created on a dashboard with widgets (many-to-many), and updated with others
	 *
	 * @throws Throwable
	 */
	public function testATabCreatedAndUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Dashboards\Tabs\Manager::class);

		$change = function () use ($manager): array {
			$tab = $manager->create([
				'id' => Uuid\Uuid::fromString(self::NEW_TAB),
				'dashboard' => $this->find(Entities\Dashboards\Dashboard::class, self::DASHBOARD),
				'identifier' => 'kitchen',
				'name' => 'Kitchen',
				'widgets' => [$this->find(Entities\Widgets\Widget::class, self::WIDGET)],
			]);

			return [$manager->update($tab, [
				'name' => 'Kitchen and dining',
				'priority' => 2,
				'widgets' => [
					$this->find(Entities\Widgets\Widget::class, self::WIDGET),
					$this->find(Entities\Widgets\Widget::class, self::OTHER_WIDGET),
				],
			])];
		};

		$fromArray = $this->change($change);

		$tab = $fromArray['reloaded']['Dashboards\Tabs\Tab of ' . self::DASHBOARD . '/kitchen'];
		self::assertSame('Kitchen and dining', $tab['name']);
		self::assertSame(
			['Widgets\AnalogSensor:' . self::WIDGET, 'Widgets\DigitalActuator:' . self::OTHER_WIDGET],
			$tab['widgets'],
		);
	}

	/**
	 * A group is created with widgets (many-to-many), and updated with others
	 *
	 * @throws Throwable
	 */
	public function testAGroupCreatedAndUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Groups\Manager::class);

		$change = function () use ($manager): array {
			$group = $manager->create([
				'id' => Uuid\Uuid::fromString(self::NEW_GROUP),
				'identifier' => 'garage',
				'name' => 'Garage',
				'params' => ['icon' => ['name' => 'car']],
				'widgets' => [$this->find(Entities\Widgets\Widget::class, self::OTHER_WIDGET)],
			]);

			$updated = $manager->update($group, [
				'comment' => 'Cars',
				'widgets' => [$this->find(Entities\Widgets\Widget::class, self::WIDGET)],
			]);

			$existing = $this->find(Entities\Groups\Group::class, self::GROUP);

			return [$updated, $manager->update(
				$existing,
				['name' => 'Heaters', 'priority' => 5],
			)];
		};

		$fromArray = $this->change($change);

		$group = $fromArray['reloaded']['Groups\Group:' . self::NEW_GROUP];
		self::assertSame('Cars', $group['comment']);
		self::assertSame(['icon' => ['name' => 'car']], $group['params']);
		self::assertSame(['Widgets\AnalogSensor:' . self::WIDGET], $group['widgets']);
		self::assertSame('Heaters', $fromArray['reloaded']['Groups\Group:' . self::GROUP]['name']);
	}

	/**
	 * A widget with its nested display (a one-to-one created with it), a nested data source (a
	 * one-to-many item) and a tab and a group (many-to-many), then updated
	 *
	 * @throws Throwable
	 */
	public function testAWidgetCreatedAndUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Widgets\Manager::class);

		$change = function () use ($manager): array {
			$widget = $manager->create([
				'id' => Uuid\Uuid::fromString(self::NEW_WIDGET),
				'entity' => Entities\Widgets\AnalogActuator::class,
				'identifier' => 'garden-light',
				'name' => 'Garden light',
				'params' => ['color' => ['hue' => 30, 'tones' => [1, 2]]],
				'display' => [
					'id' => Uuid\Uuid::fromString(self::NEW_NESTED_DISPLAY),
					'entity' => Entities\Widgets\Displays\Slider::class,
					'minimumValue' => 0.0,
					'maximumValue' => 100.0,
					'stepValue' => 5.0,
					'precision' => 1,
				],
				'dataSources' => [
					[
						'id' => Uuid\Uuid::fromString(self::NEW_NESTED_DATA_SOURCE),
						'entity' => Entities\Widgets\DataSources\Generic::class,
						'params' => ['channel' => ['property' => 'brightness']],
					],
				],
				'tabs' => [$this->find(Entities\Dashboards\Tabs\Tab::class, self::TAB)],
				'groups' => [$this->find(Entities\Groups\Group::class, self::GROUP)],
			]);

			return [$manager->update($widget, [
				'name' => 'Garden lights',
				'params' => ['color' => ['hue' => 40]],
				'groups' => [],
			])];
		};

		$fromArray = $this->change($change);

		$widget = $fromArray['reloaded']['Widgets\AnalogActuator:' . self::NEW_WIDGET];
		self::assertSame('Garden lights', $widget['name']);
		self::assertSame('Widgets\Displays\Slider:' . self::NEW_NESTED_DISPLAY, $widget['display']);
		self::assertSame(['Widgets\DataSources\Generic:' . self::NEW_NESTED_DATA_SOURCE], $widget['dataSources']);
		self::assertSame(['Dashboards\Tabs\Tab of ' . self::DASHBOARD . '/default'], $widget['tabs']);
		// An empty list leaves the groups as they are, from either input
		self::assertSame(['Groups\Group:' . self::GROUP], $widget['groups']);
	}

	/**
	 * @throws Throwable
	 */
	public function testADataSourceCreatedAndUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Widgets\DataSources\Manager::class);

		$change = function () use ($manager): array {
			$created = $manager->create([
				'id' => Uuid\Uuid::fromString(self::NEW_DATA_SOURCE),
				'entity' => Entities\Widgets\DataSources\Generic::class,
				'widget' => $this->find(Entities\Widgets\Widget::class, self::WIDGET),
				'params' => ['device' => ['property' => 'temperature', 'scale' => [0, 40]]],
			]);

			$existing = $this->find(Entities\Widgets\DataSources\DataSource::class, self::DATA_SOURCE);

			return [$created, $manager->update(
				$existing,
				['params' => ['device' => ['scale' => [1]]]],
			)];
		};

		$fromArray = $this->change($change);

		$dataSource = $fromArray['reloaded']['Widgets\DataSources\Generic:' . self::NEW_DATA_SOURCE];
		self::assertSame(['device' => ['property' => 'temperature', 'scale' => [0, 40]]], $dataSource['params']);
		self::assertSame('Widgets\AnalogSensor:' . self::WIDGET, $dataSource['widget']);
	}

	/**
	 * @throws Throwable
	 */
	public function testADisplayUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Widgets\Displays\Manager::class);

		$values = static fn (): array => [
			'minimumValue' => 10.0,
			'maximumValue' => 30.0,
			'stepValue' => 0.5,
			'precision' => 2,
		];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$display = $this->find(Entities\Widgets\Displays\Display::class, self::DISPLAY);

			return [$manager->update($display, $values())];
		});

		$display = $fromArray['reloaded']['Widgets\Displays\Slider:' . self::DISPLAY];
		self::assertSame(
			['precision' => 2, 'stepValue' => 0.5, 'maximumValue' => 30.0, 'minimumValue' => 10.0],
			$display['params'],
		);
	}

	/**
	 * The managers take an array only: an ArrayHash is refused with a TypeError before any entity
	 * is touched (#723), on create() and on update()
	 *
	 * @throws Throwable
	 */
	public function testTheManagersRefuseAnArrayHash(): void
	{
		$container = $this->getContainer();
		$hash = Utils\ArrayHash::from(['name' => 'Refused']);
		$entity = $this->find(Entities\Dashboards\Dashboard::class, self::DASHBOARD);
		$dashboards = $container->getByType(Models\Entities\Dashboards\Manager::class);
		$widgets = $container->getByType(Models\Entities\Widgets\Manager::class);
		$groups = $container->getByType(Models\Entities\Groups\Manager::class);

		foreach (
			[
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $dashboards->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $dashboards->update($entity, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $widgets->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $groups->create($hash),
			] as $call
		) {
			self::assertTrue(self::refuses($call));
		}
	}

	/**
	 * Makes a change inside a transaction it rolls back, and records the graph reachable from the
	 * entities the change returns, as returned and as read back from the database
	 *
	 * @param callable(): list<object> $change
	 *
	 * @return array{returned: array<string, array<string, mixed>>, reloaded: array<string, array<string, mixed>>}
	 *
	 * @throws Throwable
	 */
	private function change(callable $change): array
	{
		$entityManager = $this->getEntityManager();
		$connection = $entityManager->getConnection();

		$connection->beginTransaction();

		try {
			$entities = $change();

			$returned = $this->dumpGraph($entities);

			$entityManager->clear();

			$reloaded = $this->dumpGraph(array_map(
				function (object $entity): object {
					assert($entity instanceof Entities\Entity);

					return $this->find($entity::class, $entity->getId()->toString());
				},
				$entities,
			));
		} finally {
			$connection->rollBack();
			$entityManager->clear();
		}

		return ['returned' => $returned, 'reloaded' => $reloaded];
	}

	/**
	 * @param list<object> $queue
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws Throwable
	 */
	private function dumpGraph(array $queue): array
	{
		$entityManager = $this->getEntityManager();

		$graph = [];

		while ($queue !== []) {
			$entity = array_shift($queue);

			$reference = $this->reference($entity);

			if (array_key_exists($reference, $graph)) {
				continue;
			}

			$metadata = $entityManager->getClassMetadata($entity::class);

			$record = [];

			foreach ($metadata->getFieldNames() as $field) {
				$record[$field] = $this->normalize($metadata->getFieldValue($entity, $field));
			}

			if ($entity instanceof Entities\Dashboards\Tabs\Tab) {
				// The module adds a default tab, with a generated id, to every new dashboard, so the
				// reference names a tab instead
				unset($record['id']);
			}

			foreach ($metadata->getAssociationNames() as $association) {
				$value = $metadata->getFieldValue($entity, $association);

				if ($value instanceof Collection) {
					$references = [];

					foreach ($value as $item) {
						assert(is_object($item));

						$references[] = $this->reference($item);
						$queue[] = $item;
					}

					sort($references);

					$record[$association] = $references;
				} elseif (is_object($value)) {
					$record[$association] = $this->reference($value);
					$queue[] = $value;
				} else {
					$record[$association] = null;
				}
			}

			ksort($record);

			$graph[$reference] = $record;
		}

		ksort($graph);

		return $graph;
	}

	/**
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	private function find(string $class, string $id): object
	{
		$entity = $this->getEntityManager()->find($class, Uuid\Uuid::fromString($id));
		assert($entity instanceof $class);

		return $entity;
	}

	private function reference(object $entity): string
	{
		assert($entity instanceof Entities\Entity);

		$class = str_replace('FastyBird\\Module\\Ui\\Entities\\', '', $entity::class);

		if ($entity instanceof Entities\Dashboards\Tabs\Tab) {
			return sprintf(
				'%s of %s/%s',
				$class,
				$entity->getDashboard()->getId()->toString(),
				$entity->getIdentifier(),
			);
		}

		return sprintf('%s:%s', $class, $entity->getId()->toString());
	}

	private function normalize(mixed $value): mixed
	{
		if (is_array($value)) {
			return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
		}

		if ($value instanceof Uuid\UuidInterface) {
			return $value->toString();
		}

		if ($value instanceof BackedEnum) {
			return sprintf('%s::%s', $value::class, (string) $value->value);
		}

		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		}

		if (is_object($value)) {
			return sprintf('object:%s', $value::class);
		}

		return $value;
	}

	/**
	 * Whether a call is refused with a TypeError
	 *
	 * @param callable(): mixed $call
	 */
	private static function refuses(callable $call): bool
	{
		try {
			$call();
		} catch (TypeError) {
			return true;
		}

		return false;
	}

}
