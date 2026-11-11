<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * RefuseBlankItems against orisai's ArrayOf(StringValue(notEmpty: true), IntValue(unsigned: true)) (#724): the same input
 * goes to a class orisai maps and to its Valinor twin, and the two have to accept and refuse alike and hand back the same list
 */
final class RefuseBlankItemsParityTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Throwable
	 */
	#[DataProvider('inputs')]
	public function testBothBranchesTreatTheInputAlike(mixed $models): void
	{
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);
		$orisai = null;
		$valinor = null;

		try {
			$orisai = $mapper->map(['models' => $models], Dummy\DummyParityListOrisaiDocument::class)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		try {
			$valinor = $mapper->map(['models' => $models], Dummy\DummyParityListValinorDocument::class)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		self::assertSame($orisai, $valinor);
	}

	/**
	 * The known-positive control: the comparison above has to see a list accepted and a list refused, or it compares two
	 * refusals
	 *
	 * @throws Throwable
	 */
	public function testAListIsAcceptedAndAListWithABlankIsRefused(): void
	{
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		foreach ([Dummy\DummyParityListOrisaiDocument::class, Dummy\DummyParityListValinorDocument::class] as $class) {
			self::assertSame(['models' => ['A', 'B']], $mapper->map(['models' => ['A', 'B']], $class)->toArray());

			try {
				$mapper->map(['models' => ['A', ' ']], $class);

				self::fail('the mapper accepted the data');
			} catch (Documents\Exceptions\MappingFailed) {
				// the blank is refused
			}
		}
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function inputs(): array
	{
		return [
			'texts' => [['A', 'B']],
			'no texts' => [[]],
			'a text with spaces around' => [[' A ']],
			'a blank' => [['A', ' ']],
			'an empty string' => [['A', '']],
			'a tab and a line break' => [["\t\n"]],
			'a no-break space' => [["\u{00A0}"]],
			'a NUL' => [["\0"]],
			'a number' => [['A', 5]],
			'a null' => [['A', null]],
			'a bool' => [[true]],
			'a list in the list' => [[['A']]],
			'keys' => [[3 => 'A', 8 => 'B']],
			'a negative key' => [[-1 => 'A']],
			'words for keys' => [['a' => 'A']],
			'a text, not a list' => ['A'],
			'a number, not a list' => [5],
			'null' => [null],
		];
	}

}
