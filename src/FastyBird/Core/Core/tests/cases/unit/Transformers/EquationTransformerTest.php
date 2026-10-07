<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Transformers;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Transformers;
use PHPUnit\Framework\TestCase;
use function preg_match;
use function strval;

final class EquationTransformerTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testFromString(): void
	{
		$valueObject = new Transformers\EquationTransformer('equation:x=10y + 2');

		self::assertEquals('equation:x=10y+2', $valueObject->getValue());
		self::assertEquals('equation:x=10y+2', strval($valueObject));

		$valueObject = new Transformers\EquationTransformer('equation:x=(10y + 2) * 10');

		self::assertEquals('equation:x=(10y+2)*10', $valueObject->getValue());
		self::assertEquals('equation:x=(10y+2)*10', strval($valueObject));

		$valueObject = new Transformers\EquationTransformer('equation:x=(10y + 2) * 10|y=10x - 50');

		self::assertEquals('equation:x=(10y+2)*10|y=10x-50', $valueObject->getValue());
		self::assertEquals('equation:x=(10y+2)*10|y=10x-50', strval($valueObject));
	}

	public function testValueEquationTransform(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=10y + 2',
		));
		self::assertSame(1, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=(10y + 2) * 10',
		));
		self::assertSame(1, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=(10y + 2) * 10|y=x + 2 / 3',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=10x + 2',
		));
		self::assertSame(0, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=10a + 2',
		));
		self::assertSame(0, preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=[10y + 2] * 10',
		));
		self::assertFalse(preg_match(
			Transformers\EquationTransformer::PATTERN,
			'equation:x=(10y + 2) * 10|y=y + 2 / 3',
		));
	}

}
