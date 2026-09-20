<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Common;

use FastyBird\Core\Constants\Metadata;
use PHPUnit\Framework\TestCase;
use function preg_match;

final class ConstantsTest extends TestCase
{

	public function testValueFormatStringEnum(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two_v1,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two-v1,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'1one,two,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,1two,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'1,two,three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'1,2,3',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,_two,three',
		));
	}

	public function testValueFormatNumberRange(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:20',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'u8|10:20',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:f|20',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:i16|20',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':i16|20',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':20',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one,two',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one:10',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'i10|10:',
		));
	}

	public function testValueFormatCombinedEnum(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one:one:one,two:two:two,three:three:three',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:1000:s|on,sw|switch_off:2000:s|off',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u8|10:s|on,sw|switch_off:u8|20:s|off',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u10|10:s|on,sw|switch_off:u8|20:s|off',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw,sw|switch_off:u8|20:s|off',
		));
	}

	public function testValueEquationTransform(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=10y + 2',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=(10y + 2) * 10',
		));
		self::assertSame(1, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=(10y + 2) * 10|y=x + 2 / 3',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=10x + 2',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=10a + 2',
		));
		self::assertSame(0, preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=[10y + 2] * 10',
		));
		self::assertFalse(preg_match(
			Metadata\Constants::VALUE_EQUATION_TRANSFORMER,
			'equation:x=(10y + 2) * 10|y=y + 2 / 3',
		));
	}

}
