<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Schemas\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use Override;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Channel property condition entity schema
 *
 * @extends TriggersSchemas\Conditions\Condition<Entities\Conditions\ChannelPropertyCondition>
 */
final class ChannelPropertyCondition extends TriggersSchemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Automator::DEVICE_MODULE->value . '/condition/' . Entities\Conditions\ChannelPropertyCondition::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Conditions\ChannelPropertyCondition::class;
	}

	/**
	 * @return iterable<string, string|bool|array<int>|null>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'device' => $resource->getDevice()->toString(),
			'channel' => $resource->getChannel()->toString(),
			'property' => $resource->getProperty()->toString(),
			'operator' => $resource->getOperator()->value,
			'operand' => Utilities\Value::toString($resource->getOperand()),
		]);
	}

}
