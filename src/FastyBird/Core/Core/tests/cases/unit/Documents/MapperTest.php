<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyRoutedDocument;
use Orisai\ObjectMapper;
use Throwable;

/**
 * Documents\Mapping\Mapper is the one place every consumer maps a plain array to an object (#724 PR1): what it maps, what
 * it refuses, which option decides it, and the text a refusal carries, which every package builder puts in its own
 * exception
 */
final class MapperTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Throwable
	 */
	public function testItMapsDataToTheRequestedClass(): void
	{
		$document = $this->mapper()->map(['id' => 'one', 'tags' => ['a', 'b']], DummyRoutedDocument::class);

		self::assertSame(['id' => 'one', 'tags' => ['a', 'b']], $document->toArray());
	}

	/**
	 * @throws Throwable
	 */
	public function testItRefusesUnknownFieldsByDefault(): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(['id' => 'one', 'extra' => 'x'], DummyRoutedDocument::class);
	}

	/**
	 * @throws Throwable
	 */
	public function testItIgnoresUnknownFieldsWhenTheOptionAllowsThem(): void
	{
		$document = $this->mapper()->map(
			['id' => 'one', 'extra' => 'x'],
			DummyRoutedDocument::class,
			new Documents\Mapping\MappingOptions(allowUnknownFields: true),
		);

		self::assertSame(['id' => 'one', 'tags' => []], $document->toArray());
	}

	/**
	 * @throws Throwable
	 */
	public function testItStillRefusesInvalidDataWhenUnknownFieldsAreAllowed(): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(
			['id' => '', 'extra' => 'x'],
			DummyRoutedDocument::class,
			new Documents\Mapping\MappingOptions(allowUnknownFields: true),
		);
	}

	/**
	 * A refusal carries the text orisai's own printer makes of the failure, not a summary of it, and keeps the failure
	 * it came from
	 *
	 * @throws Throwable
	 */
	public function testARefusalCarriesTheTextOrisaiPrints(): void
	{
		$data = ['id' => '', 'tags' => 'not a list', 'extra' => 'x'];

		try {
			$this->container->getByType(ObjectMapper\Processing\Processor::class)->process(
				$data,
				DummyRoutedDocument::class,
			);

			self::fail('orisai accepted the data');
		} catch (ObjectMapper\Exception\InvalidData $expected) {
			$printer = new ObjectMapper\Printers\ErrorVisualPrinter(new ObjectMapper\Printers\TypeToStringConverter());
			$printed = $printer->printError($expected);
		}

		try {
			$this->mapper()->map($data, DummyRoutedDocument::class);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertNotSame('', $printed);
			self::assertSame($printed, $ex->getMessage());
			self::assertInstanceOf(ObjectMapper\Exception\InvalidData::class, $ex->getPrevious());
		}
	}

	/**
	 * @throws Throwable
	 */
	private function mapper(): Documents\Mapping\Mapper
	{
		return $this->container->getByType(Documents\Mapping\Mapper::class);
	}

}
