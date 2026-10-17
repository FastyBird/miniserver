<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Devices\Controls\Actions;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Types;
use Override;
use Ramsey\Uuid;

/**
 * Device control action document
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Types\RoutingKey::DEVICE_CONTROL_ACTION->value,
])]
final readonly class Action implements DevicesDocuments\Document
{

	/**
	 * @param bool|float|string|null $expectedValue A number is a float, as it was for the rule orisai mapped this with, which tried FloatValue before IntValue
	 */
	public function __construct(
		private Types\ControlAction $action,
		private Uuid\UuidInterface $device,
		private Uuid\UuidInterface $control,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('expected_value')]
		private bool|float|int|string|null $expectedValue = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->control;
	}

	public function getAction(): Types\ControlAction
	{
		return $this->action;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getControl(): Uuid\UuidInterface
	{
		return $this->control;
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
			'device' => $this->getDevice()->toString(),
			'control' => $this->getControl()->toString(),
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
