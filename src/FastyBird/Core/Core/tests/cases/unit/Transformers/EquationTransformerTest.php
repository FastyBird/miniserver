<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Transformers;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Transformers\Tools as Transformers;
use PHPUnit\Framework\TestCase;
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

}
