<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;
use function array_filter;
use function array_merge;

/**
 * Generation 2 device voltage state message
 */
final class DeviceVoltmeterState extends DeviceState implements API\Messages\Message
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|string|null $voltage,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|string|null $xvoltage = null,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	#[Override]
	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::VOLTMETER;
	}

	public function getVoltage(): float|string|null
	{
		return $this->voltage;
	}

	public function getXvoltage(): float|string|null
	{
		return $this->xvoltage;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'voltage' => $this->getVoltage(),
				'xvoltage' => $this->getXvoltage(),
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toState(): array
	{
		return array_filter(
			array_merge(
				parent::toState(),
				[
					'voltage' => $this->getVoltage(),
					'xvoltage' => $this->getXvoltage(),
				],
			),
			static fn ($value): bool => $value !== DeviceState::VALUE_NOT_AVAILABLE,
		);
	}

}
