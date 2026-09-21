<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Triggers\Schemas;
use Neomerx\JsonApi;
use function array_merge;

final class DummyConditionSchema extends Schemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::TRIGGERS->value . '/condition/' . DummyConditionEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyConditionEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'watch_item' => $resource->getWatchItem()->toString(),
			'operator' => $resource->getOperator()->value,
			'operand' => $resource->getOperand(),
		]);
	}

}
