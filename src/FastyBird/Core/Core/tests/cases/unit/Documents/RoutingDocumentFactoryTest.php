<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyRoutedDocument;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;

/**
 * RoutingDocumentFactory takes a plain array as well as an ArrayHash (#723, D3), and turns the
 * array into the ArrayHash Utils\ArrayHash::from() makes of it. DocumentFactory serialises an
 * object to JSON and back but reads an array as it is, so without that the two inputs would map
 * differently: an identifier given as a Uuid object maps only after the round trip.
 */
final class RoutingDocumentFactoryTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '7a1e3b5c-2d4f-4a6b-8c0d-9e1f2a3b4c5d';

	/**
	 * @throws Throwable
	 */
	public function testAnArrayHashMapsToTheRoutedDocument(): void
	{
		$document = $this->factory()->create(Utils\ArrayHash::from($this->data()), DummyRoutedDocument::ROUTING_KEY);

		self::assertInstanceOf(DummyRoutedDocument::class, $document);
		self::assertSame(['id' => self::ID, 'tags' => ['exchange', 'routing']], $document->toArray());
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayMapsToTheSameDocument(): void
	{
		$fromHash = $this->factory()->create(Utils\ArrayHash::from($this->data()), DummyRoutedDocument::ROUTING_KEY);
		$fromArray = $this->factory()->create($this->data(), DummyRoutedDocument::ROUTING_KEY);

		self::assertInstanceOf(DummyRoutedDocument::class, $fromArray);
		self::assertSame($fromHash->toArray(), $fromArray->toArray());
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
