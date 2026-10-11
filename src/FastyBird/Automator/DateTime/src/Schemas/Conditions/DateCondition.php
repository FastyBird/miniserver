<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Schemas\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Date condition entity schema
 *
 * @extends TriggersSchemas\Conditions\Condition<Entities\Conditions\DateCondition>
 */
final class DateCondition extends TriggersSchemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Automator::DATE_TIME->value . '/condition/' . Entities\Conditions\DateCondition::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Conditions\DateCondition::class;
	}

	/**
	 * @return iterable<string, string|bool|array<int>|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'date' => $resource->getDate()->format(DateTimeInterface::ATOM),
		]);
	}

}
