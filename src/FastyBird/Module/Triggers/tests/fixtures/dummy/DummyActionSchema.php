<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers\Schemas;
use Neomerx\JsonApi;
use function array_merge;

final class DummyActionSchema extends Schemas\Actions\Action
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/action/' . DummyActionEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyActionEntity::class;
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
			'do_item' => $resource->getDoItem()->toString(),
			'value' => $resource->getValue(),
		]);
	}

}
