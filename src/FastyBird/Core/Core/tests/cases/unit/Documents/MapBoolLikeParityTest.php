<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use ArrayObject;
use DateTimeImmutable;
use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Throwable;
use function json_encode;
use const INF;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * MapBoolLike beside the rule it replaces (#724 PR4d): the same input goes to a class with BoolValue(castBoolLike: true),
 * which orisai maps, and to its Valinor twin, and the two have to accept and refuse alike and hand back the same flag.
 * What castBoolLike takes is `true` and `false` in any case, and 1 and 0 as numbers or as text; Valinor's own MapAsBool
 * takes them in lower case only
 */
final class MapBoolLikeParityTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('inputs')]
	public function testBothTreatTheFlagAlike(array $data): void
	{
		$options = new Documents\Mapping\MappingOptions(allowUnknownFields: true);
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		$orisai = null;
		$valinor = null;

		try {
			$orisai = $mapper->map($data, Dummy\DummyParityBoolOrisaiDocument::class, $options)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		try {
			$valinor = $mapper->map($data, Dummy\DummyParityBoolValinorDocument::class, $options)->toArray();
		} catch (Documents\Exceptions\MappingFailed) {
			// compared below
		}

		self::assertSame($orisai, $valinor);
	}

	/**
	 * The known positive: what is taken is taken for what it is, so a parity that holds because both refuse everything
	 * would be told
	 *
	 * @throws Throwable
	 */
	public function testWhatIsTakenForABoolIsTakenAsOne(): void
	{
		$options = new Documents\Mapping\MappingOptions(allowUnknownFields: true);
		$mapper = $this->container->getByType(Documents\Mapping\Mapper::class);

		foreach (
			[
				['true', true],
				['TRUE', true],
				['False', false],
				['1', true],
				['0', false],
				[1, true],
				[0, false],
				[true, true],
				[false, false],
			] as [$input, $flag]
		) {
			self::assertSame(
				['flag' => $flag],
				$mapper->map(['flag' => $input], Dummy\DummyParityBoolValinorDocument::class, $options)->toArray(),
			);
		}

		foreach (['yes', 'no', 'on', 'off', '01', '2', ' 1', '1 ', '', '-1', 2, -1, 1.0, 0.0, null, []] as $input) {
			try {
				$mapper->map(['flag' => $input], Dummy\DummyParityBoolValinorDocument::class, $options);

				self::fail('the flag was taken');
			} catch (Documents\Exceptions\MappingFailed) {
				// refused, as castBoolLike refuses it
			}
		}
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function inputs(): array
	{
		$values = [
			'true', 'false', 'TRUE', 'FALSE', 'True', 'False', 'tRuE', '1', '0', '01', '00', '-0', '+1', ' 1', '1 ', "1\n", '1.0', '0.0',
			'2', '-1', 'yes', 'no', 'on', 'off', 'y', 'n', 't', 'f', '', ' ', 'null', 'NULL', 'a',
			1, 0, 2, -1, PHP_INT_MAX, PHP_INT_MIN, 1.0, 0.0, 1.5, INF, true, false, null, [], [1], ['true'],
			new stdClass(), new ArrayObject([1]), new DateTimeImmutable('2020-04-01T12:00:00+00:00'),
		];

		$inputs = ['no flag' => [[]]];

		foreach ($values as $i => $value) {
			$inputs['a flag of ' . $i . ' (' . json_encode(
				$value,
				JSON_PARTIAL_OUTPUT_ON_ERROR,
			) . ')'] = [['flag' => $value]];
		}

		return $inputs;
	}

}
