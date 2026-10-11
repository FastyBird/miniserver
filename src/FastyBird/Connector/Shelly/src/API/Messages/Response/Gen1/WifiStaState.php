<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 1 device wifi state message
 */
final readonly class WifiStaState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $connected,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $ssid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|null $rssi,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $ip,
	)
	{
	}

	public function isConnected(): bool
	{
		return $this->connected;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi;
	}

	public function getIp(): string|null
	{
		return $this->ip;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'connected' => $this->isConnected(),
			'ssid' => $this->getSsid(),
			'rssi' => $this->getRssi(),
			'ip' => $this->getIp(),
		];
	}

}
