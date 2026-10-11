<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Report third-party device state to NS Panel event payload request definition
 */
final readonly class ReportDeviceStateEventPayload implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\State::class)]
		private API\Messages\State $state,
	)
	{
	}

	/**
	 * @return array<API\Messages\States\State>
	 */
	public function getState(): array
	{
		return $this->state->getStates();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->state->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->state = $this->state->toJson();

		return $json;
	}

}
