<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Report third-party device state to NS Panel request definition
 */
final readonly class ReportDeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceStateEvent::class)]
		private ReportDeviceStateEvent $event,
	)
	{
	}

	public function getEvent(): ReportDeviceStateEvent
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
			'event' => $this->getEvent()->toArray(),
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
