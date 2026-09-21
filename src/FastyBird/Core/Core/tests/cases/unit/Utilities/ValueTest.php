<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as ToolsExceptions;
use FastyBird\Core\Formats\Tools as ToolsFormats;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueError;

final class ValueTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[DataProvider('normalizeValue')]
	public function testNormalizeValue(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Button|MetadataTypes\Payloads\Switcher|MetadataTypes\Payloads\Cover|null $value,
		ToolsFormats\StringEnum|ToolsFormats\NumberRange|ToolsFormats\CombinedEnum|null $format = null,
		float|int|string|null $invalid = null,
		float|int|string|null $expected = null,
		bool $throwError = false,
	): void
	{
		if ($throwError) {
			self::expectException(ToolsExceptions\InvalidValue::class);
		}

		$normalized = ToolsUtilities\Value::normalizeValue($value, $dataType, $format);

		if (!$throwError) {
			self::assertSame($expected, $normalized);
		}
	}

	/**
	 * @return array<string, array<mixed>>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function normalizeValue(): array
	{
		return [
			'integer_1' => [
				MetadataTypes\DataType::CHAR,
				'10',
				null,
				null,
				10,
				false,
			],
			'integer_2' => [
				MetadataTypes\DataType::CHAR,
				'9',
				new ToolsFormats\NumberRange([10, 20]),
				null,
				null,
				true,
			],
			'integer_3' => [
				MetadataTypes\DataType::CHAR,
				'30',
				new ToolsFormats\NumberRange([10, 20]),
				null,
				null,
				true,
			],
			'float_1' => [
				MetadataTypes\DataType::FLOAT,
				'30.3',
				null,
				null,
				30.3,
			],
		];
	}

}
