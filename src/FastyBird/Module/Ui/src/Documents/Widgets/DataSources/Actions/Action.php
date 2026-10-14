<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\DataSources\Actions;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Types;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Channel control action document
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Types\RoutingKey::WIDGET_DATA_SOURCE_ACTION->value,
])]
final readonly class Action implements UiDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\DataSourceAction::class)]
		private readonly Types\DataSourceAction $action,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $widget,
		#[Rules\UuidValue()]
		#[ObjectMapper\Modifiers\FieldName('data_source')]
		private readonly Uuid\UuidInterface $dataSource,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private readonly bool|float|int|string|null $expectedValue = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->dataSource;
	}

	public function getAction(): Types\DataSourceAction
	{
		return $this->action;
	}

	public function getWidget(): Uuid\UuidInterface
	{
		return $this->widget;
	}

	public function getDataSource(): Uuid\UuidInterface
	{
		return $this->dataSource;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'widget' => $this->getWidget()->toString(),
			'data_source' => $this->getDataSource()->toString(),
			'action' => $this->getAction()->value,
			'expected_value' => $this->getExpectedValue(),
		];
	}

	#[Override]
	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

}
