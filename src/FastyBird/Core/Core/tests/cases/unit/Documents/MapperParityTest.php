<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * The two branches of Mapper::map() side by side (#724): the same input goes to a class orisai maps and to its Valinor
 * twin, and the two have to accept and refuse alike and hand back the same values. The blank strings are the point:
 * orisai's notEmpty and castEmptyString decide what blank is, and RefuseBlank and MapBlankToNull, the template of every
 * notEmpty field still to move, have to decide the same
 */
final class MapperParityTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('inputs')]
	public function testBothBranchesTreatTheInputAlike(array $data): void
	{
		$options = new Documents\Mapping\MappingOptions(allowUnknownFields: true);
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		$orisai = null;
		$valinor = null;

		try {
			$orisai = $mapper->map($data, Dummy\DummyParityOrisaiDocument::class, $options)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		try {
			$valinor = $mapper->map($data, Dummy\DummyParityValinorDocument::class, $options)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		self::assertSame($orisai, $valinor);
	}

	/**
	 * Orisai 0.3.0 tests blankness with preg_match('/\S/') and no /u flag, so a string is blank when it holds nothing
	 * but ASCII white space, form feed and vertical tab included. A no-break space, a NUL, a zero width space and the
	 * Unicode spaces beyond ASCII are values. The converters do the same, and this test states it outright so that an
	 * upgrade of orisai, or a converter that trims by another rule, is told apart
	 *
	 * @param array{string, bool} $case
	 *
	 * @throws Throwable
	 */
	#[DataProvider('blanknessAsOrisaiDecidesIt')]
	public function testBlankIsWhatOrisaiCallsBlank(array $case): void
	{
		[$value, $blank] = $case;
		$options = new Documents\Mapping\MappingOptions(allowUnknownFields: true);
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		foreach ([Dummy\DummyParityOrisaiDocument::class, Dummy\DummyParityValinorDocument::class] as $class) {
			try {
				$accepted = $mapper->map(['id' => $value], $class, $options)->toArray()['id'];
			} catch (Documents\Exceptions\MappingFailed) {
				$accepted = null;
			}

			self::assertSame($blank ? null : $value, $accepted, $class);

			$name = $mapper->map(['id' => 'one', 'name' => $value], $class, $options)->toArray()['name'];

			self::assertSame($blank ? null : $value, $name, $class);
		}
	}

	/**
	 * @return array<string, array{array{string, bool}}>
	 */
	public static function blanknessAsOrisaiDecidesIt(): array
	{
		return [
			'space' => [[' ', true]],
			'tab, line feed, carriage return' => [["\t\n\r", true]],
			'vertical tab' => [["\v", true]],
			'form feed' => [["\f", true]],
			'NUL' => [["\0", false]],
			'no-break space' => [["\u{A0}", false]],
			'zero width space' => [["\u{200B}", false]],
			'line separator' => [["\u{2028}", false]],
			'ideographic space' => [["\u{3000}", false]],
		];
	}

	/**
	 * Every string in the lists below is held against orisai's own answer, not against a list written by hand: if
	 * orisai accepts one of them as a value, so must the converters, and if it takes one for blank, so must they
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function inputs(): array
	{
		$blanks = [
			'empty' => '',
			'space' => ' ',
			'tab, line feed, carriage return' => "\t\n\r",
			'vertical tab' => "\v",
			'form feed' => "\f",
			'NUL' => "\0",
			'no-break space' => "\u{A0}",
			'en quad' => "\u{2000}",
			'zero width space' => "\u{200B}",
			'line separator' => "\u{2028}",
			'paragraph separator' => "\u{2029}",
			'ideographic space' => "\u{3000}",
			'next line' => "\u{85}",
			'byte order mark' => "\u{FEFF}",
			'spaces and a no-break space' => " \u{A0} ",
			'a letter' => 'a',
			'a letter between spaces' => " a\u{A0}",
			'zero' => '0',
		];
		$cases = [];

		foreach ($blanks as $label => $value) {
			$cases['id: ' . $label] = [['id' => $value]];
			$cases['name: ' . $label] = [['id' => 'one', 'name' => $value]];
		}

		return $cases + [
			'only the id' => [['id' => 'one']],
			'everything' => [
				['id' => 'one', 'tags' => ['a', 'b'], 'name' => 'Name', 'updated_at' => '2021-11-30T23:59:59+02:00'],
			],
			'no id' => [['tags' => []]],
			'null id' => [['id' => null]],
			'id which is an int' => [['id' => 1]],
			'null tags' => [['id' => 'one', 'tags' => null]],
			'tags which are a string' => [['id' => 'one', 'tags' => 'a']],
			'a tag which is an int' => [['id' => 'one', 'tags' => ['a', 1]]],
			'a tag which is null' => [['id' => 'one', 'tags' => [null]]],
			'tags with keys' => [['id' => 'one', 'tags' => ['first' => 'a']]],
			'null name' => [['id' => 'one', 'name' => null]],
			'name which is an int' => [['id' => 'one', 'name' => 1]],
			'name which is an array' => [['id' => 'one', 'name' => ['a']]],
			'null updated_at' => [['id' => 'one', 'updated_at' => null]],
			'updated_at in another format' => [['id' => 'one', 'updated_at' => '2020-04-01 12:00:00']],
			'updated_at which is an int' => [['id' => 'one', 'updated_at' => 1_585_742_400]],
			'updated_at which is blank' => [['id' => 'one', 'updated_at' => '']],
		];
	}

}
