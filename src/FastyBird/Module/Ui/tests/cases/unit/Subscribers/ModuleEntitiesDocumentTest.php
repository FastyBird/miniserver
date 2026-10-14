<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Subscribers;
use FastyBird\Module\Ui\Tests;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;

/**
 * The document ModuleEntities publishes for a Ui entity, built by the real routing document
 * factory from the entity's array (#723: a plain array since, an ArrayHash of it before). The
 * arrays carry nested lists, the ids of a dashboard's tabs and of a widget's tabs, groups and
 * data sources. The documents are pinned field by field and pass on both.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ModuleEntitiesDocumentTest extends Tests\Cases\Unit\DbTestCase
{

	private const string DASHBOARD = '272379d8-8351-44b6-ad8d-73a0abcb7f9c';

	private const string TAB = '3333dd94-0605-45b5-ac51-c08cfb604e64';

	private const string GROUP = '89f4a14f-7f78-4216-99b8-584ab9229f1c';

	// Room temperature, an analog sensor, with its chart graph and its data source
	private const string WIDGET = '15553443-4564-454d-af04-0dfeef08aa96';

	private const string DISPLAY = '467e6d4d-3545-481b-b613-53be7e9aa641';

	private const string DATA_SOURCE = '764937a7-8565-472e-8e12-fe97cd55a377';

	/** @var list<array{int|string, string, class-string|null, array<string, mixed>|null}> */
	private array $published = [];

	/**
	 * @throws Throwable
	 */
	public function testACreatedDashboardIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Dashboards\Dashboard::class, self::DASHBOARD));

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Ui\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY,
					UiDocuments\Dashboards\Dashboard::class,
					[
						'id' => self::DASHBOARD,
						'source' => Sources\Module::UI->value,
						'identifier' => 'main-dashboard',
						'name' => 'Main dashboard',
						'comment' => null,
						'priority' => 0,
						'tabs' => [self::TAB],
						'owner' => null,
						'created_at' => '2020-05-28T10:43:58+02:00',
						'updated_at' => '2020-05-28T10:43:58+02:00',
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testACreatedWidgetIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Widgets\Widget::class, self::WIDGET));

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Ui\Constants::MESSAGE_BUS_WIDGET_DOCUMENT_CREATED_ROUTING_KEY,
					UiDocuments\Widgets\AnalogSensor::class,
					[
						'id' => self::WIDGET,
						'type' => 'analog-sensor',
						'source' => Sources\Module::UI->value,
						'identifier' => 'room-temperature',
						'name' => 'Room temperature',
						'display' => self::DISPLAY,
						'data_sources' => [self::DATA_SOURCE],
						'tabs' => [self::TAB],
						'groups' => [self::GROUP],
						'owner' => null,
						'created_at' => '2020-05-28T12:29:32+02:00',
						'updated_at' => '2020-05-28T12:29:32+02:00',
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testACreatedDisplayIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Widgets\Displays\Display::class, self::DISPLAY));

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Ui\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY,
					UiDocuments\Widgets\Displays\ChartGraph::class,
					[
						'id' => self::DISPLAY,
						'type' => 'chart-graph',
						'source' => Sources\Module::UI->value,
						'widget' => self::WIDGET,
						'owner' => null,
						'created_at' => '2020-05-28T12:29:32+02:00',
						'updated_at' => '2020-05-28T12:29:32+02:00',
						'minimum_value' => 5.0,
						'maximum_value' => 40.0,
						'step_value' => 0.1,
						'enable_min_max' => false,
						'precision' => 0,
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * Hands the entity to the subscriber as Doctrine does after persisting it, with a publisher
	 * that records what it is given
	 *
	 * @throws Throwable
	 */
	private function persisted(object $entity): void
	{
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
		$publisher->method('publish')->willReturnCallback(
			function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			): bool {
				$this->published[] = [
					$source->value,
					$routingKey,
					$document !== null ? $document::class : null,
					$document?->toArray(),
				];

				return true;
			},
		);

		$entityManager = $this->getEntityManager();

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$this->getContainer()->getByType(EventLoop\Status::class),
			$this->getContainer()->getByType(CoreDocuments\RoutingDocumentFactory::class),
			$publisher,
			$this->createMock(Publisher\Async\MessagePublisher::class),
		);

		$subscriber->postPersist(new ORM\Event\PostPersistEventArgs($entity, $entityManager));
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
		self::assertInstanceOf($class, $entity);

		return $entity;
	}

}
