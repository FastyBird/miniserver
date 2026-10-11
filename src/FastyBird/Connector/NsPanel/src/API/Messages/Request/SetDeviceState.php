<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel requested set device state request definition
 */
final readonly class SetDeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirective::class)]
		private SetDeviceStateDirective $directive,
	)
	{
	}

	public function getDirective(): SetDeviceStateDirective
	{
		return $this->directive;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'directive' => $this->getDirective()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->directive = $this->getDirective()->toJson();

		return $json;
	}

}
