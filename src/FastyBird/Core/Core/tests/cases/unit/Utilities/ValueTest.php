<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueError;

final class ValueTest extends TestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ValuesExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[DataProvider('normalizeValue')]
	public function testNormalizeValue(
		Types\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		bool|float|int|string|DateTimeInterface|Payloads\Button|Payloads\Switcher|Payloads\Cover|null $value,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format = null,
		float|int|string|null $invalid = null,
		float|int|string|null $expected = null,
		bool $throwError = false,
	): void
	{
		if ($throwError) {
			self::expectException(ValuesExceptions\InvalidValue::class);
		}

		$normalized = Utilities\Value::normalizeValue($value, $dataType, $format);

		if (!$throwError) {
			self::assertSame($expected, $normalized);
		}
	}

	/**
	 * @return array<string, array<mixed>>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function normalizeValue(): array
	{
		return [
			'integer_1' => [
				Types\DataType::CHAR,
				'10',
				null,
				null,
				10,
				false,
			],
			'integer_2' => [
				Types\DataType::CHAR,
				'9',
				new Formats\NumberRange([10, 20]),
				null,
				null,
				true,
			],
			'integer_3' => [
				Types\DataType::CHAR,
				'30',
				new Formats\NumberRange([10, 20]),
				null,
				null,
				true,
			],
			'float_1' => [
				Types\DataType::FLOAT,
				'30.3',
				null,
				null,
				30.3,
			],
		];
	}

}
