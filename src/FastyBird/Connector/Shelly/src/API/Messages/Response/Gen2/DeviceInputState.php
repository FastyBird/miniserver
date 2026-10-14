<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;
use function array_filter;
use function array_merge;
use function is_bool;
use function is_int;

/**
 * Generation 2 device input state message
 */
final class DeviceInputState extends DeviceState implements API\Messages\Message
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\InputPayload::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Types\Payloads\InputPayload|bool|string|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|string|null $percent,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	#[Override]
	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::INPUT;
	}

	public function getState(): Types\Payloads\InputPayload|bool|string|null
	{
		return $this->state;
	}

	public function getPercent(): int|string|null
	{
		return $this->percent;
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
				'state' => $this->getState() instanceof Types\Payloads\InputPayload ? $this->getState()->value : $this->getState(),
				'percent' => $this->getPercent(),
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
				$this->getState() instanceof Types\Payloads\InputPayload ? ['button' => $this->getState()->value] : [],
				is_bool($this->getState()) ? ['switch' => $this->getState()] : [],
				is_int($this->getPercent()) ? ['analog' => $this->getPercent()] : [],
			),
			static fn ($value): bool => $value !== DeviceState::VALUE_NOT_AVAILABLE,
		);
	}

}
