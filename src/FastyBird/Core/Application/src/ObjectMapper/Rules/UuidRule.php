<?php declare(strict_types = 1);

/**
 * UuidRule.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     ObjectMapper
 * @since          1.0.0
 *
 * @date           02.08.23
 */

namespace FastyBird\Core\Application\ObjectMapper\Rules;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function is_string;

/**
 * @implements ObjectMapper\Rules\Rule<UuidArgs>
 */
final class UuidRule implements ObjectMapper\Rules\Rule
{

	public function resolveArgs(array $args, ObjectMapper\Meta\Context\MetaFieldContext $context): UuidArgs
	{
		return new UuidArgs();
	}

	public function getArgsType(): string
	{
		return UuidArgs::class;
	}

	/**
	 * @param UuidArgs $args
	 *
	 * @throws ObjectMapper\Exception\ValueDoesNotMatch
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function processValue(
		mixed $value,
		ObjectMapper\Args\Args $args,
		ObjectMapper\Context\FieldContext $context,
	): Uuid\UuidInterface
	{
		if ($value instanceof Uuid\UuidInterface) {
			return $value;
		}

		if (!is_string($value) || !Uuid\Uuid::isValid($value)) {
			throw ObjectMapper\Exception\ValueDoesNotMatch::create(
				$this->createType($args, $context),
				ObjectMapper\Processing\Value::of($value),
			);
		}

		return Uuid\Uuid::fromString($value);
	}

	/**
	 * @param UuidArgs $args
	 */
	public function createType(
		ObjectMapper\Args\Args $args,
		ObjectMapper\Context\TypeContext $context,
	): ObjectMapper\Types\SimpleValueType
	{
		return new ObjectMapper\Types\SimpleValueType('uuid');
	}

}
