<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use ArrayObject;
use DateTimeImmutable;
use Error;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Tests\Fixtures\Dummy;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use function array_merge;
use function array_slice;
use function count;
use function gettype;
use function is_object;
use function json_encode;
use function mt_rand;
use function mt_srand;
use function preg_replace;
use function serialize;
use function sprintf;
use const INF;
use const PHP_INT_MAX;

/**
 * The converters of the Devices documents against the rules they replace. One class is mapped by orisai with the rules
 * the format, the value transformer and the value of a state had, copied word for word, the other by Valinor with the
 * converters; both are given the same input and must either refuse it or end up holding the same thing, down to the
 * int that is not a float and the text that is not a UUID. The inputs are a fixed grid and a seeded sample of every
 * shape the format rule talks about, so a rule the converter reads another way fails here with the input that shows it
 */
final class ConverterParityTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param array<string, mixed> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('fields')]
	public function testBothMapAnInputAsOne(string $field, array $inputs): void
	{
		$mapper = $this->getContainer()->getByType(CoreDocuments\Mapping\Mapper::class);
		$options = new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: true);

		$different = [];
		$accepted = 0;

		foreach ($inputs as $label => $input) {
			$orisai = self::map($mapper, Dummy\DummyParityOrisaiDocument::class, $field, $input, $options);
			$valinor = self::map($mapper, Dummy\DummyParityValinorDocument::class, $field, $input, $options);

			if ($orisai !== null) {
				$accepted++;
			}

			if ($orisai === null || $valinor === null) {
				if ($orisai !== $valinor) {
					$different[] = sprintf(
						'%s: orisai %s, Valinor %s',
						$label,
						$orisai === null ? 'refused' : 'accepted',
						$valinor === null ? 'refused' : 'accepted',
					);
				}

				continue;
			}

			// The class name is the one thing the two documents do not share
			if (self::normalise($orisai) !== self::normalise($valinor)) {
				$different[] = sprintf('%s: orisai %s, Valinor %s', $label, $orisai, $valinor);
			}
		}

		self::assertSame([], array_slice($different, 0, 10), count($different) . ' of ' . count($inputs) . ' differ');
		// The grid is no use if it is refused wholesale
		self::assertGreaterThan(count($inputs) / 100, $accepted);
	}

	/**
	 * What differs, and the one thing: orisai read a date and a payload only from the text they are written as, Valinor
	 * also takes the object itself, as it takes a UUID object. Nothing builds a document from such an object (what the
	 * documents are built from is JSON, and arrays of what entities write), so Valinor's reading is kept and stated here
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testValinorTakesAnObjectOfTheTypeWhereOrisaiTookItsText(): void
	{
		$mapper = $this->getContainer()->getByType(CoreDocuments\Mapping\Mapper::class);
		$options = new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: true);
		$date = new DateTimeImmutable('2020-04-01T12:00:00+00:00');

		foreach ([$date, Payloads\Switcher::ON, Payloads\Button::PRESSED, Payloads\Cover::STOP] as $object) {
			self::assertNull(self::map($mapper, Dummy\DummyParityOrisaiDocument::class, 'value', $object, $options));
			self::assertNotNull(
				self::map($mapper, Dummy\DummyParityValinorDocument::class, 'value', $object, $options),
			);
		}
	}

	/**
	 * What else Valinor takes as an object where orisai took its scalar or array form (all refused by orisai at the base
	 * commit): the enum case for the `action` of a control action and of a property action, a ready ActionValues for `set`
	 * and `write`, and any ArrayObject for them. The document is the same as the one made from the plain form
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testValinorTakesTheOtherObjectsOrisaiRefused(): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);
		$id = '176984ad-7cf7-465d-9e53-71668a74a688';
		$parent = '247fd6b5-8466-4323-81de-cec3e315015a';

		foreach (
			[
				[
					DevicesDocuments\Channels\Controls\Actions\Action::class,
					['channel' => $parent, 'control' => $id],
					'action',
					DevicesTypes\ControlAction::SET,
					DevicesTypes\ControlAction::SET->value,
				],
				[
					DevicesDocuments\Devices\Controls\Actions\Action::class,
					['device' => $parent, 'control' => $id],
					'action',
					DevicesTypes\ControlAction::SET,
					DevicesTypes\ControlAction::SET->value,
				],
				[
					DevicesDocuments\Connectors\Controls\Actions\Action::class,
					['connector' => $parent, 'control' => $id],
					'action',
					DevicesTypes\ControlAction::SET,
					DevicesTypes\ControlAction::SET->value,
				],
				[
					DevicesDocuments\States\Channels\Properties\Actions\Action::class,
					['channel' => $parent, 'property' => $id],
					'action',
					DevicesTypes\PropertyAction::GET,
					DevicesTypes\PropertyAction::GET->value,
				],
				[
					DevicesDocuments\States\Devices\Properties\Actions\Action::class,
					['device' => $parent, 'property' => $id],
					'action',
					DevicesTypes\PropertyAction::GET,
					DevicesTypes\PropertyAction::GET->value,
				],
				[
					DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
					['connector' => $parent, 'property' => $id],
					'action',
					DevicesTypes\PropertyAction::GET,
					DevicesTypes\PropertyAction::GET->value,
				],
				[
					DevicesDocuments\States\Channels\Properties\Actions\Action::class,
					['action' => 'set', 'channel' => $parent, 'property' => $id],
					'set',
					new DevicesDocuments\States\ActionValues(1, 2),
					['actual_value' => 1, 'expected_value' => 2],
				],
				[
					DevicesDocuments\States\Channels\Properties\Actions\Action::class,
					['action' => 'set', 'channel' => $parent, 'property' => $id],
					'write',
					new ArrayObject(['actual_value' => 1, 'expected_value' => 2]),
					['actual_value' => 1, 'expected_value' => 2],
				],
				[
					DevicesDocuments\States\Channels\Properties\Actions\Action::class,
					['action' => 'set', 'channel' => $parent, 'property' => $id],
					'set',
					new ArrayObject(['actual_value' => 'switch_on']),
					['actual_value' => 'switch_on'],
				],
			] as [$class, $base, $field, $object, $plain]
		) {
			$fromObject = $factory->create($class, array_merge($base, [$field => $object]));
			$fromPlain = $factory->create($class, array_merge($base, [$field => $plain]));

			self::assertSame($fromPlain->toArray(), $fromObject->toArray(), $class . ' ' . $field);
		}
	}

	/**
	 * @return array<string, array{string, array<string, mixed>}>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function fields(): array
	{
		return [
			'format' => ['format', self::formats()],
			'value transformer' => ['value_transformer', self::transformers()],
			'value' => ['value', self::values()],
		];
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 *
	 * @return string|null the serialised document, null when the input was refused
	 */
	private static function map(
		CoreDocuments\Mapping\Mapper $mapper,
		string $class,
		string $field,
		mixed $input,
		CoreDocuments\Mapping\MappingOptions $options,
	): string|null
	{
		try {
			return serialize($mapper->map([$field => $input], $class, $options));
		} catch (CoreDocuments\Exceptions\MappingFailed) {
			return null;
		}
	}

	private static function normalise(string $serialised): string
	{
		return (string) preg_replace(
			'/^O:\d+:"[^"]+":(\d+):\{/',
			'O:0:"":$1:{',
			(string) preg_replace('/s:\d+:"\x00[^"]*\x00(\w+)"/', 's:0:"$1"', $serialised),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function formats(): array
	{
		$atoms = [
			null,
			'',
			' ',
			"\u{00A0}",
			"\t\n",
			'a',
			'one',
			'1',
			'u8',
			'sw',
			'zz',
			'b',
			'btn',
			0,
			1,
			2,
			-1,
			2.5,
			0.0,
			true,
			false,
			INF,
			PHP_INT_MAX,
			[],
			new DateTimeImmutable('2020-04-01T12:00:00+00:00'),
		];
		$pairAtoms = ['u8', 'i8', 'f', 'sw', 'b', 's', 'zz', '1', ' ', 1, 2.5, 0, null, true, false];
		$pairs = [];

		foreach ($pairAtoms as $first) {
			foreach ($pairAtoms as $second) {
				$pairs[] = [$first, $second];
			}
		}

		$elements = [...$atoms, ...$pairs, [['u8', 1], ['u8', 2]], [1, 2, 3], ['a', 'b', 'c']];

		$inputs = [];

		foreach ($atoms as $atom) {
			$inputs[self::label($atom)] = $atom;
		}

		foreach ($atoms as $first) {
			$inputs[self::label([$first])] = [$first];

			foreach ($atoms as $second) {
				$inputs[self::label([$first, $second])] = [$first, $second];
			}
		}

		// Three of the atoms, the range and the combined enum are one and two rows of these
		mt_srand(724);

		for ($i = 0; $i < 3_000; $i++) {
			$row = [$elements[mt_rand(0, count($elements) - 1)], $elements[mt_rand(
				0,
				count($elements) - 1,
			)], $elements[mt_rand(
				0,
				count($elements) - 1,
			)]];
			$inputs[self::label($row)] = $row;
		}

		for ($i = 0; $i < 3_000; $i++) {
			$row = [$elements[mt_rand(0, count($elements) - 1)], $elements[mt_rand(0, count($elements) - 1)]];
			$inputs[self::label($row)] = $row;
		}

		for ($i = 0; $i < 3_000; $i++) {
			$rows = [];

			for ($j = mt_rand(1, 3); $j > 0; $j--) {
				$rows[] = [$elements[mt_rand(0, count($elements) - 1)], $elements[mt_rand(
					0,
					count($elements) - 1,
				)], $elements[mt_rand(
					0,
					count($elements) - 1,
				)]];
			}

			$inputs[self::label($rows)] = $rows;
		}

		// A row made of what a combined enum is made of, mostly valid
		foreach ($pairs as $first) {
			foreach ([null, '', 'x', 5, 5.5, $pairs[7], [1, 2]] as $second) {
				foreach ([null, 'on', ['s', 'on'], 1.5] as $third) {
					$inputs[self::label([[$first, $second, $third]])] = [[$first, $second, $third]];
					$inputs[self::label([$first, $second, $third])] = [$first, $second, $third];
				}
			}
		}

		// Keys: a list of three that starts anywhere, goes backwards or has a key which is not a number
		$keys = [0, 1, 2, 3, 7, -1, 'a', '1', '01', ''];

		for ($i = 0; $i < 1_500; $i++) {
			$array = [];

			for ($j = mt_rand(1, 3); $j > 0; $j--) {
				$array[$keys[mt_rand(0, count($keys) - 1)]] = $elements[mt_rand(0, count($elements) - 1)];
			}

			$inputs[self::label($array)] = $array;
		}

		for ($i = 0; $i < 1_500; $i++) {
			$rows = [];

			for ($j = mt_rand(1, 2); $j > 0; $j--) {
				$row = [];

				for ($k = 3; $k > 0; $k--) {
					$row[$keys[mt_rand(0, count($keys) - 1)]] = $elements[mt_rand(0, count($elements) - 1)];
				}

				$rows[$keys[mt_rand(0, count($keys) - 1)]] = $row;
			}

			$inputs[self::label($rows)] = $rows;
		}

		// Keys that are not a place in a list, for each of the three shapes
		foreach (
			[
				[-1 => 'a'],
				[0 => 'a', -1 => 'b'],
				['k' => 'a'],
				['0' => 'a', '1' => 'b'],
				[-1 => [1, 2, 3]],
				[0 => [1, 2, 3], 'k' => [4, 5, 6]],
				[[-1 => 1, 0 => 2, 1 => 3]],
				[0 => 1, -1 => 2],
				['a' => 1, 'b' => 2],
				[[-1 => 'u8', 0 => 1], 2],
				[0 => [['u8', 1], -1 => 2], 1 => 3],
			] as $input
		) {
			$inputs[self::label($input)] = $input;
		}

		foreach (
			[
				'0:100',
				'one,two',
				[['u8', 10.5], 50],
				[['i8', -1], ['u16', 5]],
				['one', 'two', 'three'],
				[[['sw', 'switch_on'], '1000', ['s', 'on']], [['sw', 'switch_off'], '2000', ['s', 'off']]],
				[[['sw', 'switch_on'], null, ['s', 'on']]],
				[[1, 2.0, 3], [1.0, 2, 3.5]],
			] as $input
		) {
			$inputs[self::label($input)] = $input;
		}

		return $inputs;
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private static function transformers(): array
	{
		$uuid = '3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f10';

		$all = [
			null,
			'',
			' ',
			"\t",
			"\u{00A0}",
			$uuid,
			'3A9D0F6C-6E0C-4C31-A9C5-0F8D2F4B7F10',
			'{' . $uuid . '}',
			'urn:uuid:' . $uuid,
			'3a9d0f6c6e0c4c31a9c50f8d2f4b7f10',
			$uuid . ' ',
			' ' . $uuid,
			'3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f1',
			'3a9d0f6c-6e0c-4c31-a9c5-0f8d2f4b7f1g',
			'00000000-0000-0000-0000-000000000000',
			'ffffffff-ffff-ffff-ffff-ffffffffffff',
			'equation:x=y*2',
			'equation:x',
			'x',
			'1',
			0,
			1,
			5.5,
			true,
			false,
			[],
			[$uuid],
			Uuid\Uuid::fromString($uuid),
			new DateTimeImmutable('2020-04-01T12:00:00+00:00'),
		];

		$inputs = [];

		foreach ($all as $input) {
			$inputs[self::label($input)] = $input;
		}

		return $inputs;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function values(): array
	{
		$all = [
			null,
			'',
			' ',
			"\t\n",
			"\f",
			"\u{00A0}",
			'x',
			' padded ',
			'5',
			'5.5',
			'1e3',
			'true',
			'false',
			'null',
			'N/A',
			'switch_on',
			'switch_off',
			'switch_toggle',
			'btn_pressed',
			'btn_clicked',
			'btn_extra_long_clicked',
			'cover_open',
			'cover_stop',
			'SWITCH_ON',
			' switch_on',
			"switch_on\n",
			'switch_flip',
			'on',
			'2020-04-01T12:00:00+00:00',
			'2021-11-30T23:59:59+02:00',
			'2020-04-01T12:00:00Z',
			'2020-04-01T12:00:00.123+00:00',
			'2020-02-31T12:00:00+00:00',
			'2020-04-01',
			'2020-04-01 12:00:00',
			' 2020-04-01T12:00:00+00:00',
			'1585742400',
			0,
			1,
			-1,
			5,
			1_585_742_400,
			PHP_INT_MAX,
			0.0,
			5.0,
			5.5,
			-0.5,
			INF,
			true,
			false,
			[],
			[1],
			['actual_value' => 1],
		];

		$inputs = [];

		foreach ($all as $input) {
			$inputs[self::label($input)] = $input;
		}

		return $inputs;
	}

	private static function label(mixed $input): string
	{
		$json = json_encode($input);

		return (is_object($input) ? $input::class . ' ' : '') . ($json === false ? serialize(
			$input,
		) : $json) . ' ' . gettype($input);
	}

}
