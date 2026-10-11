<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Types;
use Orisai\ObjectMapper;
use function array_map;
use function is_array;

/**
 * Local api device raw message
 */
final readonly class LocalDeviceMessage implements API\Messages\Message
{

	/**
	 * @param string|LocalDeviceWifiScan|array<DeviceDataPointState>|null $data
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\LocalDeviceCommand::class)]
		private Types\LocalDeviceCommand $command,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $sequence,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('return_code')]
		private int|null $returnCode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\MappedObjectValue(class: LocalDeviceWifiScan::class),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\MappedObjectValue(DeviceDataPointState::class),
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|array|LocalDeviceWifiScan|null $data = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\LocalDeviceError::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private Types\LocalDeviceError|null $error = null,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getCommand(): Types\LocalDeviceCommand
	{
		return $this->command;
	}

	public function getSequence(): int
	{
		return $this->sequence;
	}

	public function getReturnCode(): int|null
	{
		return $this->returnCode;
	}

	/**
	 * @return string|array<DeviceDataPointState>|LocalDeviceWifiScan|null
	 */
	public function getData(): string|array|LocalDeviceWifiScan|null
	{
		return $this->data;
	}

	public function getError(): Types\LocalDeviceError|null
	{
		return $this->error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'command' => $this->getCommand()->value,
			'sequence' => $this->getSequence(),
			'return_code' => $this->getReturnCode(),
			'data' => $this->getData() instanceof API\Messages\Message ? $this->getData()->toArray() : (is_array(
				$this->getData(),
			) ? array_map(
				static fn (
					API\Messages\Message $message,
				): array => $message->toArray(),
				$this->getData(),
			) : $this->getData()),
			'error' => $this->getError()?->value,
		];
	}

}
