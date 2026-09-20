<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Module\Ui\Documents\Widgets\DataSources\Actions;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Documents\Exchange as ExchangeDocuments;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Channel control action document
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document]
#[ExchangeDocuments\Mapping\RoutingMap([
	Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY,
])]
final readonly class Action implements Documents\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\DataSourceAction::class)]
		private readonly Types\DataSourceAction $action,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $widget,
		#[ApplicationObjectMapper\UuidValue()]
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

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

}
