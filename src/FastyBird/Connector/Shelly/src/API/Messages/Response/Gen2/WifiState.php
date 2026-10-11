<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * Generation 2 device wifi state message
 */
final class WifiState extends DeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('sta_ip')]
		private readonly string|null $staIp,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $status,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $ssid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly int|string $rssi,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('ap_client_count')]
		private readonly int|string $apClientCount,
	)
	{
		parent::__construct();
	}

	#[Override]
	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::WIFI;
	}

	public function getStaIp(): string|null
	{
		return $this->staIp;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|string
	{
		return $this->rssi;
	}

	public function getApClientCount(): int|string
	{
		return $this->apClientCount;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'sta_ip' => $this->getStaIp(),
				'status' => $this->getStatus(),
				'ssid' => $this->getSsid(),
				'rssi' => $this->getRssi(),
				'ap_client_count' => $this->getApClientCount(),
			],
		);
	}

}
