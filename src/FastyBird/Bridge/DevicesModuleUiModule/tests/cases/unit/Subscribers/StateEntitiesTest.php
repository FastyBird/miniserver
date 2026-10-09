<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Bridge\DevicesModuleUiModule\Subscribers;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async as PublisherAsync;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\Caching as NetteCaching;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;

/**
 * A change of the state of a property that a widget data source reads publishes the data source's
 * document, which is the widget's state and is built to carry the state's value, the expected one
 * if the state has one. The caches that data source is read from are cleaned for every change,
 * whatever its source, so what is read stays current. The document is published only for what a
 * target reports. With the Devices source the state is a command's (a WebSocket SET), and the
 * document would tell the Ui clients of the other ws-servers about it, which a SET must not do
 * (#679).
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StateEntitiesTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CHANNEL_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	private const string DATA_SOURCE = '32dd50e4-4b66-4dea-9bc5-e835f8543dc4';

	/**
	 * @throws Throwable
	 */
	public function testTheStateASetWritesCleansTheCachesAndPublishesNoDataSource(): void
	{
		$published = [];

		$subscriber = $this->subscriber($published);

		$subscriber->stateUpdated($this->event(Sources\Module::DEVICES));

		self::assertSame([], $published);
	}

	/**
	 * @throws Throwable
	 */
	public function testTheStateTheTargetReportsPublishesTheDataSourceOnce(): void
	{
		$published = [];

		$subscriber = $this->subscriber($published);

		$subscriber->stateUpdated($this->event(Sources\Connector::VIRTUAL));

		self::assertCount(1, $published);
		self::assertSame(Sources\Bridge::DEVICES_MODULE_UI_MODULE->value, $published[0]['source']);
		self::assertSame(
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$published[0]['routing_key'],
		);

		$document = $published[0]['document'];

		self::assertInstanceOf(DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::class, $document);
		self::assertSame(self::DATA_SOURCE, $document->getId()->toString());
		self::assertSame(self::CHANNEL_PROPERTY, $document->getProperty()->toString());
	}

	/**
	 * The subscriber, with caches that count how often they are cleaned (once each, for the one
	 * data source that reads the property) and a publisher that records what is published
	 *
	 * @param list<array{source: int|string, routing_key: string, document: CoreDocuments\Document|null}> $published
	 *
	 * @throws Throwable
	 */
	private function subscriber(array &$published): Subscribers\StateEntities
	{
		$container = $this->getContainer();

		$recorder = $this->createMock(Publisher\MessagePublisher::class);
		$recorder->method('publish')->willReturnCallback(
			static function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			) use (&$published): bool {
				$published[] = ['source' => $source->value, 'routing_key' => $routingKey, 'document' => $document];

				return true;
			},
		);

		$container->getByType(Publisher\Container::class)->register($recorder);

		$builderCache = $this->createMock(NetteCaching\Cache::class);
		$builderCache->expects(self::once())->method('clean');

		$repositoryCache = $this->createMock(NetteCaching\Cache::class);
		$repositoryCache->expects(self::once())->method('clean');

		return new Subscribers\StateEntities(
			$container->getByType(UiModels\Configuration\Widgets\DataSources\Repository::class),
			new UiCaching\Container($builderCache, $repositoryCache),
			$container->getByType(EventLoop\Status::class),
			$container->getByType(Publisher\Container::class),
			$container->getByType(PublisherAsync\Container::class),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function event(Sources\Source $source): DevicesEvents\ChannelPropertyStateEntityUpdated
	{
		$property = $this->getContainer()->getByType(DevicesModels\Configuration\Channels\Properties\Repository::class)
			->find(Uuid\Uuid::fromString(self::CHANNEL_PROPERTY));

		self::assertInstanceOf(DevicesDocuments\Channels\Properties\Dynamic::class, $property);

		$state = new Tests\Fixtures\Dummy\ChannelPropertyState($property->getId(), null, 'on', false, true);

		return new DevicesEvents\ChannelPropertyStateEntityUpdated($property, $state, $state, $source);
	}

}
