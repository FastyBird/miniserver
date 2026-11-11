<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * MapIntToFloat: an int that is mapped is a float, as it was when orisai tried a float before an int, and nothing else
 * changes. The constructor is not mapped, so that an int passed to it is kept
 */
final class MapIntToFloatTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testOnlyAnIntBecomesAFloat(mixed $value, mixed $expected): void
	{
		$document = $this->container->getByType(Documents\Mapping\Mapper::class)->map(
			['value' => $value],
			Dummy\DummyScalarDocument::class,
		);

		self::assertSame($expected, $document->getValue());
	}

	public function testTheConstructorKeepsAnInt(): void
	{
		self::assertSame(21, (new Dummy\DummyScalarDocument(21))->getValue());
	}

	/**
	 * @return array<string, array{mixed, mixed}>
	 */
	public static function values(): array
	{
		return [
			'an int' => [21, 21.0],
			'zero' => [0, 0.0],
			'a negative int' => [-50, -50.0],
			'a float' => [21.5, 21.5],
			'a text' => ['on', 'on'],
			'a text of a number' => ['21', '21'],
			'a blank text' => [' ', null],
			'true' => [true, true],
			'false' => [false, false],
			'null' => [null, null],
		];
	}

}
