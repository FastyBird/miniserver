<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Documents\Events;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Tests;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_merge;
use function file_get_contents;

final class DocumentTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelProperty')]
	public function testCreateDocument(string $data, string $class, array $fixture): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelPropertyInvalid')]
	public function testCreateDocumentInvalid(string $data, string $class): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		/** @var class-string<Throwable> $exception */
		$exception = CoreExceptions\Exception::class;

		$this->expectException($exception);

		$factory->create($class, $data);
	}

	/**
	 * @param array<mixed> $modify
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('preLoadEvent')]
	public function testPreLoadEvent(string $data, array $modify, string $class, array $fixture): void
	{
		$eventDispatcher = $this->container->getByType(EventDispatcher\EventDispatcherInterface::class);

		$eventDispatcher->addListener(
			Events\PreLoad::class,
			static function (Events\PreLoad $event) use ($modify): void {
				$data = $event->getData();

				$data = array_merge($data, $modify);

				$event->setData($data);
			},
		);

		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function channelProperty(): array
	{
		return [
			'dummy_one' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.one.json'),
				Tests\Fixtures\Dummy\DummyOneDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'dummy-document-one',
					'name' => null,
					'comment' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dummy_two' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'dummy-document-two',
					'name' => 'With some name',
					'comment' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.missing.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
			],
			'type-mismatch' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.mismatch.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
			],
		];
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function preLoadEvent(): array
	{
		return [
			'dummy_one' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.one.json'),
				[
					'identifier' => 'event-changed',
				],
				Tests\Fixtures\Dummy\DummyOneDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'event-changed',
					'name' => null,
					'comment' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dummy_two' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'),
				[
					'name' => 'Event changed',
				],
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'dummy-document-two',
					'name' => 'Event changed',
					'comment' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
			],
		];
	}

}
