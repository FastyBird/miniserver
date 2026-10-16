<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyRoutedDocument;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;
use TypeError;

/**
 * RoutingDocumentFactory takes a plain array, and sends it through JSON and back before the
 * document is mapped: DocumentFactory reads an array as it is, so an identifier given as a Uuid
 * object would not map without that round trip. An ArrayHash is no longer accepted (#723).
 */
final class RoutingDocumentFactoryTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '7a1e3b5c-2d4f-4a6b-8c0d-9e1f2a3b4c5d';

	/**
	 * @throws Throwable
	 */
	public function testAnArrayMapsToTheRoutedDocument(): void
	{
		$document = $this->factory()->create($this->data(), DummyRoutedDocument::ROUTING_KEY);

		self::assertInstanceOf(DummyRoutedDocument::class, $document);
		self::assertSame(['id' => self::ID, 'tags' => ['exchange', 'routing']], $document->toArray());
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayIsMappedLikeItsJson(): void
	{
		$fromArray = $this->factory()->create($this->data(), DummyRoutedDocument::ROUTING_KEY);
		$fromJson = $this->container->getByType(Documents\DocumentFactory::class)->create(
			DummyRoutedDocument::class,
			Utils\Json::encode($this->data()),
		);

		self::assertSame($fromJson->toArray(), $fromArray->toArray());
	}

	/**
	 * @throws Throwable
	 */
	public function testAnUnknownRoutingKeyIsRefused(): void
	{
		$this->expectException(CoreExceptions\InvalidState::class);

		$this->factory()->create($this->data(), 'unknown.routing.key');
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayHashIsRefused(): void
	{
		$this->expectException(TypeError::class);

		// @phpstan-ignore argument.type (the call is the test: the boundary takes an array only)
		$this->factory()->create(Utils\ArrayHash::from($this->data()), DummyRoutedDocument::ROUTING_KEY);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function data(): array
	{
		return [
			'id' => Uuid\Uuid::fromString(self::ID),
			'tags' => ['exchange', 'routing'],
		];
	}

	/**
	 * @throws Throwable
	 */
	private function factory(): Documents\RoutingDocumentFactory
	{
		return $this->container->getByType(Documents\RoutingDocumentFactory::class);
	}

}
