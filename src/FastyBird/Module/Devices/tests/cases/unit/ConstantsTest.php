<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit;

use FastyBird\Module\Devices;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function is_string;
use function preg_match;
use function strtolower;

/**
 * Every exchange routing key the module declares is well-formed. Publishers, consumers and the
 * document routing map all use the constant, so a malformed key never fails a lookup: it only
 * stops matching what other parties (the frontend, a broker binding) expect on the wire.
 */
final class ConstantsTest extends TestCase
{

	private const SUBJECT_PATTERN = '[a-z][a-zA-Z]*(\.[a-z][a-zA-Z]*)*';

	private const DOCUMENT_ROUTING_KEY_PATTERN = '/^fb\.exchange\.module\.document\.(reported|created|updated|deleted)\.'
		. self::SUBJECT_PATTERN . '$/';

	private const ACTION_ROUTING_KEY_PATTERN = '/^fb\.exchange\.action\.' . self::SUBJECT_PATTERN . '$/';

	private const DOCUMENT_CONSTANT_PATTERN = '/^MESSAGE_BUS_\w+_DOCUMENT_(REPORTED|CREATED|UPDATED|DELETED)_ROUTING_KEY$/';

	private const ACTION_CONSTANT_PATTERN = '/^MESSAGE_BUS_\w+_ACTION_ROUTING_KEY$/';

	#[DataProvider('documentRoutingKeys')]
	public function testDocumentRoutingKeyIsWellFormed(string $action, string $routingKey): void
	{
		self::assertMatchesRegularExpression(self::DOCUMENT_ROUTING_KEY_PATTERN, $routingKey);
		self::assertStringStartsWith('fb.exchange.module.document.' . $action . '.', $routingKey);
	}

	#[DataProvider('actionRoutingKeys')]
	public function testActionRoutingKeyIsWellFormed(string $routingKey): void
	{
		self::assertMatchesRegularExpression(self::ACTION_ROUTING_KEY_PATTERN, $routingKey);
	}

	public function testValueFormatStringEnum(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two_v1,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two-v1,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'1one,two,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,1two,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'1,two,three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'1,2,3',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,_two,three',
		));
	}

	public function testValueFormatNumberRange(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:20',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'u8|10:20',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:f|20',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:i16|20',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':i16|20',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':20',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one',
		));
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one,two',
		));
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one:10',
		));
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'i10|10:',
		));
	}

	public function testValueFormatCombinedEnum(): void
	{
		// Valid
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one:one:one,two:two:two,three:three:three',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:1000:s|on,sw|switch_off:2000:s|off',
		));
		self::assertSame(1, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u8|10:s|on,sw|switch_off:u8|20:s|off',
		));

		// Invalid
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u10|10:s|on,sw|switch_off:u8|20:s|off',
		));
		self::assertSame(0, preg_match(
			Devices\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw,sw|switch_off:u8|20:s|off',
		));
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function documentRoutingKeys(): array
	{
		$keys = [];

		foreach (self::routingKeyConstants() as $name => $value) {
			if (preg_match(self::DOCUMENT_CONSTANT_PATTERN, $name, $matches) === 1) {
				$keys[$name] = [strtolower($matches[1]), $value];
			}
		}

		return $keys;
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function actionRoutingKeys(): array
	{
		$keys = [];

		foreach (self::routingKeyConstants() as $name => $value) {
			if (preg_match(self::ACTION_CONSTANT_PATTERN, $name) === 1) {
				$keys[$name] = [$value];
			}
		}

		return $keys;
	}

	/**
	 * @return array<string, string>
	 */
	private static function routingKeyConstants(): array
	{
		$constants = [];

		foreach ((new ReflectionClass(Devices\Constants::class))->getConstants() as $name => $value) {
			if (is_string($value)) {
				$constants[$name] = $value;
			}
		}

		return $constants;
	}

}
