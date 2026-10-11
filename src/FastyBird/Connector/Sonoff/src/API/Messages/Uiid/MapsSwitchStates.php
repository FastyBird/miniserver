<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;

/**
 * Single switch states mapper
 *
 * @method string|null getSwitch()
 * @method string|null getStartup()
 * @method string|null getPulse()
 * @method int|null getPulseWidth()
 */
trait MapsSwitchStates
{

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array
	{
		return [
			Types\ParameterType::CHANNEL->value => [
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
					Types\PropertyParameter::VALUE->value => $this->getSwitch(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
					Types\PropertyParameter::VALUE->value => $this->getStartup(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
					Types\PropertyParameter::VALUE->value => $this->getPulse(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
					Types\PropertyParameter::VALUE->value => $this->getPulseWidth(),
					Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SWITCH->value,
				],
			],
		];
	}

}
