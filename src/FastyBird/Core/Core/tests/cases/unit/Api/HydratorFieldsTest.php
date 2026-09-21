<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use DateTimeInterface;
use FastyBird\Core\Encoding\JsonApi\Objects\StandardObject;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\ArrayField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BackedEnumField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BooleanField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\DateTimeField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\MixedField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\NumberField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\SingleEntityField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\TextField;
use FastyBird\Core\Types\Metadata\DataType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use ValueError;

/**
 * Characterizes the pure value-coercion behaviour of the JSON:API hydrator field
 * classes as they exist today, ahead of the E3 move to `FastyBird\Core\Api\Hydrators\Fields\`.
 */
final class HydratorFieldsTest extends TestCase
{

	public function testFieldIsRequiredAndIsWritableReturnConstructorArgumentsUnchanged(): void
	{
		$field = new TextField(false, 'mapped-name', 'field-name', true, false);

		self::assertTrue($field->isRequired());
		self::assertFalse($field->isWritable());

		$field = new TextField(false, 'mapped-name', 'field-name', false, true);

		self::assertFalse($field->isRequired());
		self::assertTrue($field->isWritable());
	}

	public function testFieldGetMappedNameDoesNotFallBackToFieldName(): void
	{
		$field = new TextField(false, 'mapped-name', 'field-name', true, true);

		self::assertSame('mapped-name', $field->getMappedName());
		self::assertSame('field-name', $field->getFieldName());
	}

	public function testTextFieldGetValueDoesNotTrimSurroundingWhitespace(): void
	{
		$field = new TextField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '  hello  ');

		self::assertSame('  hello  ', $field->getValue($attributes));
	}

	public function testTextFieldGetValueTurnsEmptyStringToNullOnlyWhenNullable(): void
	{
		$nullableField = new TextField(true, 'field', 'field', true, true);
		$notNullableField = new TextField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '');

		self::assertNull($nullableField->getValue($attributes));
		self::assertSame('', $notNullableField->getValue($attributes));
	}

	public function testNumberFieldGetValueCastsToIntWhenNotDecimal(): void
	{
		$field = new NumberField(false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '42');

		self::assertSame(42, $field->getValue($attributes));
	}

	public function testNumberFieldGetValueCastsToFloatWhenDecimal(): void
	{
		$field = new NumberField(true, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '4.5');

		self::assertSame(4.5, $field->getValue($attributes));
	}

	public function testNumberFieldGetValueTruncatesADecimalStringWhenNotDecimal(): void
	{
		$field = new NumberField(false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '4.5');

		self::assertSame(4, $field->getValue($attributes));
	}

	public function testNumberFieldGetValueOnNonNumericStringCoercesToZeroRatherThanThrowing(): void
	{
		$field = new NumberField(false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not a number');

		self::assertSame(0, $field->getValue($attributes));
	}

	#[DataProvider('booleanScalars')]
	public function testBooleanFieldGetValueCoercesEachScalarInput(bool|string|int $value, bool $expected): void
	{
		$field = new BooleanField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', $value);

		self::assertSame($expected, $field->getValue($attributes));
	}

	/**
	 * @return array<string, array{0: bool|string|int, 1: bool}>
	 */
	public static function booleanScalars(): array
	{
		return [
			'bool_true' => [true, true],
			'bool_false' => [false, false],
			'string_true' => ['true', true],
			// Any non-empty string other than "0" is truthy in PHP -- including the
			// string "false". This is the sharpest probe on this class: a mutant that
			// special-cased the literal string "false" would still pass every other case.
			'string_false' => ['false', true],
			'string_one' => ['1', true],
			'string_zero' => ['0', false],
			'int_one' => [1, true],
			'int_zero' => [0, false],
		];
	}

	public function testBooleanFieldGetValueOnMissingKeyReturnsNullOrFalseDependingOnNullable(): void
	{
		$nullableField = new BooleanField(true, 'field', 'field', true, true);
		$notNullableField = new BooleanField(false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertNull($nullableField->getValue($attributes));
		self::assertFalse($notNullableField->getValue($attributes));
	}

	public function testDateTimeFieldGetValueParsesAtomFormatAndPreservesTheInstant(): void
	{
		$field = new DateTimeField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '2026-09-21T12:00:00+00:00');

		$value = $field->getValue($attributes);

		self::assertInstanceOf(DateTimeInterface::class, $value);
		self::assertSame('2026-09-21T12:00:00+00:00', $value->format(DateTimeInterface::ATOM));
	}

	public function testDateTimeFieldGetValueReturnsNullForAnUnparseableStringRatherThanThrowing(): void
	{
		$field = new DateTimeField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not a date');

		self::assertNull($field->getValue($attributes));
	}

	public function testDateTimeFieldGetValueRejectsADateThatParsesButDoesNotRoundTrip(): void
	{
		$field = new DateTimeField(false, 'field', 'field', true, true);

		// September has 30 days. `createFromFormat()` accepts day 31 and silently
		// overflows into October 1st -- it parses, but re-formatting the result with
		// the same ATOM format no longer reproduces the input string. The round-trip
		// equality check in the source is what rejects this, not the `instanceof` check
		// alone.
		$attributes = (new StandardObject())->set('field', '2026-09-31T12:00:00+00:00');

		self::assertNull($field->getValue($attributes));
	}

	public function testBackedEnumFieldGetValueReturnsTheMatchingCaseForAValidBackingValue(): void
	{
		$field = new BackedEnumField(DataType::class, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'char');

		self::assertSame(DataType::CHAR, $field->getValue($attributes));
	}

	public function testBackedEnumFieldGetValueThrowsValueErrorForAnInvalidBackingValue(): void
	{
		$field = new BackedEnumField(DataType::class, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not-a-data-type');

		self::expectException(ValueError::class);

		$field->getValue($attributes);
	}

	public function testBackedEnumFieldGetValueReturnsNullWhenAttributeIsAbsentRatherThanThrowing(): void
	{
		$field = new BackedEnumField(DataType::class, false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertNull($field->getValue($attributes));
	}

	public function testArrayFieldGetValueRoundTripsAnArray(): void
	{
		$field = new ArrayField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', [1, 2, 3]);

		self::assertSame([1, 2, 3], $field->getValue($attributes));
	}

	public function testArrayFieldGetValueCoercesANonArrayScalarIntoASingleElementArray(): void
	{
		$field = new ArrayField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not an array');

		self::assertSame(['not an array'], $field->getValue($attributes));
	}

	public function testArrayFieldGetValueOnMissingKeyReturnsEmptyArrayOrNullDependingOnNullable(): void
	{
		$nullableField = new ArrayField(true, 'field', 'field', true, true);
		$notNullableField = new ArrayField(false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertSame([], $nullableField->getValue($attributes));
		self::assertNull($notNullableField->getValue($attributes));
	}

	public function testArrayFieldGetValueConvertsANestedStandardObjectViaToArray(): void
	{
		$field = new ArrayField(false, 'field', 'field', true, true);

		$nested = (new StandardObject())->set('inner', 'value');
		$attributes = (new StandardObject())->set('field', $nested);

		self::assertSame(['inner' => 'value'], $field->getValue($attributes));
	}

	public function testMixedFieldGetValueReturnsTheAttributeUnchanged(): void
	{
		$field = new MixedField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', ['nested' => 'value']);

		self::assertSame(['nested' => 'value'], $field->getValue($attributes));
	}

	public function testMixedFieldGetValueOnMissingKeyReturnsNullRegardlessOfNullable(): void
	{
		$nullableField = new MixedField(true, 'field', 'field', true, true);
		$notNullableField = new MixedField(false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertNull($nullableField->getValue($attributes));
		self::assertNull($notNullableField->getValue($attributes));
	}

	public function testEntityFieldGetClassNameIsNullableAndIsRelationshipReturnConstructorArgumentsUnchanged(): void
	{
		$field = new SingleEntityField(stdClass::class, true, 'field', false, 'field', true, true);

		self::assertSame(stdClass::class, $field->getClassName());
		self::assertTrue($field->isNullable());
		self::assertFalse($field->isRelationship());
	}

}
