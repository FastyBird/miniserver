<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use DateTimeInterface;
use FastyBird\Core\Encoding\JsonApi\Objects\StandardObject;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\ArrayField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BackedEnumField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BooleanField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\DateTimeField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\MixedField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\NumberField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\SingleEntityField;
use FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\TextField;
use FastyBird\Core\Values\Types;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use ValueError;

/**
 * Characterizes the pure value-coercion behaviour of the JSON:API hydrator field
 * classes as they exist today, ahead of the E3 move to `FastyBird\Core\Api\Hydrators\Fields\`.
 *
 * NumberField, BooleanField, ArrayField and BackedEnumField reject malformed input (#476,
 * decided 2026-09-22) rather than silently coercing it -- the tests named accordingly
 * document that reversal rather than the original defect.
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

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testNumberFieldGetValueCastsToIntWhenNotDecimal(): void
	{
		$field = new NumberField($this->createTranslator(), false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '42');

		self::assertSame(42, $field->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testNumberFieldGetValueCastsToFloatWhenDecimal(): void
	{
		$field = new NumberField($this->createTranslator(), true, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '4.5');

		self::assertSame(4.5, $field->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testNumberFieldGetValueTruncatesADecimalStringWhenNotDecimal(): void
	{
		$field = new NumberField($this->createTranslator(), false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '4.5');

		self::assertSame(4, $field->getValue($attributes));
	}

	public function testNumberFieldGetValueOnNonNumericStringThrowsJsonApiErrorWithAttributePointer(): void
	{
		$field = new NumberField($this->createTranslator(), false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not a number');

		try {
			$field->getValue($attributes);

			self::fail('NumberField::getValue() did not reject a non-numeric string.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame(['pointer' => '/data/attributes/field'], $ex->getSource());
		}
	}

	public function testNumberFieldGetValueRejectsABooleanRatherThanCastingItToZeroOrOne(): void
	{
		$field = new NumberField($this->createTranslator(), false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', true);

		try {
			$field->getValue($attributes);

			self::fail('NumberField::getValue() did not reject a boolean value.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
		}
	}

	/**
	 * A non-scalar value (array/object) is out of scope for #476 -- it is treated the
	 * same as an absent attribute, not rejected. Pinned so this stays a deliberate
	 * boundary rather than an undocumented silent-coercion path.
	 *
	 * @throws Exceptions\JsonApiError
	 */
	public function testNumberFieldGetValueOnNonScalarReturnsNullRatherThanThrowing(): void
	{
		$field = new NumberField($this->createTranslator(), false, false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', ['nested' => 'value']);

		self::assertNull($field->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testBooleanFieldGetValueReturnsActualBooleanUnchanged(): void
	{
		$field = new BooleanField($this->createTranslator(), false, 'field', 'field', true, true);

		self::assertTrue($field->getValue((new StandardObject())->set('field', true)));
		self::assertFalse($field->getValue((new StandardObject())->set('field', false)));
	}

	#[DataProvider('nonBooleanScalars')]
	public function testBooleanFieldGetValueRejectsAnyNonBooleanScalar(bool|string|int $value): void
	{
		$field = new BooleanField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', $value);

		try {
			$field->getValue($attributes);

			self::fail('BooleanField::getValue() did not reject a non-boolean scalar.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame(['pointer' => '/data/attributes/field'], $ex->getSource());
		}
	}

	/**
	 * @return array<string, array{0: string|int}>
	 */
	public static function nonBooleanScalars(): array
	{
		return [
			'string_true' => ['true'],
			// Any non-empty string other than "0" is truthy in PHP -- including the
			// string "false". This is the case that made #476 obvious: a client
			// serialising a boolean as the string "false" used to get back `true`.
			// Every one of these cases must now be rejected, not just this one --
			// option 1 closed the lenient "1" -> true reading along with it.
			'string_false' => ['false'],
			'string_one' => ['1'],
			'string_zero' => ['0'],
			'int_one' => [1],
			'int_zero' => [0],
		];
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testBooleanFieldGetValueOnMissingKeyReturnsNullOrFalseDependingOnNullable(): void
	{
		$nullableField = new BooleanField($this->createTranslator(), true, 'field', 'field', true, true);
		$notNullableField = new BooleanField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertNull($nullableField->getValue($attributes));
		self::assertFalse($notNullableField->getValue($attributes));
	}

	/**
	 * `IStandardObject::get()` cannot distinguish an attribute explicitly sent as JSON
	 * `null` from one that was never sent at all -- both reach `getValue()` as PHP
	 * `null`. Pinned deliberately so a future change to that boundary is a visible
	 * decision instead of a silent behavioural drift.
	 *
	 * @throws Exceptions\JsonApiError
	 */
	public function testBooleanFieldGetValueOnExplicitNullIsTreatedTheSameAsMissingKey(): void
	{
		$nullableField = new BooleanField($this->createTranslator(), true, 'field', 'field', true, true);
		$notNullableField = new BooleanField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', null);

		self::assertNull($nullableField->getValue($attributes));
		self::assertFalse($notNullableField->getValue($attributes));
	}

	/**
	 * @throws ValueError
	 */
	public function testDateTimeFieldGetValueParsesAtomFormatAndPreservesTheInstant(): void
	{
		$field = new DateTimeField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', '2026-09-21T12:00:00+00:00');

		$value = $field->getValue($attributes);

		self::assertInstanceOf(DateTimeInterface::class, $value);
		self::assertSame('2026-09-21T12:00:00+00:00', $value->format(DateTimeInterface::ATOM));
	}

	/**
	 * @throws ValueError
	 */
	public function testDateTimeFieldGetValueReturnsNullForAnUnparseableStringRatherThanThrowing(): void
	{
		$field = new DateTimeField(false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not a date');

		self::assertNull($field->getValue($attributes));
	}

	/**
	 * @throws ValueError
	 */
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

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testBackedEnumFieldGetValueReturnsTheMatchingCaseForAValidBackingValue(): void
	{
		$field = new BackedEnumField(
			$this->createTranslator(),
			Types\DataType::class,
			false,
			'field',
			'field',
			true,
			true,
		);

		$attributes = (new StandardObject())->set('field', 'char');

		self::assertSame(Types\DataType::CHAR, $field->getValue($attributes));
	}

	public function testBackedEnumFieldGetValueThrowsJsonApiErrorForAnInvalidBackingValue(): void
	{
		$field = new BackedEnumField(
			$this->createTranslator(),
			Types\DataType::class,
			false,
			'field',
			'field',
			true,
			true,
		);

		$attributes = (new StandardObject())->set('field', 'not-a-data-type');

		try {
			$field->getValue($attributes);

			self::fail('BackedEnumField::getValue() did not reject an invalid backing value.');
		} catch (Exceptions\JsonApiError $ex) {
			// Closes the gap #476 called out: previously a bare `\ValueError` reached
			// the controllers' generic `catch (Throwable)` and came back as a 422 with
			// no pointer. It must carry the same field-specific pointer as the other
			// three fields now.
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame(['pointer' => '/data/attributes/field'], $ex->getSource());
		}
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testBackedEnumFieldGetValueReturnsNullWhenAttributeIsAbsentRatherThanThrowing(): void
	{
		$field = new BackedEnumField(
			$this->createTranslator(),
			Types\DataType::class,
			false,
			'field',
			'field',
			true,
			true,
		);

		$attributes = new StandardObject();

		self::assertNull($field->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testArrayFieldGetValueRoundTripsAnArray(): void
	{
		$field = new ArrayField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', [1, 2, 3]);

		self::assertSame([1, 2, 3], $field->getValue($attributes));
	}

	public function testArrayFieldGetValueRejectsANonArrayScalarRatherThanWrappingIt(): void
	{
		$field = new ArrayField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', 'not an array');

		try {
			$field->getValue($attributes);

			self::fail('ArrayField::getValue() did not reject a non-array scalar.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame(['pointer' => '/data/attributes/field'], $ex->getSource());
		}
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testArrayFieldGetValueOnMissingKeyReturnsEmptyArrayOrNullDependingOnNullable(): void
	{
		$nullableField = new ArrayField($this->createTranslator(), true, 'field', 'field', true, true);
		$notNullableField = new ArrayField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = new StandardObject();

		self::assertSame([], $nullableField->getValue($attributes));
		self::assertNull($notNullableField->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testArrayFieldGetValueOnExplicitNullIsTreatedTheSameAsMissingKey(): void
	{
		$nullableField = new ArrayField($this->createTranslator(), true, 'field', 'field', true, true);
		$notNullableField = new ArrayField($this->createTranslator(), false, 'field', 'field', true, true);

		$attributes = (new StandardObject())->set('field', null);

		self::assertSame([], $nullableField->getValue($attributes));
		self::assertNull($notNullableField->getValue($attributes));
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	public function testArrayFieldGetValueConvertsANestedStandardObjectViaToArray(): void
	{
		$field = new ArrayField($this->createTranslator(), false, 'field', 'field', true, true);

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

	private function createTranslator(): Localization\Translator
	{
		$translator = $this->createMock(Localization\Translator::class);
		$translator->method('translate')->willReturnArgument(0);

		return $translator;
	}

}
