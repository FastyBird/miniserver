<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Formats;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Formats;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueError;
use function strval;

final class NumberRangeFormatTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testFromString(): void
	{
		$valueObject = new Formats\NumberRange('10:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, 20.0], $valueObject->toArray());
		self::assertEquals('10:20', strval($valueObject));

		$valueObject = new Formats\NumberRange('u8|10:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], 20.0], $valueObject->toArray());
		self::assertEquals('u8|10:20', strval($valueObject));

		$valueObject = new Formats\NumberRange('u8|10.4:f|20.5');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		self::assertEquals('u8|10:f|20.5', strval($valueObject));

		$valueObject = new Formats\NumberRange('10:');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, null], $valueObject->toArray());
		self::assertEquals('10:', strval($valueObject));

		$valueObject = new Formats\NumberRange(':20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([null, 20.0], $valueObject->toArray());
		self::assertEquals(':20', strval($valueObject));

		$valueObject = new Formats\NumberRange('u8|10.4:');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], null], $valueObject->toArray());
		self::assertEquals('u8|10:', strval($valueObject));

		$valueObject = new Formats\NumberRange('u8|10abc:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], 20.0], $valueObject->toArray());
		self::assertEquals('u8|10:20', strval($valueObject));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testFromArray(): void
	{
		$valueObject = new Formats\NumberRange(['10', '20']);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, 20.0], $valueObject->toArray());
		self::assertEquals('10:20', strval($valueObject));

		$valueObject = new Formats\NumberRange([['u8', 10], ['f', 20.5]]);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		self::assertEquals('u8|10:f|20.5', strval($valueObject));

		$valueObject = new Formats\NumberRange([null, '20']);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([null, 20.0], $valueObject->toArray());
		self::assertEquals(':20', strval($valueObject));

		$valueObject = new Formats\NumberRange([['u8', 10], null]);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], null], $valueObject->toArray());
		self::assertEquals('u8|10:', strval($valueObject));
	}

}
