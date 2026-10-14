<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Nette\Utils;
use Orisai\ObjectMapper;
use Override;
use function array_filter;
use function array_merge;
use function intval;
use function is_float;

/**
 * Generation 2 device light state message
 */
final class DeviceLightState extends DeviceState implements API\Messages\Message
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $source,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly bool|string $output,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly int|string $brightness,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_started_at')]
		private readonly float|string $timerStartedAt,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_duration')]
		private readonly float|string $timerDuration,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	#[Override]
	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::LIGHT;
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	public function getOutput(): bool|string
	{
		return $this->output;
	}

	public function getBrightness(): int|string
	{
		return $this->brightness;
	}

	/**
	 * @throws Exception
	 */
	public function getTimerStartedAt(): DateTimeInterface|string
	{
		if (is_float($this->timerStartedAt)) {
			return Utils\DateTime::from(intval($this->timerStartedAt));
		}

		return $this->timerStartedAt;
	}

	public function getTimerDuration(): float|string
	{
		return $this->timerDuration;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'source' => $this->getSource(),
				'output' => $this->getOutput(),
				'brightness' => $this->getBrightness(),
				'timer_started_at' => !$this->getTimerStartedAt() instanceof DateTimeInterface
					? $this->getTimerStartedAt()
					: $this->getTimerStartedAt()->format(DateTimeInterface::ATOM),
				'timer_duration' => $this->getTimerDuration(),
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
					'output' => $this->getOutput(),
					'brightness' => $this->getBrightness(),
				],
			),
			static fn ($value): bool => $value !== DeviceState::VALUE_NOT_AVAILABLE,
		);
	}

}
