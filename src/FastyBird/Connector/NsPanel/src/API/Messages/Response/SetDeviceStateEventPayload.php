<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper\Rules\BackedEnumValue;
use stdClass;

/**
 * Gateway report set device state event payload response definition
 */
final readonly class SetDeviceStateEventPayload implements API\Messages\Message
{

	public function __construct(
		#[BackedEnumValue(class: Types\ServerStatus::class)]
		private Types\ServerStatus $type,
	)
	{
	}

	public function getType(): Types\ServerStatus
	{
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'type' => $this->getType()->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->type = $this->getType()->value;

		return $json;
	}

}
