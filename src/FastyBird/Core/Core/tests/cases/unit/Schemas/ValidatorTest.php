<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Schemas;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Schemas;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function file_get_contents;

final class ValidatorTest extends TestCase
{

	/**
	 * @param array<string|bool|array<string, bool|float|int|string|null>> $expected
	 *
	 * @throws ValuesExceptions\InvalidData
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\MalformedInput
	 */
	#[DataProvider('validateValidData')]
	public function testValidateValidInput(
		string $data,
		string $schema,
		array $expected,
	): void
	{
		$validator = new Schemas\Validator();

		$result = $validator->validate($data, $schema);

		foreach ($expected as $key => $value) {
			self::assertSame($value, $result->offsetGet($key));
		}
	}

	/**
	 * @throws ValuesExceptions\InvalidData
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\MalformedInput
	 */
	#[DataProvider('validateInvalidData')]
	public function testValidateDevicePropertyInvalid(
		string $data,
		string $schema,
	): void
	{
		$validator = new Schemas\Validator();

		$this->expectException(ValuesExceptions\InvalidData::class);

		$validator->validate($data, $schema);
	}

	/**
	 * @return array<string, array<string|bool|array<string, bool|float|int|string|null>>>
	 *
	 * @throws Utils\JsonException
	 */
	public static function validateValidData(): array
	{
		return [
			'one' => [
				Utils\Json::encode([
					'attributeOne' => 'String value',
					'attributeTwo' => 20,
					'attributeThree' => false,
					'attributeFour' => null,
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
				[
					'attributeOne' => 'String value',
					'attributeTwo' => 20,
					'attributeThree' => false,
					'attributeFour' => null,
				],
			],
			'two' => [
				Utils\Json::encode([
					'attributeOne' => 'String value',
					'attributeTwo' => 20,
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
				[
					'attributeOne' => 'String value',
					'attributeTwo' => 20,
					'attributeThree' => true,
					'attributeFour' => null,
				],
			],
			'three' => [
				Utils\Json::encode([
					'attributeOne' => 'String value',
					'attributeTwo' => 2.2,
					'attributeThree' => false,
					'attributeFour' => 'String content',
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
				[
					'attributeOne' => 'String value',
					'attributeTwo' => 2.2,
					'attributeThree' => false,
					'attributeFour' => 'String content',
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 *
	 * @throws Utils\JsonException
	 */
	public static function validateInvalidData(): array
	{
		return [
			'one' => [
				Utils\Json::encode([
					'attributeOne' => 13,
					'attributeTwo' => 20,
					'attributeThree' => false,
					'attributeFour' => null,
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
			],
			'two' => [
				Utils\Json::encode([
					'attributeOne' => 'String value',
					'attributeTwo' => 'String value',
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
			],
			'three' => [
				Utils\Json::encode([
					'attributeOne' => 'String value',
					'attributeTwo' => 2.2,
					'attributeThree' => 10,
					'attributeFour' => 'String content',
				]),
				file_get_contents(__DIR__ . '/../../../fixtures/Schemas/validator.schema.json'),
			],
		];
	}

}
