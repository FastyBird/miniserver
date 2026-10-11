<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 1 device input state message
 */
final readonly class DeviceInputState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private int $input,
		#[ObjectMapper\Rules\StringValue()]
		private string $event,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('event_cnt')]
		private int $eventCount,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('last_sequence')]
		private string|null $lastSequence = null,
	)
	{
	}

	public function getInput(): int
	{
		return $this->input;
	}

	public function getEvent(): string
	{
		return $this->event;
	}

	public function getEventCnt(): int
	{
		return $this->eventCount;
	}

	public function getLastSequence(): string|null
	{
		return $this->lastSequence;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'input' => $this->getInput(),
			'event' => $this->getEvent(),
			'event_count' => $this->getEventCnt(),
			'last_sequence' => $this->getLastSequence(),
		];
	}

}
