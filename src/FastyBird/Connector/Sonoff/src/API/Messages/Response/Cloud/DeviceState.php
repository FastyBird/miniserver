<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Device queried status entity
 */
final readonly class DeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $deviceId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private API\Messages\Uiid\Uuid|null $state = null,
	)
	{
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getState(): API\Messages\Uiid\Uuid|null
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'device_id' => $this->getDeviceId(),
			'state' => $this->getState()?->toArray(),
		];
	}

}
