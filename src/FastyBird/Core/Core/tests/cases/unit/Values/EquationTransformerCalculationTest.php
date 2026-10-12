<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Values;

use Closure;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Transformers;
use FastyBird\Core\Values\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;
use function intval;
use function round;
use function sprintf;

/**
 * Characterization of what `Transformers\EquationTransformer` calculates (#443, PR1).
 *
 * The transformer evaluates through `mathsolver/mathsolver`, which has no release, no commit since
 * 2023 and a lenient, partly accidental grammar. #443 replaces it with `nxp/math-executor` in PR2.
 * This file is the safety net for that swap, in three tiers:
 *
 *  1. PINNED: behaviour the swap must keep. Every provider except `provideLenientMalformed` and
 *     `provideKnownDefects` is this tier, and asserts what is both true today and intended.
 *  2. ACCIDENTAL (`provideLenientMalformed`, `testEmptyYEquationIsZero`): what the current library
 *     does with malformed text. It is pinned only so that PR2 sees every change; nothing here is a
 *     promise, and PR2 may replace these with a thrown error.
 *  3. KNOWN DEFECTS (`provideKnownDefects`): the intended behaviour where today's library is wrong.
 *     Each runs the real calculation; while the library is wrong the test is reported incomplete
 *     (green), and the moment it is right the test FAILS and asks for the row to be moved up into
 *     the pinned tier. PR2 must make every row pass, or take it out of the list with a reason.
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
			'root through a parenthesised fraction' => ['equation:x=y^(1/2)', 16, 4.0],
			'power is right associative' => ['equation:x=2^3^2', 1, 512.0],
			'power is right associative, base is the variable' => ['equation:x=y^3^2', 2, 512.0],
			'power is right associative, exponent is the variable' => ['equation:x=2^3^y', 2, 512.0],
			'power is right associative, variable in the middle' => ['equation:x=2^y^2', 2, 16.0],
			'power is right associative, three levels' => ['equation:x=2^3^2^1', 1, 512.0],
			'left-grouped power needs parentheses' => ['equation:x=(2^3)^2', 1, 64.0],
			'right-grouped power in parentheses' => ['equation:x=2^(3^2)', 1, 512.0],

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
	 * Division by zero. Today `Math::substitute()`/`simplify()` raises `DivisionByZeroError`; PR2 will
	 * raise the library's own exception. The contract kept here is only "throws, never returns".
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
			'y-equation, literal zero divisor' => ['equation:x=y|y=x/0', false, 5],
			'y-equation, variable divisor' => ['equation:x=y|y=10/x', false, 0],
		];
	}

	/**
	 * Text the pattern accepts and the library cannot evaluate; today an `Error` or `TypeError` that
	 * leaks out of the library. The contract kept here is only "throws, never returns a number".
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
	 * ACCIDENTAL. Malformed text the library does not reject but evaluates to something. None of this
	 * is intended behaviour: it is pinned so that the swap shows exactly which of them change, and
	 * PR2 is free to turn any row into a thrown error (nxp/math-executor rejects most of them).
	 *
	 * @return array<string, array{string, int|float, float}>
	 */
	public static function provideLenientMalformed(): array
	{
		return [
			'empty x-equation is zero' => ['equation:x=', 10, 0.0],
			'trailing plus is dropped' => ['equation:x=y+', 10, 10.0],
			'trailing minus subtracts one' => ['equation:x=y-', 3, 2.0],
			'trailing multiplication is dropped' => ['equation:x=y*', 3, 3.0],
			'leading multiplication is dropped' => ['equation:x=*y', 3, 3.0],
			'unclosed parenthesis is closed' => ['equation:x=(y+1', 10, 11.0],
			'empty parentheses are zero' => ['equation:x=()', 3, 0.0],
			'doubled multiplication multiplies by the right operand' => ['equation:x=y**2', 3, 6.0],
			'doubled plus' => ['equation:x=y++2', 3, 5.0],
			'plus then multiplication' => ['equation:x=y+*2', 3, 5.0],
			'doubled minus is plus' => ['equation:x=y--2', 3, 5.0],
			'doubled power' => ['equation:x=y^^2', 3, 81.0],
			'doubled colon' => ['equation:x=y::2', 4, 8.0],
			'two decimal points are zero' => ['equation:x=1.2.3*y', 3, 0.0],
			'only decimal points are zero' => ['equation:x=..', 3, 0.0],
			'digit, variable, digit' => ['equation:x=2y3', 3, 69.0],
		];
	}

	/**
	 * INTENDED behaviour where the current library is wrong. See the class comment: reported
	 * incomplete while wrong, FAILS when it starts being right.
	 *
	 * `expected` is the intended result, or null where the intended behaviour is that it throws.
	 *
	 * @return array<string, array{string, bool, int|float, float|null, string}>
	 */
	public static function provideKnownDefects(): array
	{
		return [
			// ":" is division. mathsolver multiplies, or fails to parse and yields 0.
			'colon: variable over number' => ['equation:x=y:4', true, 10, 2.5, 'today 40.0, it multiplies'],
			'colon: variable over number, y-equation' => ['equation:x=y|y=x:4', false, 10, 2.5, 'today 40.0, it multiplies'],
			'colon: number over variable' => ['equation:x=100:y', true, 4, 25.0, 'today 400.0, it multiplies'],
			'colon: two numbers' => ['equation:x=10:2', true, 1, 5.0, 'today 0.0, unparsed'],
			'colon: chained is left associative' => ['equation:x=8:2:2', true, 1, 2.0, 'today 0.0, unparsed'],
			'colon: chained with the variable' => ['equation:x=y:2:5', true, 20, 2.0, 'today 500.0'],
			'colon: after a product' => ['equation:x=y*2:4', true, 10, 5.0, 'today 0.0, unparsed'],
			'colon: precedence equals division' => ['equation:x=y+8:2', true, 2, 6.0, 'today 0.0, unparsed'],
			'colon: parenthesised divisor' => ['equation:x=y:(2*2)', true, 10, 2.5, 'today 10.0'],
			'colon: parenthesised dividend' => ['equation:x=(y):4', true, 10, 2.5, 'today 10.0'],
			'colon: decimal divisor' => ['equation:x=y:0.5', true, 3, 6.0, 'today 1.5, it multiplies'],
			'colon: zero divisor throws like slash' => ['equation:x=y:0', true, 10, null, 'today 0.0, no error'],
			'colon: same result as slash' => ['equation:x=y:2', true, 10, 5.0, 'today 20.0, it multiplies'],

			// Not in the issue text; found while characterizing. "^" with a fractional exponent and a
			// float that PHP prints in exponent notation are both mis-evaluated, not just unusual.
			'power: fractional exponent' => ['equation:x=y^0.5', true, 16, 4.0, 'today 0.0'],
			'value large enough for exponent notation' => ['equation:x=y*2', true, 1.0E+25, 2.0E+25, 'today 0.0'],
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

		$this->expectException(Throwable::class);

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

		$this->expectException(Throwable::class);

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
	public function testEmptyYEquationIsZero(): void
	{
		// ACCIDENTAL, like the rows above: `|y=` with nothing after it passes the pattern.
		$transformer = new Transformers\EquationTransformer('equation:x=y*2|y=');

		self::assertSame(0.0, $transformer->calculateEquationTo(5, Types\DataType::FLOAT));
		self::assertSame(0, $transformer->calculateEquationTo(5, Types\DataType::INT));
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
	 * @throws Exceptions\InvalidArgument
	 */
	#[DataProvider('provideKnownDefects')]
	public function testKnownDefectsFixedByPr2(
		string $equation,
		bool $from,
		int|float $value,
		float|null $expected,
		string $today,
	): void
	{
		$transformer = new Transformers\EquationTransformer($equation);

		$assertion = static function () use ($transformer, $from, $value, $expected): void {
			if ($expected === null) {
				try {
					$from
						? $transformer->calculateEquationFrom($value, Types\DataType::FLOAT)
						: $transformer->calculateEquationTo($value, Types\DataType::FLOAT);
				} catch (Throwable) {
					return;
				}

				self::fail('Expected an exception, none was thrown.');
			}

			$result = $from
				? $transformer->calculateEquationFrom($value, Types\DataType::FLOAT)
				: $transformer->calculateEquationTo($value, Types\DataType::FLOAT);

			self::assertEqualsWithDelta($expected, $result, self::DELTA);
		};

		self::expectedFailureUntilPr2($assertion, $equation, $today);
	}

	/**
	 * Known-defect gate: red-to-green is a signal, not a surprise. While the assertion fails the
	 * test is incomplete; once it holds the test fails and tells the author to promote the row.
	 *
	 * @param Closure(): void $assertion
	 */
	private static function expectedFailureUntilPr2(Closure $assertion, string $equation, string $today): void
	{
		try {
			$assertion();
		} catch (Throwable $ex) {
			self::markTestIncomplete(sprintf(
				'Known defect, fixed by #443 PR2: "%s" (%s). %s',
				$equation,
				$today,
				$ex->getMessage(),
			));
		}

		self::fail(sprintf(
			'"%s" now gives the intended result. Move it out of provideKnownDefects() into the pinned providers.',
			$equation,
		));
	}

}
