<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Transformers;

use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types;
use MathSolver\Math;
use function array_key_exists;
use function floatval;
use function implode;
use function intval;
use function is_array;
use function preg_match;
use function round;

/**
 * Equation value transformer
 */
final class EquationTransformer
{

	private string|null $equationFrom = null;

	private string|null $equationTo = null;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string $equation)
	{
		if (preg_match(Constants::VALUE_EQUATION_TRANSFORMER, $equation, $matches) === 1) {
			$this->equationFrom = $matches['equation_x'];

			if (array_key_exists('equation_y', $matches)) {
				$this->equationTo = $matches['equation_y'];
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided equation format is not valid');
		}
	}

	public function getEquationFrom(): Math|null
	{
		return $this->equationFrom !== null ? Math::from($this->equationFrom) : null;
	}

	public function calculateEquationFrom(
		int|float $value,
		Types\DataType $dataType,
	): int|float
	{
		$equation = $this->getEquationFrom();

		if ($equation === null) {
			return $value;
		}

		$value = @$equation->substitute(['y' => $value])->simplify()->string();
		$value = is_array($value) ? implode('', $value) : $value;

		$value = @Math::from('calc[' . $value . ']')->simplify()->string();

		$value = is_array($value) ? implode(' ', $value) : $value;

		return $dataType === Types\DataType::FLOAT
			? floatval($value)
			: intval(round(floatval($value)));
	}

	public function getEquationTo(): Math|null
	{
		return $this->equationTo !== null ? Math::from($this->equationTo) : null;
	}

	public function calculateEquationTo(
		int|float $value,
		Types\DataType $dataType,
	): int|float
	{
		$equation = $this->getEquationTo();

		if ($equation === null) {
			return $value;
		}

		$value = @$equation->substitute(['x' => $value])->simplify()->string();
		$value = is_array($value) ? implode('', $value) : $value;

		$value = @Math::from('calc[' . $value . ']')->simplify()->string();

		$value = is_array($value) ? implode(' ', $value) : $value;

		return $dataType === Types\DataType::FLOAT
			? floatval($value)
			: intval(round(floatval($value)));
	}

	public function getValue(): string
	{
		return $this->__toString();
	}

	public function toString(): string
	{
		return $this->__toString();
	}

	public function __toString(): string
	{
		$from = $this->getEquationFrom()?->string();
		$to = $this->getEquationTo()?->string();

		return 'equation:'
			. ($from !== null ? 'x=' . (is_array($from) ? implode($from) : $from) : '')
			. ($to !== null ? '|y=' . (is_array($to) ? implode($to) : $to) : '');
	}

}
