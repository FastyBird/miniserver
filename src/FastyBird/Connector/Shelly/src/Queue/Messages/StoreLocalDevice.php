<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queue\Messages;

use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;
use function array_unique;
use const SORT_REGULAR;

/**
 * Discovered local device message
 */
final class StoreLocalDevice extends Device
{

	/**
	 * @param array<ChannelDescription> $channels
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private readonly string $serialNumber,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\DeviceGeneration::class)]
		private readonly Types\DeviceGeneration $generation,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ip_address')]
		private readonly string $ipAddress,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $domain,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('mac_address')]
		private readonly string $macAddress,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auth_enabled')]
		private readonly bool $authEnabled,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('firmware_version')]
		private readonly string $firmwareVersion,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(ChannelDescription::class),
		)]
		private readonly array $channels,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	public function getGeneration(): Types\DeviceGeneration
	{
		return $this->generation;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getMacAddress(): string
	{
		return $this->macAddress;
	}

	public function isAuthEnabled(): bool
	{
		return $this->authEnabled;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	/**
	 * @return array<ChannelDescription>
	 */
	public function getChannels(): array
	{
		return array_unique($this->channels, SORT_REGULAR);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'serial_number' => $this->getSerialNumber(),
			'generation' => $this->getGeneration()->value,
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'model' => $this->getModel(),
			'mac_address' => $this->getMacAddress(),
			'auth_enabled' => $this->isAuthEnabled(),
			'firmware_version' => $this->getFirmwareVersion(),
			'channels' => array_map(
				static fn (ChannelDescription $channel): array => $channel->toArray(),
				$this->getChannels(),
			),
		]);
	}

}
