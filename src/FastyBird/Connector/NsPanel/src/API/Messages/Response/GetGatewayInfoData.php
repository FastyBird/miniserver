<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel report its description data response definition
 */
final readonly class GetGatewayInfoData implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $ip,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('mac')]
		private string $macAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $domain,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('fw_version')]
		private string $firmwareVersion,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ip;
	}

	public function getMacAddress(): string
	{
		return $this->macAddress;
	}

	public function getDomain(): string
	{
		return $this->domain;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'ip_address' => $this->getIpAddress(),
			'mac_address' => $this->getMacAddress(),
			'domain' => $this->getDomain(),
			'firmware_version' => $this->getFirmwareVersion(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->ip = $this->getIpAddress();
		$json->mac = $this->getMacAddress();
		$json->domain = $this->getDomain();
		$json->fw_version = $this->getFirmwareVersion();

		return $json;
	}

}
