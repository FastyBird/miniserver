<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\States;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\States;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_key_exists;
use function array_keys;
use function array_slice;
use function is_string;
use function serialize;

/**
 * A property state change tells a given field from an absent one by the field's key, not by a
 * marker value, and turns into exactly the ArrayHash its callers built before it existed (#723)
 */
final class PropertyUpdateTest extends TestCase
{

	public function testAnAbsentFieldStaysAbsentAndANullFieldIsGiven(): void
	{
		self::assertTrue(States\PropertyUpdate::create()->isEmpty());
		self::assertSame([], States\PropertyUpdate::create()->toArray());
		self::assertCount(0, States\PropertyUpdate::create()->toArrayHash());

		$update = States\PropertyUpdate::create()->withExpectedValue(null);

		self::assertFalse($update->isEmpty());
		self::assertSame([States\Property::EXPECTED_VALUE_FIELD => null], $update->toArray());

		$hash = $update->toArrayHash();

		// null clears: the key is there, holding null
		self::assertTrue(array_key_exists(States\Property::EXPECTED_VALUE_FIELD, (array) $hash));
		self::assertNull($hash->offsetGet(States\Property::EXPECTED_VALUE_FIELD));

		// absent keeps: the key is not there at all
		self::assertFalse($hash->offsetExists(States\Property::ACTUAL_VALUE_FIELD));
		self::assertFalse($hash->offsetExists(States\Property::PENDING_FIELD));
		self::assertFalse($hash->offsetExists(States\Property::VALID_FIELD));
	}

	/**
	 * 'N/A' is `Utilities\Value::NOT_SET`, the marker the action documents use for a value that
	 * was not sent. Here it is an ordinary string and survives the way to the ArrayHash.
	 */
	public function testTheNotSetMarkerIsAnOrdinaryValue(): void
	{
		$update = States\PropertyUpdate::create()
			->withActualValue(Utilities\Value::NOT_SET)
			->withExpectedValue('N/A');

		$expected = [
			States\Property::ACTUAL_VALUE_FIELD => 'N/A',
			States\Property::EXPECTED_VALUE_FIELD => 'N/A',
		];

		self::assertSame($expected, $update->toArray());
		self::assertSame($expected, (array) $update->toArrayHash());
	}

	public function testFieldsKeepTheOrderTheyWereFirstGivenIn(): void
	{
		$update = States\PropertyUpdate::create()
			->withPending(false)
			->withExpectedValue(10)
			->withValid(false)
			->withPending(true);

		self::assertSame(
			[
				States\Property::PENDING_FIELD => true,
				States\Property::EXPECTED_VALUE_FIELD => 10,
				States\Property::VALID_FIELD => false,
			],
			$update->toArray(),
		);
	}

	public function testEveryWitherReturnsANewChange(): void
	{
		$empty = States\PropertyUpdate::create();
		$valid = $empty->withValid(true);
		$actual = $valid->withActualValue('on');

		self::assertNotSame($empty, $valid);
		self::assertNotSame($valid, $actual);
		self::assertSame([], $empty->toArray());
		self::assertSame([States\Property::VALID_FIELD => true], $valid->toArray());
		self::assertSame(
			[States\Property::VALID_FIELD => true, States\Property::ACTUAL_VALUE_FIELD => 'on'],
			$actual->toArray(),
		);
	}

	public function testPendingSinceATimeIsItsAtomString(): void
	{
		self::assertSame(
			[States\Property::PENDING_FIELD => '2020-04-01T12:00:00+02:00'],
			States\PropertyUpdate::create()
				->withPending(new DateTimeImmutable('2020-04-01T12:00:00+02:00'))
				->toArray(),
		);
	}

	/**
	 * Every ArrayHash is a new one: writeState() changes the one it is given, and that must not
	 * reach the change or the next ArrayHash made from it
	 */
	public function testTheArrayHashIsANewOneEveryTime(): void
	{
		$update = States\PropertyUpdate::create()->withActualValue('on');

		$first = $update->toArrayHash();
		$first->offsetSet(States\Property::VALID_FIELD, false);
		$first->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);

		$second = $update->toArrayHash();

		self::assertNotSame($first, $second);
		self::assertSame([States\Property::ACTUAL_VALUE_FIELD => 'on'], (array) $second);
		self::assertSame([States\Property::ACTUAL_VALUE_FIELD => 'on'], $update->toArray());
	}

	/**
	 * @return array<string, array{array<string, bool|float|int|string|DateTimeInterface|Payloads\Payload|null>}>
	 */
	public static function palettes(): array
	{
		return [
			'nulls' => [[
				States\Property::ACTUAL_VALUE_FIELD => null,
				States\Property::EXPECTED_VALUE_FIELD => null,
				States\Property::PENDING_FIELD => false,
				States\Property::VALID_FIELD => false,
			]],
			'scalars' => [[
				States\Property::ACTUAL_VALUE_FIELD => 21.5,
				States\Property::EXPECTED_VALUE_FIELD => 22,
				States\Property::PENDING_FIELD => '2020-04-01T12:00:00+00:00',
				States\Property::VALID_FIELD => true,
			]],
			'strings and markers' => [[
				States\Property::ACTUAL_VALUE_FIELD => Utilities\Value::NOT_SET,
				States\Property::EXPECTED_VALUE_FIELD => '',
				States\Property::PENDING_FIELD => true,
				States\Property::VALID_FIELD => true,
			]],
			'objects' => [[
				States\Property::ACTUAL_VALUE_FIELD => new DateTimeImmutable('2020-04-01T12:00:00+00:00'),
				States\Property::EXPECTED_VALUE_FIELD => Payloads\Switcher::ON,
				States\Property::PENDING_FIELD => false,
				States\Property::VALID_FIELD => true,
			]],
		];
	}

	/**
	 * For every subset of the four fields, in every order, the change gives the very ArrayHash
	 * the old callers built with `ArrayHash::from()` of the same array: the same class, the same
	 * keys present and absent, in the same order, with the same values (identical, objects
	 * included), and the same serialized form. Recursion cannot differ: `from()` converts only
	 * array values, and a field cannot hold one.
	 *
	 * @param array<string, bool|float|int|string|DateTimeInterface|Payloads\Payload|null> $palette
	 *
	 * @throws DateMalformedStringException
	 */
	#[DataProvider('palettes')]
	public function testTheArrayHashIsTheOneTheCallersBuilt(array $palette): void
	{
		$orders = self::orders(array_keys($palette));

		// 1 + 4 + 12 + 24 + 24 ordered subsets of four fields
		self::assertCount(65, $orders);

		foreach ($orders as $order) {
			$array = [];
			$update = States\PropertyUpdate::create();

			foreach ($order as $field) {
				$array[$field] = $palette[$field];

				$update = match ($field) {
					States\Property::ACTUAL_VALUE_FIELD => $update->withActualValue($palette[$field]),
					States\Property::EXPECTED_VALUE_FIELD => $update->withExpectedValue($palette[$field]),
					States\Property::PENDING_FIELD => $update->withPending(self::pending($palette[$field])),
					default => $update->withValid($palette[$field] === true),
				};
			}

			$old = Utils\ArrayHash::from($array);
			$new = $update->toArrayHash();

			self::assertSame(Utils\ArrayHash::class, $new::class);
			self::assertSame((array) $old, (array) $new);
			self::assertSame(array_keys((array) $old), array_keys((array) $new));
			self::assertSame(serialize($old), serialize($new));
			self::assertSame($array, $update->toArray());
			self::assertSame($array === [], $update->isEmpty());
		}
	}

	/**
	 * @param list<string> $fields
	 *
	 * @return list<list<string>>
	 */
	private static function orders(array $fields): array
	{
		$orders = [[]];

		foreach ($fields as $index => $field) {
			$rest = [...array_slice($fields, 0, $index), ...array_slice($fields, $index + 1)];

			foreach (self::orders($rest) as $tail) {
				$orders[] = [$field, ...$tail];
			}
		}

		return $orders;
	}

	/**
	 * The pending field as a caller gives it: a flag, or the time its ATOM string was made from
	 *
	 * @throws DateMalformedStringException
	 */
	private static function pending(
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value,
	): bool|DateTimeInterface
	{
		if (is_string($value)) {
			return new DateTimeImmutable($value);
		}

		self::assertIsBool($value);

		return $value;
	}

}
