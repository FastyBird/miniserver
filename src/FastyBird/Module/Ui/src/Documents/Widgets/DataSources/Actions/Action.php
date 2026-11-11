<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\DataSources\Actions;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Types;
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
		private readonly Types\DataSourceAction $action,
		private readonly Uuid\UuidInterface $widget,
		#[Configurator\MapFromKey('data_source')]
		private readonly Uuid\UuidInterface $dataSource,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('expected_value')]
		private readonly bool|float|string|null $expectedValue = null,
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

	public function getExpectedValue(): float|bool|string|null
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
