<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * RequirePattern against orisai's StringValue(pattern: ..., notEmpty: true) (#724): the same input goes to a class
 * orisai maps and to its Valinor twin, and the two have to accept and refuse alike and hand back the same value. The
 * pattern is the one of an e-mail address, and the inputs are the ones an address and its mistakes are made of
 */
final class RequirePatternParityTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Throwable
	 */
	#[DataProvider('inputs')]
	public function testBothBranchesTreatTheInputAlike(mixed $email): void
	{
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);
		$orisai = null;
		$valinor = null;

		try {
			$orisai = $mapper->map(['email' => $email], Dummy\DummyParityPatternOrisaiDocument::class)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		try {
			$valinor = $mapper->map(['email' => $email], Dummy\DummyParityPatternValinorDocument::class)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		self::assertSame($orisai, $valinor);
	}

	/**
	 * The known-positive control: the comparison above has to see an address accepted and a mistake refused, or it
	 * compares two refusals
	 *
	 * @throws Throwable
	 */
	public function testAnAddressIsAcceptedAndAMistakeIsRefused(): void
	{
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		foreach ([Dummy\DummyParityPatternOrisaiDocument::class, Dummy\DummyParityPatternValinorDocument::class] as $class) {
			self::assertSame(['email' => 'a@example.com'], $mapper->map(['email' => 'a@example.com'], $class)->toArray());

			try {
				$mapper->map(['email' => 'a@example'], $class);

				self::fail('the mapper accepted the data');
			} catch (Documents\Exceptions\MappingFailed) {
				// the mistake is refused
			}
		}
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function inputs(): array
	{
		return [
			'an address' => ['a@example.com'],
			'an upper case address' => ['A@EXAMPLE.COM'],
			'an address with a plus' => ['a+b@example.com'],
			'an address with dashes and dots' => ['a.b-c@my-host.example.co.uk'],
			'an address with a line break at the end' => ["a@example.com\n"],
			'an address with a line break at the start' => ["\na@example.com"],
			'an address with a space around' => [' a@example.com '],
			'an address with a space inside' => ['a b@example.com'],
			'an address with a NUL' => ["a@example.com\0"],
			'an address without a domain' => ['a@'],
			'an address without a top level domain' => ['a@example'],
			'an address with a one letter top level domain' => ['a@example.c'],
			'an address with a 63 letter top level domain' => ['a@example.' . 'abcdefghi' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abc'],
			'an address with a 64 letter top level domain' => ['a@example.' . 'abcdefghi' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abcdefghij' . 'abcd'],
			'an address with a dot before the top level domain' => ['a@example..com'],
			'an address with an at sign twice' => ['a@b@example.com'],
			'an address with a letter outside ASCII' => ["\u{00E1}@example.com"],
			'a text without an at sign' => ['example.com'],
			'an empty string' => [''],
			'spaces' => [' '],
			'a tab and a line break' => ["\t\n"],
			'null' => [null],
			'a number' => [42],
			'true' => [true],
			'a list' => [['a@example.com']],
		];
	}

}
