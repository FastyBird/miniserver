<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Application\Rules;

use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function is_string;

/**
 * @implements ObjectMapper\Rules\Rule<UuidArgs>
 */
final class UuidRule implements ObjectMapper\Rules\Rule
{

	#[Override]
	public function resolveArgs(array $args, ObjectMapper\Meta\Context\MetaFieldContext $context): UuidArgs
	{
		return new UuidArgs();
	}

	#[Override]
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
	#[Override]
	public function processValue(
		mixed $value,
		ObjectMapper\Args\Args $args,
		ObjectMapper\Processing\Context\ServicesContext $services,
		ObjectMapper\Processing\Context\PropertyContext $property,
		ObjectMapper\Processing\Context\DynamicContext $dynamic,
	): Uuid\UuidInterface
	{
		if ($value instanceof Uuid\UuidInterface) {
			return $value;
		}

		if (!is_string($value) || !Uuid\Uuid::isValid($value)) {
			throw ObjectMapper\Exception\ValueDoesNotMatch::create(
				$this->createType($args, $services, $dynamic),
				ObjectMapper\Processing\Value::of($value),
			);
		}

		return Uuid\Uuid::fromString($value);
	}

	/**
	 * @param UuidArgs $args
	 */
	#[Override]
	public function createType(
		ObjectMapper\Args\Args $args,
		ObjectMapper\Processing\Context\ServicesContext $services,
		ObjectMapper\Processing\Context\DynamicContext $dynamic,
	): ObjectMapper\Types\SimpleValueType
	{
		return new ObjectMapper\Types\SimpleValueType('uuid');
	}

}
