<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Transformers;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types;
use NXP\Classes\Operator;
use NXP\Exception\DivisionByZeroException;
use NXP\Exception\MathExecutorException;
use NXP\MathExecutor;
use Override;
use function array_key_exists;
use function count;
use function ctype_alpha;
use function ctype_digit;
use function ctype_space;
use function in_array;
use function is_finite;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function round;
use function sprintf;
use function str_contains;
use function strlen;
use function strspn;
use function substr;
use function var_export;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Equation value transformer
 *
 * The x-equation (`equation:x=…`) is evaluated with `y` bound to the value, the y-equation (`…|y=…`)
 * with `x` bound to the value. Arithmetic is done by `nxp/math-executor`; the equation itself is
 * parsed here first, because the syntax users may store differs from the library's in three ways:
 *
 *  - `:` is division, exactly like `/`;
 *  - `^` is right associative (`2^3^2` = 512), and binds tighter than a unary minus (`-2^2` = -4)
 *    while still accepting one in its exponent (`2^-1` = 0.5);
 *  - a missing operator is multiplication (`10y`, `y2`, `2(y+1)`, `(y+1)(y+2)`, `2 y`).
 *
 * The parsed equation is handed to the library fully parenthesised, so the library's own
 * precedence and associativity never decide anything.
 */
final class EquationTransformer
{

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string PATTERN = '/^equation:(?:(?:x=)(?<equation_x>(?:(?:[\d.y]?)*(?:[\+\-\^\*\:\/\(\)])*(?:\s)*)*)){1}(?:\|(?:(?:y=)(?<equation_y>(?:(?:[\d.x]?)*(?:[\+\-\^\*\:\/\(\)])*(?:\s)*)*))){0,1}$/';

	private const string OPERATORS = '+-*/:^()';

	private const string NUMBER_PATTERN = '/^(?:\d+\.?\d*|\.\d+)$/';

	private string|null $equationFrom = null;

	private string|null $equationTo = null;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string $equation)
	{
		if (preg_match(self::PATTERN, $equation, $matches) === 1) {
			$this->equationFrom = $matches['equation_x'];

			if (array_key_exists('equation_y', $matches)) {
				$this->equationTo = $matches['equation_y'];
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided equation format is not valid');
		}
	}

	public function getEquationFrom(): string|null
	{
		return $this->equationFrom;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function calculateEquationFrom(
		int|float $value,
		Types\DataType $dataType,
	): int|float
	{
		if ($this->equationFrom === null) {
			return $value;
		}

		return self::toDataType(self::evaluate($this->equationFrom, 'y', $value), $dataType, $this->equationFrom);
	}

	public function getEquationTo(): string|null
	{
		return $this->equationTo;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function calculateEquationTo(
		int|float $value,
		Types\DataType $dataType,
	): int|float
	{
		if ($this->equationTo === null) {
			return $value;
		}

		return self::toDataType(self::evaluate($this->equationTo, 'x', $value), $dataType, $this->equationTo);
	}

	public function getValue(): string
	{
		return $this->__toString();
	}

	public function toString(): string
	{
		return $this->__toString();
	}

	/**
	 * An integer result is rounded, and a rounded value that an `int` cannot hold is an error: `(int)` on it
	 * wraps around without a word on PHP 8.4 (2^63 becomes `PHP_INT_MIN`), and a property would silently take
	 * a wrong value. The upper limit is exclusive because `(float) PHP_INT_MAX` is 2^63, one more than
	 * `PHP_INT_MAX`; the lower limit, -2^63, is `PHP_INT_MIN` exactly.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function toDataType(float $value, Types\DataType $dataType, string $equation): int|float
	{
		if ($dataType === Types\DataType::FLOAT) {
			return $value;
		}

		$rounded = round($value);

		if (!is_finite($rounded) || $rounded < (float) PHP_INT_MIN || $rounded >= (float) PHP_INT_MAX) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'Equation "%s" result %s cannot be represented as an integer',
					$equation,
					var_export($value, true),
				),
			);
		}

		return (int) $rounded;
	}

	private static function withoutWhitespace(string $equation): string
	{
		return preg_replace('/\s+/', '', $equation) ?? $equation;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private static function evaluate(string $equation, string $variable, int|float $value): float
	{
		$expression = self::parse(self::tokenize($equation, $variable), $equation);

		try {
			$executor = new MathExecutor();
			// The library's own `^` raises zero to a negative power without complaint (INF, and an
			// E_DEPRECATED on PHP 8.4); that is a division by zero like any other.
			$executor->addOperator(new Operator(
				'^',
				true,
				220,
				static function (int|float|string $base, int|float|string $exponent): float {
					$base = (float) $base;
					$exponent = (float) $exponent;

					if ($base === 0.0 && $exponent < 0.0) {
						throw new DivisionByZeroException();
					}

					return $base ** $exponent;
				},
			));
			$executor->setVar($variable, $value);

			$result = $executor->execute($expression, false);
		} catch (MathExecutorException $ex) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					$ex instanceof DivisionByZeroException
						? 'Equation "%s" divides by zero'
						: 'Equation "%s" could not be evaluated',
					$equation,
				),
				0,
				$ex,
			);
		}

		if (!is_numeric($result) || !is_finite((float) $result)) {
			throw new Exceptions\InvalidArgument(
				sprintf('Equation "%s" does not evaluate to a finite number', $equation),
			);
		}

		return (float) $result;
	}

	/**
	 * Splits the equation into numbers, the variable, operators and parentheses. Whitespace only
	 * separates tokens, and `:` becomes `/`.
	 *
	 * @return list<string>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function tokenize(string $equation, string $variable): array
	{
		$tokens = [];
		$length = strlen($equation);
		$position = 0;

		while ($position < $length) {
			$char = $equation[$position];

			if (ctype_space($char)) {
				$position++;

				continue;
			}

			if (ctype_digit($char) || $char === '.') {
				$number = substr($equation, $position, strspn($equation, '0123456789.', $position));

				if (preg_match(self::NUMBER_PATTERN, $number) !== 1) {
					throw new Exceptions\InvalidArgument(
						sprintf('Equation "%s" contains an invalid number "%s"', $equation, $number),
					);
				}

				// Only whitespace can separate two numbers, and dropping it would join them into one.
				if ($tokens !== [] && self::isNumber($tokens[count($tokens) - 1])) {
					throw new Exceptions\InvalidArgument(
						sprintf('Equation "%s" has two numbers without an operator between them', $equation),
					);
				}

				$tokens[] = $number;
				$position += strlen($number);

				continue;
			}

			if ($char === $variable || str_contains(self::OPERATORS, $char)) {
				$tokens[] = $char === ':' ? '/' : $char;
				$position++;

				continue;
			}

			throw new Exceptions\InvalidArgument(
				sprintf('Equation "%s" contains an unexpected character "%s"', $equation, $char),
			);
		}

		if ($tokens === []) {
			throw new Exceptions\InvalidArgument(sprintf('Equation "%s" is empty', $equation));
		}

		return $tokens;
	}

	/**
	 * Recursive descent over the grammar below, returning the expression fully parenthesised:
	 *
	 *   expression := term (("+" | "-") term)*
	 *   term       := unary (("*" | "/") unary | implicit unary)*    implicit: next token opens an operand
	 *   unary      := ("+" | "-") unary | power
	 *   power      := primary ("^" unary)?                           right associative
	 *   primary    := number | variable | "(" expression ")"
	 *
	 * @param list<string> $tokens
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function parse(array $tokens, string $equation): string
	{
		$position = 0;

		$expression = self::parseExpression($tokens, $position, $equation);

		if ($position < count($tokens)) {
			throw self::unexpected($tokens, $position, $equation);
		}

		return $expression;
	}

	/**
	 * @param list<string> $tokens
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function parseExpression(array $tokens, int &$position, string $equation): string
	{
		$expression = self::parseTerm($tokens, $position, $equation);

		while (in_array($tokens[$position] ?? null, ['+', '-'], true)) {
			$operator = $tokens[$position++];

			$expression = '(' . $expression . $operator . self::parseTerm($tokens, $position, $equation) . ')';
		}

		return $expression;
	}

	/**
	 * @param list<string> $tokens
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function parseTerm(array $tokens, int &$position, string $equation): string
	{
		$term = self::parseUnary($tokens, $position, $equation);

		while (true) {
			$token = $tokens[$position] ?? null;

			if ($token === '*' || $token === '/') {
				$position++;

				$term = '(' . $term . $token . self::parseUnary($tokens, $position, $equation) . ')';

			} elseif ($token !== null && ($token === '(' || self::isNumber($token) || ctype_alpha($token))) {
				$term = '(' . $term . '*' . self::parseUnary($tokens, $position, $equation) . ')';

			} else {
				return $term;
			}
		}
	}

	/**
	 * @param list<string> $tokens
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function parseUnary(array $tokens, int &$position, string $equation): string
	{
		$token = $tokens[$position] ?? null;

		if ($token === '-') {
			$position++;

			return '(-' . self::parseUnary($tokens, $position, $equation) . ')';
		}

		if ($token === '+') {
			$position++;

			return self::parseUnary($tokens, $position, $equation);
		}

		$base = self::parsePrimary($tokens, $position, $equation);

		if (($tokens[$position] ?? null) === '^') {
			$position++;

			return '(' . $base . '^' . self::parseUnary($tokens, $position, $equation) . ')';
		}

		return $base;
	}

	/**
	 * @param list<string> $tokens
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function parsePrimary(array $tokens, int &$position, string $equation): string
	{
		$token = $tokens[$position] ?? null;

		if ($token === '(') {
			$position++;

			$expression = self::parseExpression($tokens, $position, $equation);

			if (($tokens[$position] ?? null) !== ')') {
				throw array_key_exists($position, $tokens)
					? self::unexpected($tokens, $position, $equation)
					: new Exceptions\InvalidArgument(
						sprintf('Equation "%s" has an unclosed parenthesis', $equation),
					);
			}

			$position++;

			return '(' . $expression . ')';
		}

		if ($token !== null && (self::isNumber($token) || ctype_alpha($token))) {
			$position++;

			return $token;
		}

		throw self::unexpected($tokens, $position, $equation);
	}

	private static function isNumber(string $token): bool
	{
		return ctype_digit($token[0]) || $token[0] === '.';
	}

	/**
	 * @param list<string> $tokens
	 */
	private static function unexpected(array $tokens, int $position, string $equation): Exceptions\InvalidArgument
	{
		return new Exceptions\InvalidArgument(
			array_key_exists($position, $tokens)
				? sprintf('Equation "%s" has an unexpected "%s"', $equation, $tokens[$position])
				: sprintf('Equation "%s" ends where an operand is expected', $equation),
		);
	}

	#[Override]
	public function __toString(): string
	{
		return 'equation:'
			. ($this->equationFrom !== null ? 'x=' . self::withoutWhitespace($this->equationFrom) : '')
			. ($this->equationTo !== null ? '|y=' . self::withoutWhitespace($this->equationTo) : '');
	}

}
