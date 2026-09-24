<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Devices\Documents\Devices\Controls\Actions;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device control action document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY,
])]
final readonly class Action implements DevicesDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\ControlAction::class)]
		private Types\ControlAction $action,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $control,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private bool|float|int|string|null $expectedValue = null,
	)
	{
	}

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

	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

}
