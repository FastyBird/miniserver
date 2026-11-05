<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Tests;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
use function array_merge;

/**
 * What the Devices documents do with an input the mapping libraries cannot take apart: a NUL byte in a string that is
 * read as a date (the dates of a property and the pending flag of a state; the value of a state is a text then, see StateValueNulByteTest) and a key or a value which is not
 * valid UTF-8 where Valinor prints it into the message of its refusal. Each is a refusal, a MappingFailed from the
 * mapper, strict or lenient, and an InvalidArgument from DocumentFactory (#823, #828)
 */
final class MappedDocumentRefusalTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string CHANNEL = '247fd6b5-8466-4323-81de-cec3e315015a';

	private const string DATE = '2026-01-01T00:00:00+00:00';

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('inputs')]
	public function testItIsRefused(string $class, array $data, bool $lenient): void
	{
		$mapper = $this->getContainer()->getByType(CoreDocuments\Mapping\Mapper::class);

		try {
			$mapper->map($data, $class, new CoreDocuments\Mapping\MappingOptions(allowUnknownFields: $lenient));

			self::fail('the mapper accepted the data');
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertNotSame('', $ex->getMessage());
		}

		if (!$lenient) {
			// the factory maps with unknown fields allowed
			return;
		}

		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		try {
			$factory->create($class, $data);

			self::fail('the document was accepted');
		} catch (CoreExceptions\InvalidArgument $ex) {
			self::assertStringContainsString('Could not map data to document', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>, bool}>
	 */
	public static function inputs(): array
	{
		$property = DevicesDocuments\Channels\Properties\Variable::class;
		$state = DevicesDocuments\States\Channels\Properties\Property::class;

		$refused = [
			'a property created with a NUL byte after the date' => [
				$property,
				array_merge(self::variable(), ['created_at' => self::DATE . "\0"]),
			],
			'a property updated with a NUL byte only' => [
				$property,
				array_merge(self::variable(), ['updated_at' => "\0"]),
			],
			'a state pending with a NUL byte after the date' => [
				$state,
				array_merge(self::state(), ['pending' => self::DATE . "\0"]),
			],
			'a property whose id is not valid UTF-8' => [
				$property,
				array_merge(self::variable(), ['id' => "\xC3\x28"]),
			],
			'a state whose channel is not valid UTF-8' => [
				$state,
				array_merge(self::state(), ['channel' => "\xC3\x28"]),
			],
			'a property with a child which is not valid UTF-8' => [
				$property,
				array_merge(self::variable(), ['children' => [self::ID => "\xC3\x28"]]),
			],
		];

		$cases = [];

		foreach ($refused as $name => [$class, $data]) {
			$cases[$name . ', strict'] = [$class, $data, false];
			$cases[$name . ', lenient'] = [$class, $data, true];
		}

		$cases['a property with a key which is not valid UTF-8, strict'] = [
			$property,
			array_merge(self::variable(), ["\xC3\x28" => 'a']),
			false,
		];

		return $cases;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function variable(): array
	{
		return [
			'id' => self::ID,
			'type' => 'variable',
			'category' => 'generic',
			'identifier' => 'property-identifier',
			'name' => null,
			'data_type' => 'int',
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'scale' => null,
			'step' => null,
			'default' => null,
			'value_transformer' => null,
			'channel' => self::CHANNEL,
			'owner' => null,
			'created_at' => null,
			'updated_at' => null,
			'value' => 'two',
			'children' => [],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function state(): array
	{
		return [
			'id' => self::ID,
			'channel' => self::CHANNEL,
			'read' => ['actual_value' => 1, 'expected_value' => 2],
			'get' => ['actual_value' => 1, 'expected_value' => 2],
			'pending' => false,
			'valid' => false,
			'created_at' => null,
			'updated_at' => null,
		];
	}

}
