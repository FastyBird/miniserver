<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Gateway report set device state response definition
 */
final readonly class SetDeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateEvent::class)]
		private SetDeviceStateEvent $event,
	)
	{
	}

	public function getEvent(): SetDeviceStateEvent
	{
		return $this->event;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'event' => $this->getEvent(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->event = $this->getEvent()->toJson();

		return $json;
	}

}
