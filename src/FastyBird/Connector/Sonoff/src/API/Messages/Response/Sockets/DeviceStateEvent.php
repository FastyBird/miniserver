<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Sockets;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Device updated status entity
 */
final readonly class DeviceStateEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('apikey')]
		private string $apiKey,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('deviceid')]
		private string $deviceId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private API\Messages\Uiid\Uuid|null $state = null,
	)
	{
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
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
			'api_key' => $this->getApiKey(),
			'device_id' => $this->getDeviceId(),
			'state' => $this->getState()?->toArray(),
		];
	}

}
