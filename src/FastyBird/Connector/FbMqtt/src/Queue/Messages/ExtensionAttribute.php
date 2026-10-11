<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Device extension attribute
 */
final readonly class ExtensionAttribute implements Message
{

	public const string MAC_ADDRESS = 'mac-address';

	public const string MANUFACTURER = 'manufacturer';

	public const string MODEL = 'model';

	public const string VERSION = 'version';

	public const string NAME = 'name';

	public const array ALLOWED_PARAMETERS = [
		self::MAC_ADDRESS,
		self::MANUFACTURER,
		self::MODEL,
		self::VERSION,
		self::NAME,
	];

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $device,
		#[ObjectMapper\Rules\InstanceOfValue(type: Types\ExtensionType::class)]
		private Types\ExtensionType $extension,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: self::ALLOWED_PARAMETERS)]
		private string $parameter,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $value,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $retained = false,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getExtension(): Types\ExtensionType
	{
		return $this->extension;
	}

	public function getParameter(): string
	{
		return $this->parameter;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function isRetained(): bool
	{
		return $this->retained;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			$this->getParameter() => $this->getValue(),
		];
	}

}
