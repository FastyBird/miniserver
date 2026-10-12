<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Values;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Transformers;
use FastyBird\Core\Values\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function intval;
use function round;

/**
 * What `Transformers\EquationTransformer` calculates (#443).
 *
 * PR1 pinned the results of `mathsolver/mathsolver` here as the safety net for replacing it with
 * `nxp/math-executor` (PR2). Two tiers remain:
 *
 *  1. PINNED: intended behaviour. Every provider except `provideLenientMalformed` is this tier. It
 *     includes the 15 rows PR1 recorded as known defects of mathsolver (`:` multiplied or was not
 *     parsed, `y^0.5` gave 0, a value printed in exponent notation gave 0), which PR2 fixed.
 *  2. ACCIDENTAL (`provideLenientMalformed`): malformed text that still evaluates to a number. It is
 *     pinned so that a later change shows up, not promised. Most of what mathsolver accepted here
 *     is now rejected; those rows are in `provideRejectedMalformed`.
 *
 * The x-equation (`equation:x=…`) is applied by `calculateEquationFrom()` with `y` bound to the
 * value; the y-equation (`…|y=…`) by `calculateEquationTo()` with `x` bound to the value.
 */
final class EquationTransformerCalculationTest extends TestCase
{

	private const float DELTA = 1e-9;

	/**
	 * @return array<string, array{string, int|float, float}>
	 */
	public static function provideFromEquations(): array
	{
		return [
			// The four operators
			'addition' => ['equation:x=y+2', 10, 12.0],
			'subtraction' => ['equation:x=y-2', 10, 8.0],
			'multiplication' => ['equation:x=y*2', 10, 20.0],
			'division' => ['equation:x=y/4', 10, 2.5],
			'division, repeating decimal' => ['equation:x=y/3', 10, 10 / 3],
			'identity' => ['equation:x=y', 7, 7.0],
			'constant, value ignored' => ['equation:x=7', 3, 7.0],
			'decimal constant, value ignored' => ['equation:x=0.5', 3, 0.5],
			'operand on the left' => ['equation:x=100-y', 30, 70.0],
			'variable twice' => ['equation:x=y*y', 3, 9.0],
			'variable divided by itself' => ['equation:x=y/y', 4, 1.0],

			// Decimals
			'decimal factor' => ['equation:x=y*0.5', 10, 5.0],
			'decimal factor above one' => ['equation:x=y*1.5', 3, 4.5],
			'decimal divisor, unit conversion' => ['equation:x=y/2.54', 254, 100.0],
			'decimal factor, unit conversion' => ['equation:x=y*2.54', 100, 254.0],
			'decimal input' => ['equation:x=y+1', 1.5, 2.5],
			'decimal input kept' => ['equation:x=y', 0.1, 0.1],
			'decimal sum is not 0.30000000000000004' => ['equation:x=0.1+0.2', 1, 0.3],
			'decimal input times three' => ['equation:x=y*3', 0.1, 0.3],
			'small decimal input' => ['equation:x=y*2', 1.0E-5, 2.0E-5],
			'value large enough for exponent notation' => ['equation:x=y*2', 1.0E+25, 2.0E+25],
			'large integer product' => ['equation:x=y*1000000', 1_000_000, 1_000_000_000_000.0],
			'fixture: scale by ten' => ['equation:x=y*10', 5, 50.0],
			'fixture: divide by ten' => ['equation:x=y/10', 50, 5.0],

			// Precedence and parentheses
			'multiplication before addition' => ['equation:x=2+3*y', 2, 8.0],
			'parentheses override precedence' => ['equation:x=(2+3)*y', 2, 10.0],
			'two products added' => ['equation:x=2*3+4*5', 1, 26.0],
			'mixed operators' => ['equation:x=2+3*4-5', 1, 9.0],
			'subtraction is left associative' => ['equation:x=y-2-3', 10, 5.0],
			'parenthesised subtraction' => ['equation:x=y-(2-3)', 10, 11.0],
			'division is left associative' => ['equation:x=y/2/5', 100, 10.0],
			'division is left associative, variable in the middle' => ['equation:x=100/y/5', 4, 5.0],
			'parenthesised divisor' => ['equation:x=y/(2*5)', 100, 10.0],
			'power before multiplication' => ['equation:x=y*2^2', 3, 12.0],
			'power before multiplication, power first' => ['equation:x=2^2*y', 3, 12.0],
			'whitespace is ignored' => ['equation:x=( y + 1 ) * 2', 3, 8.0],
			'leading whitespace' => ['equation:x= y', 3, 3.0],

			// Unary minus and negative values
			'negated value' => ['equation:x=-y', 10, -10.0],
			'negated value of a negative' => ['equation:x=-y', -3, 3.0],
			'negation then addition' => ['equation:x=-y+3', 10, -7.0],
			'negated operand of a product' => ['equation:x=3*-y', 10, -30.0],
			'double minus is plus' => ['equation:x=3--y', 2, 5.0],
			'parenthesised negation' => ['equation:x=3-(-y)', 2, 5.0],
			'plus minus' => ['equation:x=3+-y', 2, 1.0],
			'negative exponent' => ['equation:x=2^-1', 3, 0.5],
			'negative value, subtraction' => ['equation:x=y-3', -3, -6.0],
			'negative value, subtracted from' => ['equation:x=2-y', -3, 5.0],
			'negative value, product' => ['equation:x=2*y', -3, -6.0],
			'negative value, square' => ['equation:x=y^2', -3, 9.0],
			'negative value, implicit multiplication' => ['equation:x=10y+2', -5, -48.0],
			'unary minus binds looser than power' => ['equation:x=-y^2', 3, -9.0],
			'unary minus binds looser than power, literal' => ['equation:x=-2^2', 3, -4.0],
			'parenthesised negation, then power' => ['equation:x=(-y)^2', 3, 9.0],

			// Power
			'square' => ['equation:x=y^2', 3, 9.0],
			'variable exponent' => ['equation:x=2^y', 3, 8.0],
			'zero to the zero' => ['equation:x=y^0', 0, 1.0],
			'fractional exponent' => ['equation:x=y^0.5', 16, 4.0],
			'root through a parenthesised fraction' => ['equation:x=y^(1/2)', 16, 4.0],
			'power is right associative' => ['equation:x=2^3^2', 1, 512.0],
			'power is right associative, base is the variable' => ['equation:x=y^3^2', 2, 512.0],
			'power is right associative, exponent is the variable' => ['equation:x=2^3^y', 2, 512.0],
			'power is right associative, variable in the middle' => ['equation:x=2^y^2', 2, 16.0],
			'power is right associative, three levels' => ['equation:x=2^3^2^1', 1, 512.0],
			'left-grouped power needs parentheses' => ['equation:x=(2^3)^2', 1, 64.0],
			'right-grouped power in parentheses' => ['equation:x=2^(3^2)', 1, 512.0],

			// Colon is division
			'colon: variable over number' => ['equation:x=y:4', 10, 2.5],
			'colon: number over variable' => ['equation:x=100:y', 4, 25.0],
			'colon: two numbers' => ['equation:x=10:2', 1, 5.0],
			'colon: chained is left associative' => ['equation:x=8:2:2', 1, 2.0],
			'colon: chained with the variable' => ['equation:x=y:2:5', 20, 2.0],
			'colon: after a product' => ['equation:x=y*2:4', 10, 5.0],
			'colon: precedence equals division' => ['equation:x=y+8:2', 2, 6.0],
			'colon: parenthesised divisor' => ['equation:x=y:(2*2)', 10, 2.5],
			'colon: parenthesised dividend' => ['equation:x=(y):4', 10, 2.5],
			'colon: decimal divisor' => ['equation:x=y:0.5', 3, 6.0],
			'colon: same result as slash' => ['equation:x=y:2', 10, 5.0],

			// Implicit multiplication
			'number then variable' => ['equation:x=10y+2', 3, 32.0],
			'number then variable, alone' => ['equation:x=2y', 3, 6.0],
			'variable then number' => ['equation:x=y2', 3, 6.0],
			'decimal then variable' => ['equation:x=.5y', 3, 1.5],
			'variable then decimal' => ['equation:x=y.5', 3, 1.5],
			'number then parenthesis' => ['equation:x=2(y+1)', 3, 8.0],
			'parenthesis then number' => ['equation:x=(y+1)2', 3, 8.0],
			'two parentheses' => ['equation:x=(y+1)(y+2)', 3, 20.0],
			'number and parenthesised number' => ['equation:x=2(3)', 3, 6.0],
			'two parenthesised numbers' => ['equation:x=(2)(3)', 3, 6.0],
			'variable then parenthesis' => ['equation:x=y(y+1)', 3, 12.0],
			'variable then parenthesised number' => ['equation:x=y(2)', 3, 6.0],
			'number, variable, parenthesis' => ['equation:x=3y(2)', 3, 18.0],
			'space between number and variable' => ['equation:x=2 y', 3, 6.0],
			'space between two variables' => ['equation:x=y y', 3, 9.0],
			'two terms' => ['equation:x=2y+3y', 3, 15.0],
			'implicit multiplication keeps the power tight' => ['equation:x=2y^2', 3, 18.0],
			'implicit multiplication with parenthesised power' => ['equation:x=2(y)^2', 3, 18.0],
		];
	}

	/**
	 * @return array<string, array{string, int|float, float}>
	 */
	public static function provideToEquations(): array
	{
		return [
			'identity' => ['equation:x=y|y=x', 7, 7.0],
			'addition' => ['equation:x=y|y=x+1', 5, 6.0],
			'subtraction' => ['equation:x=y+1|y=x-1', 5, 4.0],
			'multiplication' => ['equation:x=y/3|y=x*3', 10, 30.0],
			'division' => ['equation:x=y*10|y=x/10', 50, 5.0],
			'colon is division' => ['equation:x=y|y=x:4', 10, 2.5],
			'decimal factor' => ['equation:x=y|y=x*0.1', 55, 5.5],
			'fixture: inches to centimetres' => ['equation:x=y/2.54|y=x*2.54', 10, 25.4],
			'fixture: centimetres to inches' => ['equation:x=y*2.54|y=x/2.54', 25.4, 10.0],
			'fixture: divide by ten' => ['equation:x=y/10|y=x*10', 5, 50.0],
			'negated' => ['equation:x=y|y=-x', 10, -10.0],
			'negative value' => ['equation:x=y|y=x*2', -2.5, -5.0],
			'negative decimal value' => ['equation:x=y|y=x*2', -2.4, -4.8],
			'square' => ['equation:x=y|y=x^2', 3, 9.0],
			'constant power chain' => ['equation:x=y|y=2^3^2', 3, 512.0],
			'power is right associative on x' => ['equation:x=y|y=2^3^x', 2, 512.0],
			'implicit multiplication' => ['equation:x=y|y=10x-50', 7, 20.0],
			'implicit multiplication, parenthesis' => ['equation:x=y|y=2(x+1)', 3, 8.0],
			'precedence' => ['equation:x=y|y=2+3*x', 2, 8.0],
			'parentheses' => ['equation:x=y|y=(2+3)*x', 2, 10.0],
			'the x-equation does not leak into the y-equation' => ['equation:x=y*100|y=x+1', 5, 6.0],
		];
	}

	/**
	 * Pairs of equations that are each other's inverse, exactly the shape the Devices fixtures use.
	 *
	 * @return array<string, array{string, int|float}>
	 */
	public static function provideInversePairs(): array
	{
		return [
			'scale by ten' => ['equation:x=y*10|y=x/10', 7],
			'divide by ten' => ['equation:x=y/10|y=x*10', 70],
			'inches' => ['equation:x=y/2.54|y=x*2.54', 12],
			'centimetres' => ['equation:x=y*2.54|y=x/2.54', 12],
			'offset' => ['equation:x=y+32|y=x-32', 21],
		];
	}

	/**
	 * Division by zero, by `/`, by `:` or by a negative power of zero, throws and never returns.
	 *
	 * @return array<string, array{string, bool, int|float}>
	 */
	public static function provideDivisionByZero(): array
	{
		return [
			'literal zero divisor' => ['equation:x=y/0', true, 10],
			'literal decimal zero divisor' => ['equation:x=y/0.0', true, 3],
			'divisor that cancels out' => ['equation:x=y/(1-1)', true, 3],
			'variable is the divisor and is zero' => ['equation:x=10/y', true, 0],
			'zero over zero' => ['equation:x=0/y', true, 0],
			'variable over itself at zero' => ['equation:x=y/y', true, 0],
			'zero divisor after a product' => ['equation:x=y*1/0', true, 3],
			'zero to a negative power' => ['equation:x=y^-1', true, 0],
			'colon, literal zero divisor' => ['equation:x=y:0', true, 10],
			'y-equation, literal zero divisor' => ['equation:x=y|y=x/0', false, 5],
			'y-equation, variable divisor' => ['equation:x=y|y=10/x', false, 0],
		];
	}

	/**
	 * Text the pattern accepts and that cannot be evaluated throws, never returns a number.
	 *
	 * @return array<string, array{string, bool, int|float}>
	 */
	public static function provideUnevaluable(): array
	{
		return [
			'unbalanced closing parenthesis' => ['equation:x=y+1)', true, 10],
			'reversed parentheses' => ['equation:x=)y(', true, 3],
			'leading division' => ['equation:x=/y', true, 3],
			'doubled division' => ['equation:x=y//2', true, 4],
			'leading power' => ['equation:x=^y', true, 3],
			'trailing power' => ['equation:x=y^', true, 3],
			'empty call' => ['equation:x=y()', true, 3],
			'y-equation, leading division' => ['equation:x=y|y=/x', false, 3],
		];
	}

	/**
	 * ACCIDENTAL. Malformed text that still evaluates to something. None of this is intended
	 * behaviour; it is pinned so that a change to it is seen. A doubled sign is a binary operator
	 * followed by a unary one, and a missing operator is multiplication, so `2y3` is `2*y*3`
	 * (mathsolver gave 69).
	 *
	 * @return array<string, array{string, int|float, float}>
	 */
	public static function provideLenientMalformed(): array
	{
		return [
			'doubled plus' => ['equation:x=y++2', 3, 5.0],
			'doubled minus is plus' => ['equation:x=y--2', 3, 5.0],
			'digit, variable, digit' => ['equation:x=2y3', 3, 18.0],
		];
	}

	/**
	 * Malformed text the pattern accepts, which mathsolver evaluated to a number (given in the
	 * comment) and which now throws.
	 *
	 * @return array<string, array{string, bool, int|float}>
	 */
	public static function provideRejectedMalformed(): array
	{
		return [
			'empty x-equation' => ['equation:x=', true, 10], // 0
			'empty y-equation' => ['equation:x=y*2|y=', false, 5], // 0
			'trailing plus' => ['equation:x=y+', true, 10], // 10
			'trailing minus' => ['equation:x=y-', true, 3], // 2
			'trailing multiplication' => ['equation:x=y*', true, 3], // 3
			'leading multiplication' => ['equation:x=*y', true, 3], // 3
			'unclosed parenthesis' => ['equation:x=(y+1', true, 10], // 11
			'empty parentheses' => ['equation:x=()', true, 3], // 0
			'doubled multiplication' => ['equation:x=y**2', true, 3], // 6
			'plus then multiplication' => ['equation:x=y+*2', true, 3], // 5
			'doubled power' => ['equation:x=y^^2', true, 3], // 81
			'doubled colon' => ['equation:x=y::2', true, 4], // 8
			'two decimal points' => ['equation:x=1.2.3*y', true, 3], // 0
			'only decimal points' => ['equation:x=..', true, 3], // 0
			// Not pinned by PR1. getValue() drops whitespace, which would turn it into 12.
			'two numbers separated by whitespace' => ['equation:x=1 2', true, 3],
		];
	}

	/**
	 * A result that is not a finite number throws rather than reaching a property value.
	 *
	 * @return array<string, array{string, int|float}>
	 */
	public static function provideNonFiniteResults(): array
	{
		return [
			'root of a negative number' => ['equation:x=(-y)^(1/2)', 4],
			'overflow' => ['equation:x=y*10', 1.0E+308],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideFromEquations')]
	public function testXEquationAsFloat(string $equation, int|float $value, float $expected): void
	{
		$result = (new Transformers\EquationTransformer($equation))->calculateEquationFrom(
			$value,
			Types\DataType::FLOAT,
		);

		self::assertIsFloat($result);
		self::assertEqualsWithDelta($expected, $result, self::DELTA, $equation);
	}

	/**
	 * Every non-float data type rounds the result to an integer.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideFromEquations')]
	public function testXEquationAsInteger(string $equation, int|float $value, float $expected): void
	{
		$result = (new Transformers\EquationTransformer($equation))->calculateEquationFrom(
			$value,
			Types\DataType::INT,
		);

		self::assertSame(intval(round($expected)), $result, $equation);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideToEquations')]
	public function testYEquationAsFloat(string $equation, int|float $value, float $expected): void
	{
		$result = (new Transformers\EquationTransformer($equation))->calculateEquationTo(
			$value,
			Types\DataType::FLOAT,
		);

		self::assertIsFloat($result);
		self::assertEqualsWithDelta($expected, $result, self::DELTA, $equation);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideToEquations')]
	public function testYEquationAsInteger(string $equation, int|float $value, float $expected): void
	{
		$result = (new Transformers\EquationTransformer($equation))->calculateEquationTo(
			$value,
			Types\DataType::INT,
		);

		self::assertSame(intval(round($expected)), $result, $equation);
	}

	/**
	 * Every numeric data type other than FLOAT takes the integer branch.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testIntegerBranchServesEveryNonFloatType(): void
	{
		$transformer = new Transformers\EquationTransformer('equation:x=y/4|y=x*4');

		foreach ([
			Types\DataType::CHAR,
			Types\DataType::UCHAR,
			Types\DataType::SHORT,
			Types\DataType::USHORT,
			Types\DataType::INT,
			Types\DataType::UINT,
		] as $dataType) {
			self::assertSame(3, $transformer->calculateEquationFrom(10, $dataType), $dataType->value);
			self::assertSame(40, $transformer->calculateEquationTo(10, $dataType), $dataType->value);
		}

		self::assertSame(2.5, $transformer->calculateEquationFrom(10, Types\DataType::FLOAT));
	}

	/**
	 * `round()` is half away from zero, not banker's rounding and not truncation.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testIntegerResultRoundsHalfAwayFromZero(): void
	{
		$transformer = new Transformers\EquationTransformer('equation:x=y*0.5|y=x*0.5');

		self::assertSame(2, $transformer->calculateEquationFrom(3, Types\DataType::INT));
		self::assertSame(3, $transformer->calculateEquationFrom(5, Types\DataType::INT));
		self::assertSame(4, $transformer->calculateEquationFrom(7, Types\DataType::INT));
		self::assertSame(-3, $transformer->calculateEquationFrom(-5, Types\DataType::INT));
		self::assertSame(-2, $transformer->calculateEquationFrom(-3, Types\DataType::INT));
		self::assertSame(1, $transformer->calculateEquationFrom(1, Types\DataType::INT));
		self::assertSame(0, $transformer->calculateEquationFrom(0, Types\DataType::INT));

		self::assertSame(3, $transformer->calculateEquationTo(5, Types\DataType::UINT));
		self::assertSame(-3, $transformer->calculateEquationTo(-5, Types\DataType::INT));

		// Rounds, does not truncate: 2.4 stays 2, 2.6 becomes 3.
		self::assertSame(2, (new Transformers\EquationTransformer('equation:x=y/10'))->calculateEquationFrom(
			24,
			Types\DataType::INT,
		));
		self::assertSame(3, (new Transformers\EquationTransformer('equation:x=y/10'))->calculateEquationFrom(
			26,
			Types\DataType::INT,
		));
	}

	/**
	 * Without a y-equation, `calculateEquationTo()` returns the value untouched, type included, and
	 * the data type plays no part.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testMissingYEquationReturnsValueUntouched(): void
	{
		$transformer = new Transformers\EquationTransformer('equation:x=y*2');

		self::assertNull($transformer->getEquationTo());
		self::assertSame(10, $transformer->calculateEquationTo(10, Types\DataType::FLOAT));
		self::assertSame(10, $transformer->calculateEquationTo(10, Types\DataType::INT));
		self::assertSame(2.5, $transformer->calculateEquationTo(2.5, Types\DataType::INT));
		self::assertSame(2.5, $transformer->calculateEquationTo(2.5, Types\DataType::FLOAT));
	}

	/**
	 * The two directions are independent equations, each reading only its own variable.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testDirectionsUseTheirOwnEquation(): void
	{
		$transformer = new Transformers\EquationTransformer('equation:x=y*10|y=x+1');

		self::assertEqualsWithDelta(50.0, $transformer->calculateEquationFrom(5, Types\DataType::FLOAT), self::DELTA);
		self::assertEqualsWithDelta(6.0, $transformer->calculateEquationTo(5, Types\DataType::FLOAT), self::DELTA);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideInversePairs')]
	public function testInversePairRoundTrips(string $equation, int|float $value): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$there = $transformer->calculateEquationFrom($value, Types\DataType::FLOAT);
		$back = $transformer->calculateEquationTo($there, Types\DataType::FLOAT);

		self::assertEqualsWithDelta((float) $value, $back, 1e-9, $equation);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideDivisionByZero')]
	public function testDivisionByZeroThrows(string $equation, bool $from, int|float $value): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$this->expectException(Exceptions\InvalidArgument::class);
		$this->expectExceptionMessage('divides by zero');

		$from
			? $transformer->calculateEquationFrom($value, Types\DataType::FLOAT)
			: $transformer->calculateEquationTo($value, Types\DataType::FLOAT);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideUnevaluable')]
	public function testUnevaluableTextThrows(string $equation, bool $from, int|float $value): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$this->expectException(Exceptions\InvalidArgument::class);

		$from
			? $transformer->calculateEquationFrom($value, Types\DataType::FLOAT)
			: $transformer->calculateEquationTo($value, Types\DataType::FLOAT);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideLenientMalformed')]
	public function testLenientMalformedTextIsPinnedNotPromised(
		string $equation,
		int|float $value,
		float $expected,
	): void
	{
		$result = (new Transformers\EquationTransformer($equation))->calculateEquationFrom(
			$value,
			Types\DataType::FLOAT,
		);

		self::assertEqualsWithDelta($expected, $result, self::DELTA, $equation);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideRejectedMalformed')]
	public function testMalformedTextThrows(string $equation, bool $from, int|float $value): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$this->expectException(Exceptions\InvalidArgument::class);

		$from
			? $transformer->calculateEquationFrom($value, Types\DataType::FLOAT)
			: $transformer->calculateEquationTo($value, Types\DataType::FLOAT);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideNonFiniteResults')]
	public function testNonFiniteResultThrows(string $equation, int|float $value): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$this->expectException(Exceptions\InvalidArgument::class);
		$this->expectExceptionMessage('does not evaluate to a finite number');

		$transformer->calculateEquationFrom($value, Types\DataType::FLOAT);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideRejectedAtConstruction')]
	public function testPatternRejectsAtConstruction(string $equation): void
	{
		$this->expectException(Exceptions\InvalidArgument::class);
		$this->expectExceptionMessage('Provided equation format is not valid');

		new Transformers\EquationTransformer($equation);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provideRejectedAtConstruction(): array
	{
		return [
			'empty string' => [''],
			'no prefix' => ['x=y'],
			'wrong prefix case' => ['EQUATION:x=y'],
			'y-equation first' => ['equation:y=x'],
			'x in the x-equation' => ['equation:x=10x + 2'],
			'y in the y-equation' => ['equation:x=y|y=y'],
			'other letter' => ['equation:x=10a + 2'],
			'upper-case variable' => ['equation:x=Y'],
			'square brackets' => ['equation:x=[10y + 2] * 10'],
			'percent' => ['equation:x=y%2'],
			'comma' => ['equation:x=y,5'],
			'exponent notation literal' => ['equation:x=1e3'],
			'second x-equation' => ['equation:x=y|x=2'],
			'dangling separator' => ['equation:x=y*2|'],
		];
	}

	/**
	 * `:` and `^` are part of the syntax users may write, so the pattern must keep accepting them.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testPatternAcceptsColonAndPower(): void
	{
		foreach (['equation:x=y:4', 'equation:x=y^2', 'equation:x=y|y=x:2', 'equation:x=2^3^2|y=x^2'] as $equation) {
			self::assertStringStartsWith(
				'equation:x=',
				(new Transformers\EquationTransformer($equation))->getValue(),
				$equation,
			);
		}
	}

	/**
	 * `getValue()` drops whitespace and otherwise keeps the equation as written. mathsolver
	 * re-rendered its parse tree instead, so `y:4` came back as `4y` and `2*y` as `2y`.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function testGetValueKeepsTheEquationAsWritten(): void
	{
		self::assertSame(
			'equation:x=y:4|y=x*4',
			(new Transformers\EquationTransformer('equation:x=y : 4|y=x * 4'))->getValue(),
		);
		self::assertSame(
			'equation:x=2*y/2/5',
			(new Transformers\EquationTransformer("equation:x=2 * y\t/ 2 / 5"))->getValue(),
		);
		self::assertSame('equation:x=y*2', (string) new Transformers\EquationTransformer('equation:x=y*2'));
	}

}
