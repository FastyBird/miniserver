<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Report third-party device online state to NS Panel request definition
 */
final readonly class ReportDeviceOnline implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceOnlineEvent::class)]
		private ReportDeviceOnlineEvent $event,
	)
	{
	}

	public function getEvent(): ReportDeviceOnlineEvent
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
